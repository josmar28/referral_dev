<?php

namespace App\Http\Controllers\monitor;

use App\Login;
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

class OnlineCtrl extends Controller
{
    public function searchDoctor(Request $req)
    {
        $data = array(
            'keyword' => $req->keyword,
            'facility_id' => $req->facility_id
        );
        Session::put('search_doctor',$data);
        return self::index();
    }

    public function index()
    {
        ParamCtrl::lastLogin();
        $user = Session::get('auth');
        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->endOfDay();

        $data = Login::select(
            'users.id as id',
            'users.fname as fname',
            'users.lname as lname',
            'users.mname as mname',
            'users.level as level',
            'users.contact',
            'facility.name as facility',
            'facility.abbr as abbr',
            'department.description as department',
            'login.login as login',
            'login.status as status'
        );

        $data = $data->where(function($q) {
            $q->where('login.status','login')
                ->orwhere('login.status','login_off');
        });

        $data = $data->join('users','users.id','=','login.userId')
                ->join('facility','facility.id','=','users.facility_id')
                ->leftJoin('department','department.id','=','users.department_id');

        $data = $data
                ->whereBetween('login.login',[$start,$end])
                ->where('login.logout','0000-00-00 00:00:00')
                ->where('users.province',$user->province)
                ->orderBy('login.id','desc')
                ->get();

        $date_start = Carbon::now()->startOfDay();
        $date_end = Carbon::now()->endOfDay();
        $hospitals = \DB::connection('mysql')->select("call online_facility_view('$date_start','$date_end')");

        return view('monitor.list',[
            'title' => 'Online Users',
            'data' => $data,
            'hospitals' => $hospitals
        ]);
    }
}
