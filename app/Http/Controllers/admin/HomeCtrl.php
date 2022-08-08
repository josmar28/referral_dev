<?php

namespace App\Http\Controllers\admin;

use App\Facility;
use App\Login;
use App\Tracking;
use App\Diagnosis;
use App\DiagSubcat;
use App\DiagMain;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;


class HomeCtrl extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        //$this->middleware('admin');
        //$this->middleware('doctor');
    }

    public function index()
    {
        return view('admin.home',[
            'title' => 'Admin: Dashboard'
        ]);
    }

    public function count()
    {
        $tmp = User::where('level','doctor');
        $data['countDoctors'] = $tmp->count();

        $data['countReferral'] = Tracking::count();
        $countFacility = Facility::where('status',1)->count();
        return array(
            'countDoctors' => number_format($data['countDoctors']),
            'countReferral' => number_format($data['countReferral']),
            'countOnline' => number_format(self::countOnline()),
            'countFacility' => number_format($countFacility)
        );
    }

    function countOnline()
    {
        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->endOfDay();

        $data = Login::where(function($q) {
                    $q->where('login.status','login')
                        ->orwhere('login.status','login_off');
                })
                ->join('users','users.id','=','login.userId')
                ->where('users.level','doctor')
                ->whereBetween('login.login',[$start,$end])
                ->where('login.logout','0000-00-00 00:00:00')
                ->count();
        return $data;
    }

    public function mainCat(Request $request)
    {
        if($request->keyword)
        {
              $keyword = $request->keyword;
              $data = DiagMain::where(function($q) use ($keyword){
                  $q->where('diagcat',"like","%$keyword%")
                      ->orwhere('catdesc',"like","%$keyword%");
                     
                  })
                  ->where('void',0)
                  ->orderby('id','asc')
                  ->paginate(50);
        }
        else{
        $data = DiagMain::where('void',0)
        ->orderby('id','asc')
        ->paginate(50);
      }
        return view('admin.diagnosis.maincat',[
            'data' => $data
        ]);

    }
    public function subCat(Request $request)
    {
        if($request->keyword)
        {
              $keyword = $request->keyword;
              $data = DiagSubcat::where(function($q) use ($keyword){
                  $q->where('diagsubcat',"like","%$keyword%")
                      ->orwhere('diagscatdesc',"like","%$keyword%");
                     
                  })
                  ->where('void',0)
                  ->orderby('id','asc')
                  ->paginate(50);
        }
        else{
        $data = DiagSubcat::where('void',0)
        ->orderby('id','asc')
        ->paginate(50);
      }
        return view('admin.diagnosis.subcat',[
            'data' => $data
        ]);
    }
    public function diag(Request $request)
    {
    if($request->keyword)
    {
        $keyword = $request->keyword;
        $data = Diagnosis::where(function($q) use ($keyword){
            $q->where('diagcode',"like","%$keyword%")
                ->orwhere('diagdesc',"like","%$keyword%");
               
            })
            ->where('void',0)
            ->orderby('id','asc')
            ->paginate(50);
         
    }else{
        $data = Diagnosis::where('void',0)
        ->orderby('id','asc')
        ->paginate(50);
    }
    return view('admin.diagnosis.diagnosis',[
        'data' => $data
    ]);
    }
    public function diagBody(Request $request)
    {
        $data = Diagnosis::find($request->diag_id);
        return view('admin.diagnosis_body', [
            'data' => $data
        ]);
    }
    public function diagnosisAdd(Request $request)
    {
        $data = $request->all();
        unset($data['_token']);

        if(isset($request->id)){
            Diagnosis::find($request->id)->update($data);
            Session::put('diagnosis_message','Successfully updated diagnosis');
        } else {
            Diagnosis::create($data);
            Session::put('diagnosis_message','Successfully added diagnosis');
        }

        Session::put('diagnosis',true);
        return Redirect::back();
    }

    public function diagnosisDelete(Request $request)
    {
        Diagnosis::where('id',$request->diag_id)
        ->update([
            'void' => '1'
        ]);
        Session::put('diag_delete_message','Deleted Diagnosis');
        Session::put('diag_delete',true);
        return Redirect::back();

    }

    public function subcatBody(Request $request)
    {
        $data = DiagSubcat::find($request->subcat_id);
        return view('admin.subcat_body', [
            'data' => $data
        ]);
        
    }

    public function subcatAdd(Request $request)
    {
        $data = $request->all();
        unset($data['_token']);

        if(isset($request->id)){
            DiagSubcat::find($request->id)->update($data);
            Session::put('subcat_message','Successfully updated sub category');
        } else {
            DiagSubcat::create($data);
            Session::put('subcat_message','Successfully added sub category');
        }

        Session::put('subcat',true);
        return Redirect::back();
    }

    public function subcatDelete(Request $request)
    {
        DiagSubcat::where('id',$request->subcat_id)
        ->update([
            'void' => '1'
        ]);
        Session::put('sub_delete_message','Deleted Sub Category');
        Session::put('sub_delete',true);
        return Redirect::back();
    }

    public function maincatBody(Request $request)
    {   
        $data = DiagMain::find($request->maincat_id);
        return view('admin.maincat_body', [
            'data' => $data
        ]);
    }

    public function maincatAdd(Request $request)
    {
        $data = $request->all();
        unset($data['_token']);

        if(isset($request->id)){
            DiagMain::find($request->id)->update($data);
            Session::put('maincat_message','Successfully updated main category');
        } else {
            DiagMain::create($data);
            Session::put('maincat_message','Successfully added main category');
        }

        Session::put('maincat',true);
        return Redirect::back();    
    }

    public function maincatDelete(Request $request)
    {
        DiagMain::where('id',$request->maincat_id)
        ->update([
            'void' => '1'
        ]);
        Session::put('main_delete_message','Deleted Main Category');
        Session::put('main_delete',true);
        return Redirect::back();
    }


    static function getMaincat($id)
    {
        $code = DiagSubcat::where('diagmcat',$id)
        ->get();
        if($code){
            return $code;
        }
        return 'N/A';
    }

}
