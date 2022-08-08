<?php

namespace App\Http\Controllers\Monitoring;

use App\Issue;
use App\Monitoring;
use App\OfflineFacilityRemark;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Incident;
use App\Facility;
use App\Tracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class MonitoringCtrl extends Controller
{
    public function monitoring(Request $request){

        $fac_id = Session::get('auth')->facility_id;
        if(isset($request->date_range)){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[0])).' 00:00:00';
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[1])).' 23:59:59';
        } else {
            $date_start = date('Y-m-d',strtotime(Carbon::now()->subDays(3))).' 00:00:00';
            $date_end = date('Y-m-d').' 23:59:59';
        }

        $pending_activity = \DB::connection('mysql')->select("call monitoring('$date_start','$date_end','$fac_id')");
        return view('monitoring.monitoring',[
            "pending_activity" => $pending_activity,
            "date_start" => $date_start,
            "date_end" => $date_end
        ]);
    }

    public function bodyRemark(Request $request){
        return view('monitoring.monitoring_remark',[
            "activity_id" => $request->activity_id,
            "code" => $request->code,
            "remark_by" => $request->remark_by,
            "referring_facility" => $request->referring_facility,
            "referred_to" => $request->referred_to
        ]);
    }

    public function offlineRemarkBody(Request $request){
        return view('admin.report.offline_remark_body',[
            "facility_id" => $request->facility_id
        ]);
    }

    public function offlineRemarkAdd(Request $request){
        $facility_id = $request->facility_id;
        $remark_by = Session::get('auth')->id;
        $remarks = $request->remarks;
        $offline_facility_remarks = new OfflineFacilityRemark();
        $offline_facility_remarks->facility_id = $facility_id;
        $offline_facility_remarks->remark_by = $remark_by;
        $offline_facility_remarks->remarks = $remarks;
        $offline_facility_remarks->save();

        Session::put("add_offline_remark",true);
        return Redirect::back();
    }

    public function addRemark(Request $request){
        $monitoring_not_accepted = new Monitoring();
        $monitoring_not_accepted->code = $request->code;
        $monitoring_not_accepted->remark_by = Session::get('auth')->id;
        $monitoring_not_accepted->activity_id = $request->activity_id;
        $monitoring_not_accepted->referring_facility = $request->referring_facility;
        $monitoring_not_accepted->referred_to = $request->referred_to;
        $monitoring_not_accepted->remarks = $request->remarks;
        $monitoring_not_accepted->save();

        Session::put("add_remark",true);
        return Redirect::back();
    }

    public function feedbackDOH($code){
        $data = Monitoring::where("code","=",$code)->orderBy("id","asc")->get();;

        return view('doctor.feedback_monitoring',[
            'data' => $data
        ]);
    }

    public function getIssue(Request $request){
        if(isset($request->date_range)){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[0])).' 00:00:00';
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[1])).' 23:59:59';
        } else {
            $date_start = Carbon::now()->startOfYear()->format('Y-m-d').' 00:00:00';
            $date_end = date('Y-m-d').' 23:59:59';
        }

        $issue = \DB::connection('mysql')->select("call issue('$date_start','$date_end')");
        return view('doctor.issue',[
            "issue" => $issue,
            "date_start" => $date_start,
            "date_end" => $date_end
        ]);
    }

    public function IssueAndConcern($tracking_id,$referred_from)
    {
        $facility = Facility::find($referred_from);
        $data = Issue::where("tracking_id","=",$tracking_id)->orderBy("id","asc")->get();
        return view('issue.issue',[
            'data' => $data,
            'facility' => $facility
        ]);
    }

    public function issueSubmit(Request $request)
    {
        $issue = $request->get('issue');
        $tracking_id = $request->get('tracking_id');
        $data  = array(
            "tracking_id" => $tracking_id,
            "issue" => $issue,
            "status" => 'outgoing'
        );
        Issue::create($data);

        $facility = Facility::find(Session::get("auth")->facility_id);
        return view("issue.issue_append",[
            "facility_name" => $facility->name,
            "facility_picture" => $facility->picture,
            "tracking_id" => $tracking_id,
            "issue" => $issue
        ]);
    }

    public function IncidentLog($track_id)
    {
        $fac_id = Session::get('auth')->facility_id;
        $incident = \DB::connection('mysql')->select("call incident_log_chd12('$fac_id','$track_id')");

        $reported = count($incident);
       
        return $reported;
    }

    public function incidentIndex(Request $req)
    {
        
        $user = Session::get('auth');

        $data = Incident::select(
            'incident.*', 
            DB::raw('CONCAT(patients.fname," ",patients.mname," ",patients.lname) as patient_name'),
            DB::raw("TIMESTAMPDIFF(YEAR, patients.dob, CURDATE()) AS age"),
            'patients.sex',
            'patient_form.patient_id',
            DB::raw('CONCAT(encoded_by.fname," ",encoded_by.mname," ",encoded_by.lname) as encoded_by_name'),
            'incident_type.type as inci_type',
            'fr.name as referred_from',
            'to.name as referred_to',
            'fr.id as from_id',
            'to.id as to_id'
            
        )
        ->leftJoin('facility as fr','fr.id','=','incident.referred_from')
        ->leftJoin('facility as to','to.id','=','incident.referred_to')
        ->leftJoin('patient_form','patient_form.code','=','incident.patient_code')
        ->leftJoin('patients','patients.id','=','patient_form.patient_id')
        ->leftJoin('incident_type','incident_type.id','=','incident.type_id')
        ->leftJoin('users as encoded_by','encoded_by.id','=','incident.encoded_by')
        ->Where('incident.referred_from',$user->facility_id)
        ->orWhere('incident.referred_to',$user->facility_id)
        ->orderby('id','asc');
      
        if($req->date_range){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$req->date_range)[0])).' 00:00:00';
            $date_end = date('Y-m-d',strtotime(explode(' - ',$req->date_range)[1])).' 23:59:59';

        } 
        else{
            $date_start = Carbon::now()->startOfYear()->format('Y-m-d').' 00:00:00';
            $date_end = Carbon::now()->endOfMonth()->format('Y-m-d').' 23:59:59';
        }
      

        $data = $data->whereBetween('incident.created_at',[$date_start,$date_end]);

        $data = $data
        //->orderBy(DB::raw("IF( ((tracking.status='referred' or tracking.status='seen' or tracking.status = 'transferred') && tracking.department_id = '$user->department_id' ), now(), tracking.date_referred )"),"desc")
        ->orderBy("incident.created_at","asc")
        ->paginate(15);

        return view('doctor.allincident',[
            'data' => $data,
            'date_end' => $date_end,
            'date_start' => $date_start

        ]);
    }

    public function recotoRed(Request $request)
    {
        // $ = Carbon::now()->startOfYear()->format('Y-m-d').' 00:00:00';
        // $ = Carbon::now()->endOfMonth()->format('Y-m-d').' 23:59:59';

        if($request->date_range)
        {
            $date = $request->date_range;
            $range = explode('-',str_replace(' ', '', $date));
            $start = $range[0];
            $end = $range[1];


            $date_start = Carbon::parse($start)->startOfDay();
            $date_end = Carbon::parse($end)->endOfDay();
        }else
        {
        $date_start = Carbon::now()->startOfYear()->format('m/d/Y');
        $date_end = Carbon::now()->endOfYear()->format('m/d/Y');
        }

    
        $fac_id = Session::get('auth')->facility_id;
        $data = \DB::connection('mysql')->select("call recotored('$fac_id','$date_start','$date_end')");

       return view('doctor.recotored',[
           'data' => $data,
            'date_start' => $date_start,
            'date_end' => $date_end
       ]);
    }

}
