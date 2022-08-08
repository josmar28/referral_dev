<?php

namespace App\Http\Controllers\doctor;

use App\Affiliated;
use App\Facility;
use App\User;
use App\Activity;
use App\Feedback;
use App\Http\Controllers\DeviceTokenCtrl;
use App\Http\Controllers\ParamCtrl;
use App\Issue;
use App\Monitoring;
use App\Baby;
use App\PatientForm;
use Illuminate\Support\Facades\Redirect;
use App\Patients;
use App\PregnantForm;
use App\Pregnancy;
use App\Seen;
use App\Tracking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;

class AffiliatedCtrl extends Controller
{
    
    public function index(Request $req)
    {
        $user = Session::get('auth');
        $keyword = $req->search;

        $data = Affiliated::select('*','facility.*','affiliated.id as main_id')
              ->leftJoin('facility','facility.id','=','affiliated.facility_id')
              ->where(function($q) use($keyword){     
                $q->where('facility.name',"like","%$keyword%")
                    ->orWhere('facility.chief_hospital','like',"%$keyword%");
                 })
                ->where('user_id',$user->id)
                ->paginate(1);

        return view('doctor.affiliated',[
            'data' => $data
        ]);
    }

    public function AfiiliatedBody(Request $req)
    {
        $user = Session::get('auth');
        $keyword = $req->keyword;
        $facility = Facility::select('facility.*')
                            ->where('id','<>',$user->facility_id)
                            ->where('facility.status',1)
                            ->where(function($q) use($keyword){     
                                $q->where('facility.name',"like","%$keyword%")
                                    ->orWhere('facility.chief_hospital','like',"%$keyword%");
                                 })
                            ->whereNotExists(function($query)
                            {
                                $query->select(DB::raw(1))
                                    ->from('affiliated')
                                    ->whereRaw('affiliated.facility_id = facility.id');
                            })  
                            ->paginate(1);


        return view('doctor.affiliated_body',[
            'facility' => $facility
        ]);
    }

    public function AffiliatedOptions(Request $req)
    {

        if($req->id)
        {
            Affiliated::find($req->id)->delete();

            Session::put('affi_delete',true);
        }else
        {
            $user = Session::get('auth');

            foreach($req->facility_checkbox as $fac)
            {
                $data = array(
                    'user_id' => $user->id,
                    'facility_id' => $fac,
                    'status' => 1
                );

                Affiliated::create($data);
            }
            Session::put('affi_add',true);

            return Redirect::back();
         }
    }

    public function AffiReferral(Request $request)
    {
        ParamCtrl::lastLogin();
        $keyword = '';
        $dept = '';
        $fac = '';
        $option = '';
      
        $user = Session::get('auth');
        print_r($user->facility_id);

        $start = Carbon::now()->startOfYear()->format('m/d/Y');
        $end = Carbon::now()->endOfYear()->format('m/d/Y');

        $start_date = Carbon::parse($start)->startOfDay();
        $end_date = Carbon::parse($end)->endOfDay();
        if($request->facility_filter)
        {
            $fac = $request->facility_filter;
        

             $data = Tracking::select(
            'tracking.*', 
            DB::raw('CONCAT(patients.fname," ",patients.mname," ",patients.lname) as patient_name'),
            DB::raw("TIMESTAMPDIFF(YEAR, patients.dob, CURDATE()) AS age"),
            'patients.sex',
            'facility.name as facility_name',
            DB::raw('CONCAT(
                if(users.level="doctor","Dr. ",""),
            users.fname," ",users.mname," ",users.lname) as referring_md'),
            DB::raw('CONCAT(action.fname," ",action.mname," ",action.lname) as action_md')
            )
            ->join('patients','patients.id','=','tracking.patient_id')
            ->join('patient_form','patient_form.patient_id','=','tracking.patient_id')
            ->join('facility','facility.id','=','tracking.referred_from')
            ->leftJoin('users','users.id','=','tracking.referring_md')
            ->leftJoin('users as action','action.id','=','tracking.action_md')
            ->where('patient_form.referred_md',$user->id)
            ->where('tracking.referred_to',$fac);
        
        if($request->search)
        {
            $keyword = $request->search;
            $data = $data->where(function($q) use ($keyword){
                $q->where('patients.lname',"$keyword")
                    ->orwhere('patients.fname',"$keyword")
                    ->orwhere('tracking.code',"$keyword");
            });
        }

        if($request->department_filter)
        {
            $dept = $request->department_filter;
            $data = $data->where('tracking.department_id',$dept);
        }

     

        if($request->date_range)
        {
            $date = $request->date_range;
            $range = explode('-',str_replace(' ', '', $date));
            $start = $range[0];
            $end = $range[1];
        }else
        {
        $start = Carbon::now()->startOfYear()->format('m/d/Y');
        $end = Carbon::now()->endOfYear()->format('m/d/Y');
        }

        $start_date = Carbon::parse($start)->startOfDay();
        $end_date = Carbon::parse($end)->endOfDay();

        if($request->option_filter)
        {
            $option = $request->option_filter;
            if($option=='referred'){
                $data = $data->where(function($q){
                    $q->where('tracking.status','referred')
                        ->orwhere('tracking.status','seen');
                });
            }elseif($option=='accepted'){
                $data = $data->where(function($q){
                    $q->where('tracking.status','accepted');
                });
            }
        }else{
            $data = $data->where(function($q){
                $q->where('tracking.status','referred')
                    ->orwhere('tracking.status','seen')
                    ->orwhere('tracking.status','accepted')
                    ->orwhere('tracking.status','redirected')
                    ->orwhere('tracking.status','rejected')
                    ->orwhere('tracking.status','arrived')
                    ->orwhere('tracking.status','admitted')
                    ->orwhere('tracking.status','discharged')
                    ->orwhere('tracking.status','transferred')
                    ->orwhere('tracking.status','archived')
                    ->orwhere('tracking.status','cancelled');
            });
        }
        $data = $data->whereBetween('tracking.date_referred',[$start_date,$end_date]);

        $data = $data
                //->orderBy(DB::raw("IF( ((tracking.status='referred' or tracking.status='seen' or tracking.status = 'transferred') && tracking.department_id = '$user->department_id' ), now(), tracking.date_referred )"),"desc")
                ->orderBy("tracking.date_referred","desc")
                ->paginate(15);              

        }

        return view('doctor.affi_referral',[
            'title' => 'Affiliated Incoming Patients',
            'data' => $data,
            'start' => $start,
            'end' => $end,
            'keyword' => $keyword,
            'department' => $dept,
            'facility' => $fac,
            'option' => $option
        ]);

    }

    public function AffiPatient(Request $request)
    {
        $user = Session::get('auth');
        $id = $user->id;
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
        else {
            $start = \Carbon\Carbon::now()->startOfYear();
            $end = \Carbon\Carbon::now()->endOfYear();
            $data = $data->whereBetween('tracking.date_accepted',[$start,$end]);
        }

        $data = $data->orderBy('tracking.date_accepted','desc')
            ->paginate(15);

        return view('doctor.affi_accepted',[
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


}
