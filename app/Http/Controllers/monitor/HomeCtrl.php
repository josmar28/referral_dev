<?php

namespace App\Http\Controllers\monitor;

use App\Activity;
use App\Facility;
use App\Http\Controllers\ParamCtrl;
use App\Tracking;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class HomeCtrl extends Controller
{
    public function index(Request $req){
        ParamCtrl::lastLogin();
        $user = Session::get("auth");
        if(empty($req->facility_filter))
        {
            $facility_id = $user->facility_id;
        }
        else{
            $facility_id = $req->facility_filter;
        }
   
        $group_by_department = $user->level == 'admin' ?
            User::
            select(DB::raw("count(users.id) as y"),DB::raw("coalesce(department.description,'NO DEPARTMENT') as label"))
                ->leftJoin("department","department.id","=","users.department_id")
                ->where("users.level","doctor")
                ->groupBy("users.department_id")
                ->get()
        :
            User::
            select(DB::raw("count(users.id) as y"),DB::raw("coalesce(department.description,'NO DEPARTMENT') as label"))
                ->leftJoin("department","department.id","=","users.department_id")
                ->where("users.facility_id",$facility_id)
                ->where("users.level","doctor")
                ->groupBy("users.department_id")
                ->get()
        ;

        $date_start = Carbon::now()->startOfYear()->format('Y-m-d').' 00:00:00';
        $date_end = Carbon::now()->endOfYear()->format('Y-m-d').' 23:59:59';

        $incoming_statistics = \DB::connection('mysql')->select("call statistics_report_facility('$date_start','$date_end','$facility_id','$user->level')")[0];
        //return json_encode($incoming_statistics);

        $referred_query = DB::connection('mysql')->select("call doctor_monthly_report('$date_start','$date_end','$facility_id','referred','$user->level')");
        $accepted_query = DB::connection('mysql')->select("call doctor_monthly_report('$date_start','$date_end','$facility_id','accepted','$user->level')");
        $redirected_query = DB::connection('mysql')->select("call doctor_monthly_report('$date_start','$date_end','$facility_id','redirected','$user->level')");


        //for past 15 days
        $date_start_past = date('Y-m-d',strtotime(Carbon::now()->subDays(15))).' 00:00:00';
        $date_end_past = date('Y-m-d',strtotime(Carbon::now()->subDays(1))).' 23:59:59';
        $referred_past = DB::connection('mysql')->select("call doctor_past_transaction('$date_start_past','$date_end_past','$facility_id','referred','$user->level')");
        $accepted_past = DB::connection('mysql')->select("call doctor_past_transaction('$date_start_past','$date_end_past','$facility_id','accepted','$user->level')");
        $redirected_past = DB::connection('mysql')->select("call doctor_past_transaction('$date_start_past','$date_end_past','$facility_id','redirected','$user->level')");

        $index = 0;
        foreach($referred_query as $row){
            $data['referred'][] = $row->value;
            $data['accepted'][] = $accepted_query[$index]->value;
            $data['redirected'][] = $redirected_query[$index]->value;
            $index++;
        }

        return view('monitor.home',[
            "group_by_department" => $group_by_department,
            "facility_filter" => $facility_id,
            "doctor_monthly_report" => $data,
            "referred_past" => $referred_past,
            "accepted_past" => $accepted_past,
            "redirected_past" => $redirected_past,
            "incoming_statistics" => $incoming_statistics,
            "date_start" => $date_start,
            "date_end" => Carbon::now()->format('Y-m-d')
        ]);
    }   

    public function consolidated(Request $req)
    {
        $user = Session::get("auth");
        if(empty($req->facility_filter))
        {
            $facility_id = $user->facility_id;
        }
        else{
            $facility_id = $req->facility_filter;
        }

        if(isset($req->date_range)){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$req->date_range)[0])).' 00:00:00';
            $date_end = date('Y-m-d',strtotime(explode(' - ',$req->date_range)[1])).' 23:59:59';
        } else {
            $date_start = Carbon::now()->startOfYear()->format('Y-m-d').' 00:00:00';
            $date_end = Carbon::now()->endOfMonth()->format('Y-m-d').' 23:59:59';
        }

        
        //Session::get("auth")->level == "mcc" ? $stored_name = "consolidatedIncomingMcc('$date_start','$date_end','$facility_id')" : $stored_name = "consolidatedIncoming('$date_start','$date_end')";
        $stored_name = "consolidatedIncomingMcc('$date_start','$date_end','$facility_id')";
        $incomingData = \DB::connection('mysql')->select("call $stored_name");

        Session::put('data',$incomingData);
        return view('monitor.consolidated',
        [
            'title' => 'REFERRAL CONSOLIDATION TABLE',
            'data' => $incomingData,
            'facility_filter' => $facility_id,
            'date_range_start' => $date_start,
            'date_range_end' => $date_end
        ]);
    }

    public function statisticsReportIncoming(Request $request){

        if(empty($request->facility_filter))
        {
            $facility_id = 0;
        }
        else{
            $facility_id = $request->facility_filter;
        }

        if($request->isMethod('post') && isset($request->date_range)){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[0])).' 00:00:00';
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[1])).' 23:59:59';
        } else {
            $date_start = Carbon::now()->startOfYear()->format('Y-m-d').' 00:00:00';
            $date_end = Carbon::now()->endOfMonth()->format('Y-m-d').' 23:59:59';
        }

        $stored_name = "monitoring_statistics_incoming('$date_start','$date_end','$facility_id')";
        $data = \DB::connection('mysql')->select("call $stored_name");

        return view('monitor.statistics_incoming',[
            'title' => 'STATISTICS REPORT INCOMING',
            "data" => $data,
            'facility_filter' => $facility_id,
            'date_range_start' => $date_start,
            'date_range_end' => $date_end
        ]);
    }

    public function statisticsReportOutgoing(Request $request){
        if(empty($request->facility_filter))
        {
            $facility_id = 0;
        }
        else{
            $facility_id = $request->facility_filter;
        }

        if($request->isMethod('post') && isset($request->date_range)){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[0])).' 00:00:00';
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[1])).' 23:59:59';
        } else {
            $date_start = Carbon::now()->startOfYear()->format('Y-m-d').' 00:00:00';
            $date_end = Carbon::now()->endOfMonth()->format('Y-m-d').' 23:59:59';
        }

        $stored_name = "monitoring_statistics_outgoing('$date_start','$date_end','$facility_id')";
        $data = \DB::connection('mysql')->select("call $stored_name");

        return view('monitor.statistics_outgoing',[
            'title' => 'STATISTICS REPORT OUTGOING',
            "data" => $data,
            'facility_filter' => $facility_id,
            'date_range_start' => $date_start,
            'date_range_end' => $date_end
        ]);
    }

    public function patientTrans(Request $request)
    {
        ParamCtrl::lastLogin();
        $search = $request->search;
        $option_filter = $request->option_filter;
        $date = $request->date;
        $facility_filter = $request->facility_filter;
        $department_filter = $request->department_filter;

        $start = Carbon::now()->startOfYear()->format('m/d/Y');
        $end = Carbon::now()->endOfYear()->format('m/d/Y');

        if($request->referredCode){
            ParamCtrl::lastLogin();
            $data = Tracking::select(
                'tracking.*',
                DB::raw('CONCAT(patients.fname," ",patients.mname," ",patients.lname) as patient_name'),
                DB::raw("TIMESTAMPDIFF(YEAR, patients.dob, CURDATE()) AS age"),
                DB::raw('CONCAT(users.fname," ",users.mname," ",users.lname) as referring_md'),
                'patients.sex',
                'facility.name as facility_name',
                'facility.id as facility_id',
                'patients.id as patient_id',
                'patients.contact',
                'users.level as user_level'
            )
                ->join('patients','patients.id','=','tracking.patient_id')
                ->join('facility','facility.id','=','tracking.referred_to')
                ->leftJoin('users','users.id','=','tracking.referring_md')
                ->where('tracking.code',$request->referredCode)
                ->orderBy('date_referred','desc')
                ->paginate(10);

        } else {
            $user = Session::get('auth');
            $data = Tracking::select(
                'tracking.*',
                DB::raw('CONCAT(patients.fname," ",patients.mname," ",patients.lname) as patient_name'),
                DB::raw("TIMESTAMPDIFF(YEAR, patients.dob, CURDATE()) AS age"),
                DB::raw('COALESCE(CONCAT(users.fname," ",users.mname," ",users.lname),"WALK IN") as referring_md'),
                'patients.sex',
                'facility.name as facility_name',
                'facility.id as facility_id',
                'patients.id as patient_id',
                'patients.contact',
                'users.level as user_level'
            )
                ->join('patients','patients.id','=','tracking.patient_id')
                ->join('facility','facility.id','=','tracking.referred_to')
                ->leftJoin('users','users.id','=','tracking.referring_md')
                ->where('facility.province',$user->province)
                ->where(function($q){
                    $q->where('tracking.status','referred')
                        ->orwhere('tracking.status','seen')
                        ->orwhere('tracking.status','accepted')
                        ->orwhere('tracking.status','arrived')
                        ->orwhere('tracking.status','admitted')
                        ->orwhere('tracking.status','transferred')
                        ->orwhere('tracking.status','discharged')
                        ->orwhere('tracking.status','cancelled')
                        ->orwhere('tracking.status','archived')
                        ->orwhere('tracking.status','rejected');
                });
            if($search){
                $data = $data->where(function($q) use ($search){
                    $q->where('patients.fname','like',"%$search%")
                        ->orwhere('patients.mname','like',"%$search%")
                        ->orwhere('patients.lname','like',"%$search%")
                        ->orwhere('tracking.code','like',"%$search%");
                });
            }

            if($option_filter){
                $data = $data->where('tracking.status',$option_filter);
            }
            if($facility_filter)
            {
                $data = $data->where('tracking.referred_to',$facility_filter);
            }
            if($department_filter)
            {
                $data = $data->where('tracking.department_id',$department_filter);
            }

            if($date)
            {
                $range = explode('-',str_replace(' ', '', $date));
                $start = $range[0];
                $end = $range[1];
            }

            $start_date = Carbon::parse($start)->startOfDay();
            $end_date = Carbon::parse($end)->endOfDay();

            $data = $data->whereBetween('tracking.date_referred',[$start_date,$end_date]);

            $data = $data->orderBy('date_referred','desc')
                ->paginate(10);
        }

        return view('monitor.patient_trans',[
            'title' => 'Referred Patients',
            'data' => $data,
            'start' => $start,
            'end' => $end,
            'referredCode' => $request->referredCode,
            'search' => $search,
            'option_fitler' => $option_filter,
            'facility_filter' => $facility_filter,
            'department_filter' => $department_filter
        ]);
    }

    public function loginStatus(Request $request) {
        $user = Session::get('auth');
    	if($request->isMethod('post') && isset($request->date_range)){
            $date_start = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[0]));
            $date_end = date('Y-m-d',strtotime(explode(' - ',$request->date_range)[1]));
        } else {
            $date_start = date('Y-m-d',strtotime(Carbon::now()->subDays(31)));
            $date_end = date('Y-m-d');
        }

        $facility = \DB::connection('mysql')->select("call weekly_report_perfac('$user->province')");
        $generate_weeks = \DB::connection('mysql')->select("call generate_weeks('$date_start','$date_end')");

        return view('monitor.offline_facility_weekly',[
            'title' => 'Login Status',
            'facility' => $facility,
            'generate_weeks' => $generate_weeks,
            'date_start' => $date_start,
            'date_end' => $date_end
        ]);
    }
    public function referral()
    {
        $start = Session::get('startDateReportReferral');
        $end = Session::get('endDateReportReferral');
        $facility_filter = Session::get('referralstatus_facility');
        if(!$start)
            $start = date('Y-m-d');
        if(!$end)
            $end = date('Y-m-d');

        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();

        $data = Tracking::whereBetween('updated_at',[$start,$end])
            ->orderBy('updated_at','desc')
            ->where('referred_from', $facility_filter)
            ->paginate(20);

        return view('monitor.referral',[
            'title' => 'Referral Status',
            'facility_filter' => $facility_filter,
            'data' => $data
        ]);
    }

    public function filterReferral(Request $req)
    {
        $range = explode('-',str_replace(' ', '', $req->date));
        $tmp1 = explode('/',$range[0]);
        $tmp2 = explode('/',$range[1]);

        $start = $tmp1[2].'-'.$tmp1[0].'-'.$tmp1[1];
        $end = $tmp2[2].'-'.$tmp2[0].'-'.$tmp2[1];
        
        Session::put('referralstatus_facility',$req->facility_filter);
        Session::put('startDateReportReferral',$start);
        Session::put('endDateReportReferral',$end);
        return self::referral();
    }

    public function NoAction($facility_id,$date_start,$date_end,$type){

        $data = Tracking::select(
            'tracking.*',
            DB::raw('CONCAT(patients.fname," ",patients.mname," ",patients.lname) as patient_name'),
            DB::raw("TIMESTAMPDIFF(YEAR, patients.dob, CURDATE()) AS age"),
            DB::raw('CONCAT(users.fname," ",users.mname," ",users.lname) as referring_md'),
            'patients.sex',
            'facility.name as facility_name',
            'facility.id as facility_id',
            'patients.id as patient_id'
        )
            ->join('patients','patients.id','=','tracking.patient_id')
            ->join('facility','facility.id','=','tracking.referred_to')
            ->leftJoin('users','users.id','=','tracking.referring_md')
            ->where('tracking.'.$type,"=",$facility_id)
            ->whereBetween('tracking.date_referred',[$date_start,$date_end])
            ->where(function($q){
                $q->where('tracking.status','referred')
                    ->orwhere('tracking.status','cancelled')
                    ->orwhere('tracking.status','rejected')
                    ->orwhere('tracking.status','seen')
                    ->orwhere('tracking.status','archived');
            })
            ->orderBy('tracking.date_referred','desc')
            ->paginate(10);

        return view("monitor.no_action_view",[
            'title' => 'Viewed Only',
            'data' => $data,
        ]);
    }
}
