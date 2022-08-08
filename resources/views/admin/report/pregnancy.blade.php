<?php
    use App\Http\Controllers\admin\ReportCtrl as Admin;
?>

@extends('layouts.app')

@section('content')
    <style>
        label {
            padding: 0px !important;
        }
    </style>
    <div class="row col-md-12">
        <div class="box box-success">
            <div class="box-header with-border">
                <div class="pull-right">
                    <form action="{{ asset('admin/pregnancy') }}" method="POST" class="form-inline">
                        {{ csrf_field() }}
                        <div class="form-group-sm">
                        <input type="text" class="form-control" name="date_range" value="{{ date("m/d/Y",strtotime($date_start)).' - '.date("m/d/Y",strtotime($date_end)) }}" placeholder="Filter your date here..." id="consolidate_date_range">
                            <button type="submit" class="btn-sm btn-info btn-flat"><i class="fa fa-search"></i> Filter</button>
                        </div>
                    </form>
                </div>
                <h3>{{ $title }}</h3>
            </div>
            <div class="box-body">
                @if(count($data) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <tr class="bg-black">
                                <th style="width:20%">Facility Name</th>
                                <th style="width:20%">Teenage Pregnancy</th>
                                <th style="width:20%">Legal Age Pregnancy</th>
                                <th style="width:20%">Date Range</th>
                            </tr>
                            <?php
                            $date1 = date("Y-m-d",strtotime($date_start));
                            $date2 = date("Y-m-d",strtotime($date_end));
                            ?>
                         @foreach($facility as $row)
                         <?php
                         $teenage = Admin::Countpregnancy($row->id,$date1,$date2);
                         $legal = Admin::legalPregnancy($row->id,$date1,$date2);
                         
                         ?>
                                <tr>
                                    <td> {{ $row->name}} </td>
                                    <td> {{ $teenage}} </td>
                                    <td> {{ $legal}} </td>
                                    <td> {{$date1}} - {{$date2}} </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <span class="text-warning">
                            <i class="fa fa-warning"></i> No data found!
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection


@section('css')

@endsection

@section('js')
    <script>

        //Date range picker
        $('#onboard_picker').daterangepicker({
            "singleDatePicker": true
        });
        $('#consolidate_date_range').daterangepicker();
    </script>
@endsection
