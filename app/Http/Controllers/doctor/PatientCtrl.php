<?php

namespace App\Http\Controllers\doctor;

use App\Activity;
use App\VitalSigns;
use App\PhysicalExam;
use App\Baby;
use App\Barangay;
use App\Facility;
use App\Http\Controllers\DeviceTokenCtrl;
use App\Http\Controllers\ParamCtrl;
use App\Icd10;
use App\Muncity;
use App\PatientForm;
use App\Patients;
use App\PregnantForm;
use App\Pregnancy;
use App\Profile;
use App\Province;
use App\Tracking;
use App\User;
use Illuminate\Support\Facades\Input;
use \Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;

class PatientCtrl extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        //$this->middleware('doctor');
    }

    public function searchProfile(Request $req)
    {
        $data = array(
            'keyword' => $req->keyword,
            'brgy' => $req->brgy,
            'muncity' => $req->muncity,
            'others' => $req->others,
            'source' => $req->source
        );
        Session::put('profileSearch',$data);
        return self::index();
    }

    public function index()
    {
        ParamCtrl::lastLogin();

        $user = Session::get('auth');
        $muncity = Muncity::where('province_id',$user->province)->orderby('description','asc')->get();

        $keyword = '';
        $brgy = '';
        $mun = '';
        $others = '';
        $session = Session::get('profileSearch');
        if(isset($session))
        {
            $keyword = $session['keyword'];
            $brgy = $session['brgy'];
            $mun = $session['muncity'];
            $others = $session['others'];
        }

        $source='referral';

        $data = array();

        if(!empty($keyword) || !empty($mun) || !empty($brgy))
        {
            // $tsekap = Profile::orderBy('lname','asc')
            //     ->where('barangay_id',$brgy)
            //     ->where('muncity_id',$mun)
            //     ->where(function($q) use($keyword){
            //         $q->where('lname',"like","%$keyword%")
            //             ->orWhere('fname','like',"%$keyword%")
            //             ->orwhere(DB::raw('concat(fname," ",lname)'),"like","%$keyword%");
            //     })
            //     ->get();

            // foreach($tsekap as $req){
            //     $unique = array(
            //         $req->fname,
            //         $req->mname,
            //         $req->lname,
            //         date('Ymd',strtotime($req->dob)),
            //         $req->barangay_id
            //     );
            //     $unique = implode($unique);

            //     $match = array('unique_id'=>$unique);
            //     $data = array(
            //         'phic_status' => ($req->phic_status) ? $req->phic_status: '',
            //         'phic_id' => ($req->phicID) ? $req->phicID: '',
            //         'fname' => ($req->fname) ? $req->fname: '',
            //         'mname' => ($req->mname) ? $req->mname: '',
            //         'lname' => ($req->lname) ? $req->lname: '',
            //         'dob' => ($req->dob) ? $req->dob: '',
            //         'sex' => ($req->sex) ? $req->sex: '',
            //         'muncity' => ($req->muncity_id) ? $req->muncity_id : '',
            //         'province' => ($req->province_id) ? $req->province_id : '',
            //         'brgy' => ($req->barangay_id) ? $req->barangay_id: ''
            //     );
            //     Patients::updateOrCreate($match,$data);
            // }
            $facility_id = $user->facility_id;

            $data = Patients::orderBy('lname','asc');
            if(!empty($brgy)){
                $data = $data->where('brgy',$brgy);
            }
            if(!empty($mun) && $mun!='others'){
                $data = $data->where('muncity',$mun);
            }
            if(!empty($others)){
                $data = $data->where('address','like',"%$others%");
            }

            $data = $data->where('facility_id',$facility_id)
            ->where(function($q) use($keyword){     
                $q->where('lname',"like","%$keyword%")
                    ->orWhere('fname','like',"%$keyword%")
                    ->orwhere(DB::raw('concat(fname," ",lname)'),"like","%$keyword%");
            });

            $data = $data->paginate(20);
        }

        //$icd10 = \DB::connection('mysql')->select("call icd10()");
        return view('doctor.patient',[
            'title' => 'Patient List',
            'data' => $data,
            'muncity' => $muncity,
            'source' => $source,
            //'icd10' => $icd10,
            'sidebar' => 'filter_profile'
        ]);
    }

    public function storePatient(Request $req)
    {
        $user = Session::get('auth');
        $facility_id = $user->facility_id;
        $unique = array(
            $req->fname,
            $req->mname,
            $req->lname,
            date('Ymd',strtotime($req->dob)),
            $req->brgy

            
        );
        $unique = implode($unique);

        $match = array('unique_id' => $unique);
        $data = array(
            'phic_status' => $req->phic_status,
            'phic_id' => ($req->phicID) ? $req->phicID: '',
            'fname' => $req->fname,
            'mname' => $req->mname,
            'lname' => $req->lname,
            'contact' => $req->contact,
            'dob' => $req->dob,
            'sex' => $req->sex,
            'civil_status' => $req->civil_status,
            'facility_id' => $facility_id,
            'muncity' => $req->muncity,
            'province' => $user->province,
            'brgy' => ($req->brgy) ? $req->brgy:'' ,
            'address' => ($req->others) ? $req->others: ''
        );

        Patients::updateOrCreate($match,$data);

        $data = array(
            'keyword' => $req->fname.' '.$req->lname,
            'brgy' => $req->brgy,
            'muncity' => $req->muncity,
            'others' => '',
            'source' => 'referral'
        );
        Session::put('profileSearch',$data);
        return redirect('doctor/patient');
    }

    public function addPatient()
    {
        $user = Session::get('auth');
        $muncity = Muncity::where('province_id',$user->province)->where(function($q){
            $q->WhereNull("vaccine_used")
            ->orWhere("vaccine_used","No");
        })
        ->orderby('description','asc')
        ->get();
        return view('doctor.addPatient',[
            'title' => 'Add New Patient',
            'muncity' => $muncity,
            'method' => 'store'
        ]);
    }

    public function updatePatient(Request $request){
        $data = Patients::find($request->patient_id);
        if($request->patient_update_button){
            $data_update = $request->all();
            unset($data_update['_token']);
            unset($data_update['patient_update_button']);
            unset($data_update['patient_id']);
            $data->update($data_update);
            Session::put('patient_update_save',true);
            Session::put('patient_message','Successfully updated patient');
            return Redirect::back();
        }
        return view('doctor.patient_body',[
            "data" => $data
        ]);
    }

    public function showPatientProfile($id)
    {
        $data = Patients::find($id);
        if($data->brgy)
        {
            $brgy = Barangay::find($data->brgy)->description;
            $muncity = Muncity::find($data->muncity)->description;
            $province = Province::find($data->province)->description;
            $data->address = "$brgy, $muncity, $province";
        }else{
            $data->address = $data->address;
        }
        $data->patient_name = "$data->fname $data->mname $data->lname";
        $data->age = ParamCtrl::getAge($data->dob);
        return $data;
    }

    public function caseInfo($id)
    {
        $result = VitalSigns::where('patient_id',$id)
            ->where('void',1)
            ->orderby('id','desc')
            ->first();
        
        return $result;
    }

    public function pexamInfo($id)
    {
        $results = PhysicalExam::where('patient_id',$id)
            ->where('void',1)
            ->orderby('id','desc')
            ->first();
        
        return $results;
    }

    public function showTsekapProfile($id)
    {
        $data = Profile::find($id);
        if($data->barangay_id)
        {
            $brgy = Barangay::find($data->barangay_id)->description;
            $muncity = Muncity::find($data->muncity_id)->description;
            $province = Province::find($data->province_id)->description;
            $data->address = "$brgy, $muncity, $province";
        }else{
            $data->address = 'N/A';
        }
        $data->patient_name = "$data->fname $data->mname $data->lname";
        $data->age = ParamCtrl::getAge($data->dob);
        $data->civil_status = 'Single';
        $data->phic_status = 'None';
        $data->phic_id = $data->phicID;
        return $data;
    }

    public function searchTsekap(Request $req)
    {
        $data = array(
            'keyword' => $req->keyword,
            'brgy' => $req->brgy,
            'muncity' => $req->muncity
        );
        Session::put('tsekapSearch',$data);
        return self::tsekap();
    }

    public function tsekap()
    {
        ParamCtrl::lastLogin();
        $keyword = '';
        $brgy = '';
        $mun = '';
        $session = Session::get('tsekapSearch');
        if(isset($session))
        {
            $keyword = $session['keyword'];
            $brgy = $session['brgy'];
            $mun = $session['muncity'];
        }
        $user = Session::get('auth');
        $muncity = Muncity::where('province_id',$user->province)->orderby('description','asc')->get();

        $data = array();

        if(isset($keyword) && isset($brgy) && isset($mun)){
            $data = Profile::orderBy('lname','asc');
            if(isset($brgy)){
                $data = $data->where('barangay_id',$brgy);
            }else if(isset($mun)){
                $data = $data->where('muncity_id',$mun);
            }

            $data = $data->where(function($q) use($keyword){
                    $q->where('lname',"$keyword")
                        ->orwhere(DB::raw('concat(fname," ",lname)'),"$keyword");
                });

            $data = $data->where('barangay_id','>',0)
                        ->paginate(20);
        }

        return view('doctor.tsekap',[
            'title' => 'Patient List: Tsekap Profiles',
            'data' => $data,
            'muncity' => $muncity,
            'source' => 'tsekap',
            'sidebar' => 'tsekap_profile'
        ]);
    }

    function addTracking($code,$patient_id,$user,$req,$type, $form_id,$status='')
    {
        $match = array(
            'code' => $code
        );
        $track = array(
            'patient_id' => $patient_id,
            'date_referred' => date('Y-m-d H:i:s'),
            'referred_from' => ($status=='walkin') ? $req->referring_facility_walkin : $user->facility_id,
            'referred_to' => ($status=='walkin') ? $user->facility_id : $req->referred_facility,
            'department_id' => $req->referred_department,
            'referring_md' => ($status=='walkin') ? 0 : $user->id,
            'action_md' => '',
            'type' => $type,
            'form_id' => $form_id,
            'remarks' => ($req->reason) ? $req->reason: '',
            'status' => ($status=='walkin') ? 'accepted' : 'referred',
            'walkin' => 'no'
        );

        if($status=='walkin'){
            $track['date_seen'] = date('Y-m-d H:i:s');
            $track['date_accepted'] = date('Y-m-d H:i:s');
            $track['action_md'] = $user->id;
            $track['walkin'] = 'yes';
        }

        $tracking = Tracking::updateOrCreate($match,$track);

        $activity = array(
            'code' => $code,
            'patient_id' => $patient_id,
            'date_referred' => date('Y-m-d H:i:s'),
            'date_seen' => ($status=='walkin') ? date('Y-m-d H:i:s') : '',
            'referred_from' => ($status=='walkin') ? $req->referring_facility_walkin : $user->facility_id,
            'referred_to' => ($status=='walkin') ? $user->facility_id : $req->referred_facility,
            'department_id' => $req->referred_department,
            'referring_md' => ($status=='walkin') ? 0 : $user->id,
            'action_md' => '',
            'remarks' => ($req->reason) ? $req->reason: '',
            'status' => 'referred'
        );

        Activity::create($activity);
        if($status=='walkin'){
            $activity['date_seen'] = date('Y-m-d H:i:s');
            $activity['status'] = 'accepted';
            $activity['remarks'] = 'Walk-In Patient';
            $activity['action_md'] = $user->id;
            Activity::create($activity);
        }

        $tracking_id = $tracking->id;

        return $tracking_id;
    }

    function referPatient(Request $req,$type)
    {
        $user = Session::get('auth');
        $patient_id = $req->patient_id;
        $user_code = str_pad($user->facility_id,3,0,STR_PAD_LEFT);
        $code = date('ymd').'-'.$user_code.'-'.date('His');
        $tracking_id = 0;
        $case_summary = implode(" , ",$req->case_summary); 

        foreach ($req->diagnosis as $value) {
            $diagnosis .= $value . " ,";
         }

        //serialize($req->case_summary);
            
        if($req->source == 'tsekap')
        {
            $patient_id = self::importTsekap($req->patient_id,$req->patient_status,$req->phic_id,$req->phic_status);
        }

        $unique_id = "$patient_id-$user->facility_id-".date('ymdH');
        $match = array(
            'unique_id' => $unique_id
        );

        if($type==='normal')
        {
            Patients::where('id',$patient_id)
                ->update([
                    'sex' => $req->patient_sex,
                    'civil_status' => $req->civil_status,
                    'phic_status' => $req->phic_status,
                    'phic_id' => $req->phic_id
                ]);

            $data = array(
                'referring_facility' => $user->facility_id,
                'referred_to' => $req->referred_facility,
                'department_id' => $req->referred_department,
                'covid_number' => $req->covid_number,
                'refer_clinical_status' => $req->clinical_status,
                'refer_sur_category' => $req->sur_category,
                'time_referred' => date('Y-m-d H:i:s'),
                'time_transferred' => '',
                'patient_id' => $patient_id,
                'case_summary' => $case_summary,
                'reco_summary' => $req->reco_summary,
                'diagnosis' => $diagnosis,
                //'icd_code' => $req->icd_code,
                'reason' => $req->reason,
                'referring_md' => $user->id,
                'referred_md' => ($req->reffered_md) ? $req->reffered_md: 0,
            );
            $form = PatientForm::updateOrCreate($match,$data);
            if($form->wasRecentlyCreated){
                PatientForm::where('unique_id',$unique_id)
                    ->update([
                        'code' => $code
                    ]);
                $tracking_id = self::addTracking($code,$patient_id,$user,$req,$type,$form->id,'refer');
            }
        }
        else if($type==='pregnant')
        {
            $baby = array(
                'fname' => ($req->baby_fname) ? $req->baby_fname: '',
                'mname' => ($req->baby_mname) ? $req->baby_mname: '',
                'lname' => ($req->baby_lname) ? $req->baby_lname: '',
                'dob' => ($req->baby_dob) ? $req->baby_dob: '',
                'civil_status' => 'Single'
            );
            $baby_id = self::storeBabyAsPatient($baby,$patient_id);

            Baby::updateOrCreate([
                'baby_id' => $baby_id,
                'mother_id' => $patient_id
            ],[
                'weight' => ($req->baby_weight) ? $req->baby_weight:'',
                'gestational_age' => ($req->baby_gestational_age) ? $req->baby_gestational_age: ''
            ]);

            $data = array(
                'referring_facility' => ($user->facility_id) ? $user->facility_id: '',
                'referred_by' => ($user->id) ? $user->id: '',
                'record_no' => ($req->record_no) ? $req->record_no: '',
                'referred_date' => date('Y-m-d H:i:s'),
                'referred_to' => ($req->referred_facility) ? $req->referred_facility: '',
                'department_id' => ($req->referred_department) ? $req->referred_department:'',
                'covid_number' => $req->covid_number,
                'refer_clinical_status' => $req->clinical_status,
                'refer_sur_category' => $req->sur_category,
                'health_worker' => ($req->health_worker) ? $req->health_worker: '',
                'patient_woman_id' => $patient_id,
                'woman_reason' => ($req->woman_reason) ? $req->woman_reason: '',
                'woman_major_findings' => ($req->woman_major_findings) ? $req->woman_major_findings: '',
                'woman_before_treatment' => ($req->woman_before_treatment) ? $req->woman_before_treatment: '',
                'woman_before_given_time' => ($req->woman_before_given_time) ? $req->woman_before_given_time: '',
                'woman_during_transport' => ($req->woman_during_treatment) ? $req->woman_during_treatment: '',
                'woman_transport_given_time' => ($req->woman_during_given_time) ? $req->woman_during_given_time: '',
                'woman_information_given' => ($req->woman_information_given) ? $req->woman_information_given: '',
                'patient_baby_id' => $baby_id,
                'baby_reason' => ($req->baby_reason) ? $req->baby_reason: '',
                'baby_major_findings' => ($req->baby_major_findings) ? $req->baby_major_findings: '',
                'baby_last_feed' => ($req->baby_last_feed) ? $req->baby_last_feed: '',
                'baby_before_treatment' => ($req->baby_before_treatment) ? $req->baby_before_treatment: '',
                'baby_before_given_time' => ($req->baby_before_given_time) ? $req->baby_before_given_time: '',
                'baby_during_transport' => ($req->baby_during_treatment) ? $req->baby_during_treatment: '',
                'baby_transport_given_time' => ($req->baby_during_given_time) ? $req->baby_during_given_time: '',
                'baby_information_given' => ($req->baby_information_given) ? $req->baby_information_given: '',
                'woman_age' => ($req->woman_age) ? $req->woman_age : '',
            );
            $form = PregnantForm::updateOrCreate($match,$data);
            if($form->wasRecentlyCreated){
                PregnantForm::where('unique_id',$unique_id)
                    ->update([
                        'code' => $code
                    ]);
                $tracking_id = self::addTracking($code,$patient_id,$user,$req,$type,$form->id);
            }
        }

        Session::put("refer_patient",true);

        return array(
            'id' => $tracking_id,
            'patient_code' => $code,
            'referred_date' => date('M d, Y h:i A')
        );
    }

    public function vitalInfo(Request $req)
    {
            $vitalPage = 1;

              $vital_data = VitalSigns::where('patient_id',$req->patient_id)
                ->where('void','1')
                ->orderBy('id','desc')
                ->paginate(1);

                $pexam_data = PhysicalExam::where('patient_id',$req->patient_id)
                ->where('void','1')
                ->orderBy('id','desc')
                ->paginate(1);

                $patient_id = $req->patient_id;

        return view('doctor.vital_body',[
                'vital_data' => $vital_data,
                'pexam_data' => $pexam_data,
                'patient_id' => $patient_id
        ]);
    }

    public function addVital(Request $request)
    {
        $user = Session::get('auth');
        Session::put('patient_id_unique',$request->patient_id);
       if($request->type == 'vital')
            {

                $match = array(
                    'id' => $request->vital_id
                );

                $data = array(
                    'patient_id' => ($request->patient_id) ? $request->patient_id: '',
                    'bps' => ($request->bps) ? $request->bps: '',
                    'bpd' => ($request->bpd) ? $request->bpd: '',
                    'respiratory_rate' => ($request->respiratory_rate) ? $request->respiratory_rate: '',
                    'body_temperature' => ($request->body_temperature) ? $request->body_temperature: '',
                    'heart_rate' => ($request->heart_rate) ? $request->heart_rate: '',
                    'normal_rate' => ($request->normal_rate) ? $request->normal_rate: '',
                    'regular_rhythm' => ($request->regular_rhythm) ? $request->regular_rhythm: '',
                    'pulse_rate' => ($request->pulse_rate) ? $request->pulse_rate: '',
                    'oxygen_saturation' => ($request->oxygen_saturation) ? $request->oxygen_saturation: '',
                    'consolidation_date' => date('Y-m-d h:i:s'),
                    'consultation_date' => ($request->consultation_date) ? $request->consultation_date: '',
                    'administered_by' => ($request->administered_by) ? $request->administered_by: '',
                    'remarks' => ($request->remarks) ? $request->remarks: '',
                    'encoded_by' => $user->id,
                    'void' => '1'
                );
            
              $form =  VitalSigns::updateOrCreate($match,$data);
              if($form->wasRecentlyCreated){
                Session::put('vital_sign_save',true);
                Session::put('vital_sign_message','Successfully added vital signs');
                }else{

                    Session::put('vital_sign_update',true);
                    Session::put('vital_update_message','Successfully updated vital signs');
                }
                return Redirect::back();
            }
            elseif($request->type == 'pexam')
            {
                foreach ($request->conjunctiva as $value) {
                    $conjunctiva .= $value . ",";
                 }
                 foreach ($request->neck as $value) {
                    $neck .= $value . ",";
                 }
                 foreach ($request->breast as $value) {
                    $breast .= $value . ",";
                 }
                 foreach ($request->thorax as $value) {
                    $thorax .= $value . ",";
                 }
                 foreach ($request->abdomen as $value) {
                    $abdomen .= $value . ",";
                 }
                 foreach ($request->genitals as $value) {
                    $genitals .= $value . ","; 
                 }
                 foreach ($request->extremities as $value) {
                    $extremities .= $value . ",";
                 }
                 $match = array(
                    'id' => $request->pexam_id
                );

                 $data = array(
                    'patient_id' => ($request->patient_id) ? $request->patient_id: '',
                    'heigth' => ($request->heigth) ? $request->heigth: '',
                    'weigth' => ($request->weigth) ? $request->weigth: '',
                    'head' => ($request->head) ? $request->head: '',
                    'conjunctiva' => ($conjunctiva) ? $conjunctiva: '',
                    'conjunctiva_remarks' => ($request->conjunctiva_remarks) ? $request->conjunctiva_remarks: '',
                    'neck' => ($neck) ? $neck: '',
                    'chest' => ($request->chest) ? $request->chest: '',
                    'breast' => ($breast) ? $breast: '',
                    'breast_remarks' => ($request->breast_remarks) ? $request->breast_remarks: '',
                    'thorax' => ($thorax) ? $thorax: '',
                    'thorax_remarks' => ($request->thorax_remarks) ? $request->thorax_remarks: '',
                    'abdomen' => ($abdomen) ? $abdomen: '',
                    'abdomen_remarks' => ($request->abdomen_remarks) ? $request->abdomen_remarks: '',
                    'genitals' => ($genitals) ? $genitals: '',
                    'genitals_remarks' => ($request->genitals_remarks) ? $request->genitals_remarks: '',
                    'extremities' => ($extremities) ? $extremities: '',
                    'extremities_remarks' => ($request->extremities_remarks) ? $request->extremities_remarks: '',
                    'others' => ($request->others) ? $request->others: '',
                    'administered_by' => ($request->administered_by) ? $request->administered_by: '',
                    'encoded_by' => $user->id,
                    'waist_circumference' => ($request->waist_circumference) ? $request->waist_circumference: '',
                    'consulidation_date' => date('Y-m-d H:i:s'),
                    'consultation_date' => ($request->consultation_date) ? $request->consultation_date: '',
                    'void' => '1'
                );

                $form = PhysicalExam::UpdateOrCreate($match,$data);
                if($form->wasRecentlyCreated){
                Session::put('physical_exam_save',true);
                Session::put('physical_exam_message','Successfully added physical exam');
                }else
                {
                Session::put('pexam_update',true);
                Session::put('pexam_update_message','Successfully updated physical exam');
                }
                return Redirect::back();
            }
    }

    public function removeVital($id)
    {
        VitalSigns::where('id',$id)
        ->update([
            'void' => '0'
        ]);
        return array(
            'message' => 'Successfully removed vital signs'
        );
    }

    public function removePexam($id)
    {
        PhysicalExam::where('id',$id)
        ->update([
            'void' => '0'
        ]);
        return array(
            'message' => 'Successfully removed physical exam'
        );
    }


    static function getVital($id)
    {
        $data = VitalSigns::where('id',$id)
        ->where('void','1')
        ->first();
        return array(
            'data' => $data
        );
    }

    static function getPexam($id)
    {
        $data = PhysicalExam::where('id',$id)
        ->where('void','1')
        ->first();
        return array(
            'data' => $data
        );
    }


    function referPatientWalkin(Request $req,$type)
    {
        $user = Session::get('auth');
        $patient_id = $req->patient_id;
        $user_code = str_pad($user->facility_id,3,0,STR_PAD_LEFT);
        $code = date('ymd').'-'.$user_code.'-'.date('His');
        $tracking_id = 0;
        if($req->source==='tsekap')
        {
            $patient_id = self::importTsekap($req->patient_id,$req->patient_status,$req->phic_id,$req->phic_status);
        }

        $unique_id = "$patient_id-$user->facility_id-".date('ymdH');
        $match = array(
            'unique_id' => $unique_id
        );

        if($type==='normal')
        {
            Patients::where('id',$patient_id)
                ->update([
                    'sex' => $req->patient_sex,
                    'civil_status' => $req->civil_status,
                    'phic_status' => $req->phic_status,
                    'phic_id' => $req->phic_id
                ]);

            $data = array(
                'referring_facility' => $req->referring_facility_walkin,
                'referred_to' => $user->facility_id,
                'department_id' => $req->referred_department,
                'time_referred' => date('Y-m-d H:i:s'),
                'time_transferred' => '',
                'patient_id' => $patient_id,
                'case_summary' => $req->case_summary,
                'reco_summary' => $req->reco_summary,
                'diagnosis' => $req->diagnosis,
                //'icd_code' => $req->icd_code_walkin,
                'reason' => $req->reason,
                'referring_md' => $user->id,
                'referred_md' => ($req->reffered_md) ? $req->reffered_md: 0,
            );
            $form = PatientForm::updateOrCreate($match,$data);
            if($form->wasRecentlyCreated){
                PatientForm::where('unique_id',$unique_id)
                    ->update([
                        'code' => $code
                    ]);
                $req->reffered_to = $user->facility_id;

                $tracking_id = self::addTracking($code,$patient_id,$user,$req,$type,$form->id,'walkin');
            }
        }
        else if($type==='pregnant')
        {
            $baby = array(
                'fname' => ($req->baby_fname) ? $req->baby_fname: '',
                'mname' => ($req->baby_mname) ? $req->baby_mname: '',
                'lname' => ($req->baby_lname) ? $req->baby_lname: '',
                'dob' => ($req->baby_dob) ? $req->baby_dob: '',
                'civil_status' => 'Single'
            );
            $baby_id = self::storeBabyAsPatient($baby,$patient_id);

            Baby::updateOrCreate([
                'baby_id' => $baby_id,
                'mother_id' => $patient_id
            ],[
                'weight' => ($req->baby_weight) ? $req->baby_weight:'',
                'gestational_age' => ($req->baby_gestational_age) ? $req->baby_gestational_age: ''
            ]);

            $data = array(
                'referring_facility' => ($req->referring_facility_walkin) ? $req->referring_facility_walkin: '',
                'referred_by' => $user->id,
                'record_no' => ($req->record_no) ? $req->record_no: '',
                'referred_date' => date('Y-m-d H:i:s'),
                'referred_to' => ($user->facility_id) ? $user->facility_id: '',
                'department_id' => ($req->referred_department) ? $req->referred_department:'',
                'health_worker' => ($req->health_worker) ? $req->health_worker: '',
                'patient_woman_id' => $patient_id,
                'woman_reason' => ($req->woman_reason) ? $req->woman_reason: '',
                'woman_major_findings' => ($req->woman_major_findings) ? $req->woman_major_findings: '',
                'woman_before_treatment' => ($req->woman_before_treatment) ? $req->woman_before_treatment: '',
                'woman_before_given_time' => ($req->woman_before_given_time) ? $req->woman_before_given_time: '',
                'woman_during_transport' => ($req->woman_during_treatment) ? $req->woman_during_treatment: '',
                'woman_transport_given_time' => ($req->woman_during_given_time) ? $req->woman_during_given_time: '',
                'woman_information_given' => ($req->woman_information_given) ? $req->woman_information_given: '',
                'patient_baby_id' => $baby_id,
                'baby_reason' => ($req->baby_reason) ? $req->baby_reason: '',
                'baby_major_findings' => ($req->baby_major_findings) ? $req->baby_major_findings: '',
                'baby_last_feed' => ($req->baby_last_feed) ? $req->baby_last_feed: '',
                'baby_before_treatment' => ($req->baby_before_treatment) ? $req->baby_before_treatment: '',
                'baby_before_given_time' => ($req->baby_before_given_time) ? $req->baby_before_given_time: '',
                'baby_during_transport' => ($req->baby_during_treatment) ? $req->baby_during_treatment: '',
                'baby_transport_given_time' => ($req->baby_during_given_time) ? $req->baby_during_given_time: '',
                'baby_information_given' => ($req->baby_information_given) ? $req->baby_information_given: '',

            );
            $form = PregnantForm::updateOrCreate($match,$data);
            if($form->wasRecentlyCreated){
                PregnantForm::where('unique_id',$unique_id)
                    ->update([
                        'code' => $code
                    ]);
                $tracking_id = self::addTracking($code,$patient_id,$user,$req,$type,$form->id,'walkin');
            }
        }

        return array(
            'id' => $tracking_id,
            'patient_code' => $code,
            'referred_date' => date('M d, Y h:i A')
        );
    }

    function storeBabyAsPatient($data,$mother_id)
    {
        if($data['fname']){
            $mother = Patients::find($mother_id);
            $data['brgy'] = $mother->brgy;
            $data['muncity'] = $mother->muncity;
            $data['province'] = $mother->province;
            $dob = date('ymd',strtotime($data['dob']));
            $tmp = array(
                $data['fname'],
                $data['mname'],
                $data['lname'],
                $data['brgy'],
                $dob
            );
            $unique = implode($tmp);
            $match = array(
                'unique_id' => $unique
            );

            $patient = Patients::updateOrCreate($match,$data);
            return $patient->id;
        }else{
            return '0';
        }
    }

    function importTsekap($patient_id,$civil_status='',$phic_id='',$phic_status='')
    {
        $profile = Profile::find($patient_id);

        $unique = array(
            $profile->fname,
            $profile->mname,
            $profile->lname,
            date('Ymd',strtotime($profile->dob)),
            $profile->barangay_id
        );
        $unique = implode($unique);
        $match = array(
            'unique_id' => $unique
        );
        $data = array(
            'fname' => $profile->fname,
            'mname' => $profile->mname,
            'lname' => $profile->lname,
            'dob' => $profile->dob,
            'sex' => $profile->sex,
            'civil_status' => ($civil_status) ? $civil_status: 'N/A',
            'phic_id' => ($phic_id) ? $phic_id: 'N/A',
            'phic_status' => ($phic_status) ? $phic_status: 'N/A',
            'brgy' => $profile->barangay_id,
            'muncity' => $profile->muncity_id,
            'province' => $profile->province_id,
            'tsekap_patient' => 1
        );
        $patient = Patients::updateOrCreate($match,$data);
        return $patient->id;
    }

    function accepted(Request $request)
    {
        $user = Session::get('auth');
        $keyword = Session::get('keywordAccepted');
        $start = Session::get('startAcceptedDate');
        $end = Session::get('endAcceptedDate');

        if($start && $end){
            $start = Carbon::parse($start)->startOfDay();
            $end = Carbon::parse($end)->endOfDay();
        } else {
            $start = \Carbon\Carbon::now()->startOfYear();
            $end = \Carbon\Carbon::now()->endOfYear();
        }


        $data = \DB::connection('mysql')->select("call AcceptedFunc('$user->facility_id','$start','$end','$keyword')");
        $patient_count = count($data);
        $data = $this->MyPagination($data,15,$request);

        return view('doctor.accepted',[
            'title' => 'Accepted Patients',
            'data' => $data,
            'start' => $start,
            'end' => $end,
            'patient_count' => $patient_count
        ]);
    }

    public function MyPagination($list,$perPage,Request $request)
    {
        // Get current page form url e.x. &page=1
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        // Create a new Laravel collection from the array data
        $itemCollection = collect($list);

        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);

        // set url path for generted links
        $paginatedItems->setPath($request->url());

        return $paginatedItems;
    }

    function AcceptedJimmy()
    {
        $user = Session::get('auth');
        $keyword = Session::get('keywordAccepted');
        $start = Session::get('startAcceptedDate');
        $end = Session::get('endAcceptedDate');

        $data = Tracking::select(
            'tracking.id',
            'tracking.type',
            'tracking.code',
            'facility.name',
            DB::raw('CONCAT(patients.fname," ",patients.mname," ",patients.lname) as patient_name'),
            DB::raw("DATE_FORMAT(tracking.date_accepted,'%M %d, %Y %h:%i %p') as date_accepted")
        )
            ->join('facility','facility.id','=','tracking.referred_from')
            ->join('patients','patients.id','=','tracking.patient_id')
            ->where('referred_to',$user->facility_id)
            ->where(function($q){
                $q->where('tracking.status','accepted')
                    ->orwhere('tracking.status','admitted')
                    ->orwhere('tracking.status','arrived');
            });
        if($keyword){
            $data = $data->where(function($q) use ($keyword){
                $q->where('patients.fname','like',"%$keyword%")
                    ->orwhere('patients.mname','like',"%$keyword%")
                    ->orwhere('patients.lname','like',"%$keyword%")
                    ->orwhere('tracking.code','like',"%$keyword%");
            });
        }

        if($start && $end){
            $start = Carbon::parse($start)->startOfDay();
            $end = Carbon::parse($end)->endOfDay();
            $data = $data->whereBetween('tracking.date_accepted',[$start,$end]);
        }

        $data = $data->orderBy('tracking.date_accepted','desc')
            ->paginate(15);

        return view('doctor.accepted',[
            'title' => 'Accepted Patients',
            'data' => $data
        ]);
    }

    public function searchAccepted(Request $req)
    {
        $range = explode('-',str_replace(' ', '', $req->daterange));

        $start = $range[0];
        $end = $range[1];

        Session::put('startAcceptedDate',$start);
        Session::put('endAcceptedDate',$end);
        Session::put('keywordAccepted',$req->keyword);

        return redirect('/doctor/accepted');
    }

    function discharge(Request $request)
    {
        $keyword = Session::get('keywordDischarged');
        $start = Session::get('startDischargedDate');
        $end = Session::get('endDischargedDate');
        $user = Session::get('auth');
        $id = $user->id;

        $data1 = Tracking::select(
            'tracking.id',
            'tracking.type',
            'tracking.code',
            'facility.name',
            'tracking.status',
            DB::raw('CONCAT(patients.fname," ",patients.mname," ",patients.lname) as patient_name'),
            DB::raw("DATE_FORMAT(tracking.date_accepted,'%M %d, %Y %h:%i %p') as date_accepted")
        )
            ->join('facility','facility.id','=','tracking.referred_from')
            ->join('patients','patients.id','=','tracking.patient_id')
            ->join('patient_form','patient_form.patient_id','=','tracking.patient_id')
            ->where('patient_form.referred_md',$user->id)
            ->whereExists(function($query) use($id)
            {
                $query->select(DB::raw(1))
                ->from('affiliated')
                    ->whereRaw('affiliated.facility_id = facility.id')
                    ->where('affiliated.user_id',$id);
            }) 
            ->where(function($q){
                $q->where('tracking.status','discharged')
                            ->orwhere('tracking.status','transferred');
            });
        if($keyword){
            $data1 = $data1->where(function($q) use ($keyword){
                $q->where('patients.fname','like',"%$keyword%")
                    ->orwhere('patients.mname','like',"%$keyword%")
                    ->orwhere('patients.lname','like',"%$keyword%")
                    ->orwhere('tracking.code','like',"%$keyword%");
            });
        }
        if($start && $end){
            $start = Carbon::parse($start)->startOfDay();
            $end = Carbon::parse($end)->endOfDay();
            $data1 = $data1->whereBetween('tracking.date_accepted',[$start,$end]);
        }
        else {
            $start = \Carbon\Carbon::now()->startOfYear();
            $end = \Carbon\Carbon::now()->endOfYear();
            $data1 = $data1->whereBetween('tracking.date_accepted',[$start,$end]);
        }


        
        $data = Tracking::select(
                    'tracking.id',
                    'tracking.type',
                    'tracking.code',
                    'facility.name',
                    'tracking.status',
                    DB::raw('CONCAT(patients.fname," ",patients.mname," ",patients.lname) as patient_name'),
                    DB::raw("DATE_FORMAT(tracking.updated_at,'%M %d, %Y %h:%i %p') as date_accepted")
                )
                ->join('facility','facility.id','=','tracking.referred_from')
                ->join('patients','patients.id','=','tracking.patient_id')
                ->where('tracking.referred_to',$user->facility_id);

        if($keyword){
            $data = $data->where(function($q) use ($keyword){
                $q->where('patients.fname','like',"%$keyword%")
                    ->orwhere('patients.mname','like',"%$keyword%")
                    ->orwhere('patients.lname','like',"%$keyword%")
                    ->orwhere('tracking.code','like',"%$keyword%");
            });
        }

        if($start && $end){
            $start = Carbon::parse($start)->startOfDay();
            $end = Carbon::parse($end)->endOfDay();
            $data = $data
                    ->leftJoin('activity','activity.code','=','tracking.code')
                    ->where(function ($q) {
                        $q->where('activity.status','discharged')
                            ->orwhere('activity.status','transferred');
                    })
                    ->whereBetween('activity.date_referred',[$start,$end]);
        }else{
            $data = $data->where(function($q){
                        $q->where('tracking.status','discharged')
                            ->orwhere('tracking.status','transferred');
                    });
        }

        $page = Input::get('page', 1);
        $paginate = 5;
        
        $data = $data->union($data1)->get();

        $data = $this->MyPagination($data,10,$request);
        

        print_r(count($data));
        return view('doctor.discharge',[
            'title' => 'Discharged/Transferred Patients',
            'data' => $data
        ]);
    }

    public function searchDischarged(Request $req)
    {
        $range = explode('-',str_replace(' ', '', $req->daterange));
        $tmp1 = explode('/',$range[0]);
        $tmp2 = explode('/',$range[1]);

        $start = $tmp1[2].'-'.$tmp1[0].'-'.$tmp1[1];
        $end = $tmp2[2].'-'.$tmp2[0].'-'.$tmp2[1];

        Session::put('startDischargedDate',$start);
        Session::put('endDischargedDate',$end);
        Session::put('keywordDischarged',$req->keyword);

        return redirect('/doctor/discharge');
    }

    function cancel()
    {
        $user = Session::get('auth');
        $keyword = Session::get('keywordCancelled');
        $start = Session::get('startCancelledDate');
        $end = Session::get('endCancelledDate');

        $data = Tracking::select(
            'tracking.id',
            'tracking.type',
            'tracking.code',
            'facility.name',
            DB::raw('CONCAT(patients.fname," ",patients.mname," ",patients.lname) as patient_name'),
            DB::raw("DATE_FORMAT(tracking.updated_at,'%M %d, %Y %h:%i %p') as date_accepted")
        )
            ->join('facility','facility.id','=','tracking.referred_from')
            ->join('patients','patients.id','=','tracking.patient_id')
            ->where('referred_to',$user->facility_id)
            ->where('tracking.status','cancelled');

        if($keyword){
            $data = $data->where(function($q) use ($keyword){
                $q->where('patients.fname','like',"%$keyword%")
                    ->orwhere('patients.mname','like',"%$keyword%")
                    ->orwhere('patients.lname','like',"%$keyword%")
                    ->orwhere('tracking.code','like',"%$keyword%");
            });
        }

        if($start && $end){
            $start = Carbon::parse($start)->startOfDay();
            $end = Carbon::parse($end)->endOfDay();
            $data = $data->whereBetween('tracking.updated_at',[$start,$end]);
        }

        $data = $data->orderBy('date_referred','asc')
            ->paginate(15);

        return view('doctor.cancel',[
            'title' => 'Cancelled Patients',
            'data' => $data
        ]);
    }

    public function searchCancelled(Request $req)
    {
        $range = explode('-',str_replace(' ', '', $req->daterange));
        $tmp1 = explode('/',$range[0]);
        $tmp2 = explode('/',$range[1]);

        $start = $tmp1[2].'-'.$tmp1[0].'-'.$tmp1[1];
        $end = $tmp2[2].'-'.$tmp2[0].'-'.$tmp2[1];

        Session::put('startCancelledDate',$start);
        Session::put('endCancelledDate',$end);
        Session::put('keywordCancelled',$req->keyword);

        return redirect('/doctor/cancelled');
    }

    function archived()
    {
        $user = Session::get('auth');
        $keyword = Session::get('keywordArchived');
        $start = Session::get('startArchivedDate');
        $end = Session::get('endArchivedDate');

        $data = Tracking::select(
            'tracking.id',
            'tracking.type',
            'tracking.code',
            'facility.name',
            DB::raw('CONCAT(patients.fname," ",patients.mname," ",patients.lname) as patient_name'),
            DB::raw("DATE_FORMAT(tracking.updated_at,'%M %d, %Y %h:%i %p') as date_accepted")
        )
            ->join('facility','facility.id','=','tracking.referred_from')
            ->join('patients','patients.id','=','tracking.patient_id')
            ->where('referred_to',$user->facility_id)
            ->where(function($q){
                $q->where('tracking.status','referred')
                    ->orwhere('tracking.status','seen');
            })
            ->where(DB::raw("TIMESTAMPDIFF(MINUTE,tracking.date_referred,now())"),">",4320);

        if($keyword){
            $data = $data->where(function($q) use ($keyword){
                $q->where('patients.fname','like',"%$keyword%")
                    ->orwhere('patients.mname','like',"%$keyword%")
                    ->orwhere('patients.lname','like',"%$keyword%")
                    ->orwhere('tracking.code','like',"%$keyword%");
            });
        }

        if($start && $end){
            $start = Carbon::parse($start)->startOfDay();
            $end = Carbon::parse($end)->endOfDay();
            $data = $data->whereBetween('tracking.updated_at',[$start,$end]);
        }
        $data = $data->orderBy('date_referred','desc')
                     ->paginate(15);

        return view('doctor.archive',[
            'title' => 'Archived Patients',
            'data' => $data
        ]);
    }

    public function searchArchived(Request $req)
    {
        $range = explode('-',str_replace(' ', '', $req->daterange));
        $tmp1 = explode('/',$range[0]);
        $tmp2 = explode('/',$range[1]);

        $start = $tmp1[2].'-'.$tmp1[0].'-'.$tmp1[1];
        $end = $tmp2[2].'-'.$tmp2[0].'-'.$tmp2[1];

        Session::put('startArchivedDate',$start);
        Session::put('endArchivedDate',$end);
        Session::put('keywordArchived',$req->keyword);

        return redirect('/doctor/archived');
    }

    static function getCancellationReason($status, $code)
    {
        $act = Activity::where('code',$code)
                    ->where('status',$status)
                    ->first();
        if($act)
            return $act->remarks;
        return 'No Reason';
    }

    static function getDischargeDate($status, $code)
    {
        $date = Activity::where('code',$code)
                    ->where('status',$status)
                    ->first();
        if($date)
            $date = $date->date_referred;
        else
            return false;

        return date('F d, Y h:i A',strtotime($date));
    }

    public function history($code)
    {
        Session::put('keywordDischarged',$code);
        return redirect('doctor/referred');
    }

    public function walkinPatient(Request $request){
        $user = Session::get('auth');
        if(isset($request->date_range)){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[0])).' 00:00:00';
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[1])).' 23:59:59';
        } else {
            $date_start = Carbon::now()->startOfYear()->format('Y-m-d').' 00:00:00';
            $date_end = Carbon::now()->endOfMonth()->format('Y-m-d').' 23:59:59';
        }

        $walkin_patient = \DB::connection('mysql')->select("call walkin('$date_start','$date_end','$user->facility_id')");
        return view('doctor.walkin',[
            "walkin_patient" => $walkin_patient,
            "user_level" => $user->level,
            "date_start" => $date_start,
            "date_end" => $date_end
        ]);
    }

}
