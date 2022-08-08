<?php

namespace App\Http\Controllers\doctor;


use App\Uploads;
use App\Tracking;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class UploadCtrl extends Controller
{
    public function uploadBody(Request $req)
    {
        $code = $req->code;
        $data = Uploads::select("uploads.*",
        "filetypes.description as file_type",
        "users.fname",
        "users.mname",
        "users.lname"
    ) ->leftjoin("filetypes","filetypes.id","=","uploads.type_id")
    ->leftjoin("users","users.id","=","uploads.uploaded_by")
     ->where('uploads.referral_code',$code)
     ->get();
        
        
        return view('doctor.upload_body',[
            'code' => $code,
            'data' => $data
        ]);
    }

    public function uploadFile(Request $req)
    {
        Session::put('unique_referral_code',$req->refer_code);
        $user = Session::get('auth');
        $refer_code = $req->refer_code;

        mkdir("app/uploads/$refer_code");
        
            foreach($req->file as $num => $file)
            {
                $filename = $file->getClientOriginalName();
                $file->move(base_path('public/uploads/'.$refer_code), $filename);
                $data = array(
                    'name' => $filename,
                    'path' => "app/uploads/".$refer_code,
                    'uploaded_date' => date('Y-m-d H:i:s'),
                    'uploaded_by' => $user->id,
                    'type_id' => $req->file_type[$num],
                    'referral_code' => $refer_code
                );
               Uploads::create($data);
               Session::put('upload_file',true);
               Session::put('upload_file_message','File Uploaded Successfully!');
            }
            return Redirect::back();
    }

    public function fileView($id)
    {
       $data = Uploads::find($id);

       return view('doctor.viewfile',[
           'data' => $data
       ]);
    }

    public function fileDelete($id)
    {
        $data = Uploads::find($id)->delete();
        Session::put('upload_file',true);
        Session::put('upload_file_message','File Deleted Successfully!');

        return Redirect::back();
    }
}
