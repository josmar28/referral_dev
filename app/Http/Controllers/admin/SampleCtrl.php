<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class SampleCtrl extends Controller
{
    public function export() 
    {
         return Excel::download(new UsersExport, 'users.xlsx');
    }
}
