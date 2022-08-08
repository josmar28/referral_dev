@extends('layouts.app')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <div class="pull-right">
                <form action="{{ asset('admin/province') }}" method="POST" class="form-inline">
                    {{ csrf_field() }}
                    <div class="form-group-lg" style="margin-bottom: 10px;">
                        <input type="text" class="form-control" name="keyword" placeholder="Search type..." value="{{ Session::get("keyword") }}">
                        <button type="submit" class="btn btn-success btn-sm btn-flat">
                            <i class="fa fa-search"></i> Search
                        </button>
                        <button type="submit" value="view_all" name="view_all" class="btn btn-warning btn-sm btn-flat">
                            <i class="fa fa-eye"></i> View All
                        </button>
                        <a href="#incident_modal" data-toggle="modal" class="btn btn-info btn-sm btn-flat" onclick="incident_Body('empty')">
                            <i class="fa fa-hospital-o"></i> Add Type
                        </a>
                        <a href="#incident" data-toggle="modal" class="btn btn-info btn-sm btn-flat" onclick="incident('empty')">
                            <i class="fa fa-hospital-o"></i> Add Incdi
                        </a>
                    </div>
                </form>
            </div>
            <h3>{{ $title }}</h3>
        </div>
        <div class="box-body">
            @if(count($data)>0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <tr class="bg-black">
                            <th>Incident Type</th>
                            <th>Created Date</th>
                            <th>Updated Date</th>
                        </tr>
                        @foreach($data as $row)
                            <tr>
                            <td style="white-space: nowrap;">
                            <b>
                                <a
                                    href="#incident_modal"
                                    data-toggle="modal"
                                    onclick="incident_Body('<?php echo $row->id ?>')"
                                    >
                                    {{ $row->type }}
                                </a>
                            </b>
                            </td>
                            <td style="white-space: nowrap;">
                                {{$row->created_at}}
                            </td>
                            <td style="white-space: nowrap;">
                            {{$row->updated_at}}
                            </td>
                            </tr>
                        @endforeach
                    </table>
                    <div class="text-center">
                      
                    </div>
                </div>
            @else
                <div class="alert alert-warning">
                    <span class="text-warning">
                        <i class="fa fa-warning"></i> No Province found!
                    </span>
                </div>
            @endif
        </div>
    </div>

    @include('admin.modal.incident_modal')
    @include('modal.accept')
@endsection
@section('js')
    <script>
        <?php $user = Session::get('auth'); ?>
        function incident_Body(data){
            var json;
            if(data == 'empty'){
                json = {
                    "_token" : "<?php echo csrf_token()?>"
                };
            } else {
                json = {
                    "inci_id" : data,
                    "_token" : "<?php echo csrf_token()?>"
                };
            }
            var url = "<?php echo asset('admin/incident_type/body') ?>";
            $.post(url,json,function(result){
                $(".incident_body").html(result);
            })
        }

        function incident(data){
            var json;
            if(data == 'empty'){
                json = {
                    "_token" : "<?php echo csrf_token()?>"
                };
            } else {
                json = {
                    "inci_id" : data,
                    "_token" : "<?php echo csrf_token()?>"
                };
            }
            var url = "<?php echo asset('admin/incident/body') ?>";
            $.post(url,json,function(result){
                $(".inci_body").html(result);
            })
        }

        function subDelete(subcat_id){
            $(".subcat_id").val(subcat_id);
        }

        function ProvinceDelete(facility_id){
            $(".province_id").val(facility_id);
        }

        @if(Session::get('province'))
        Lobibox.notify('success', {
            title: "",
            msg: "<?php echo Session::get("province_message"); ?>",
            size: 'mini',
            rounded: true
        });
        <?php
        Session::put("province",false);
        Session::put("province_message",false)
        ?>
        @endif
    </script>
@endsection

