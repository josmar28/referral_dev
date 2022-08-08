@extends('layouts.app')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <div class="pull-right">
                <form action="{{ asset('admin/province') }}" method="POST" class="form-inline">
                    {{ csrf_field() }}
                    <div class="form-group-lg" style="margin-bottom: 10px;">
                        <!-- <input type="text" class="form-control" name="keyword" placeholder="Search province..." value="{{ Session::get("keyword") }}">
                        <button type="submit" class="btn btn-success btn-sm btn-flat">
                            <i class="fa fa-search"></i> Search
                        </button>
                        <button type="submit" value="view_all" name="view_all" class="btn btn-warning btn-sm btn-flat">
                            <i class="fa fa-eye"></i> View All
                        </button> -->
                        <a href="#filetypes_modal" data-toggle="modal" class="btn btn-info btn-sm btn-flat" onclick="filetypeBody('empty')">
                            <i class="fa fa-hospital-o"></i> Add Type
                        </a>
                    </div>
                </form>
            </div>
            <h3></h3>
        </div>
        <div class="box-body">
            @if(count($data)>0)
            {{$data}}
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <tr class="bg-black">
                            <th>Description</th>
                            <th>Date Created</th>
                        </tr>
                        @foreach($data as $row)
                            <tr>
                                <td style="white-space: nowrap;">
                                <b>
                                        <a
                                            href="#filetypes_modal"
                                            data-toggle="modal"
                                            onclick="filetypeBody('<?php echo $row->id ?>')"
                                        >
                                            {{ $row->description }}
                                        </a>
                                    </b>
                                </td>
                                
                                <td>
                                {{ $row->created_at }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    <div class="text-center">
                        {{ $data->links() }}
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

    @include('admin.modal.filetypes_modal')
@endsection
@section('js')
    <script>
        <?php $user = Session::get('auth'); ?>
        function filetypeBody(data){
            var json;
            if(data == 'empty'){
                json = {
                    "_token" : "<?php echo csrf_token()?>"
                };
            } else {
                json = {
                    "id" : data,
                    "_token" : "<?php echo csrf_token()?>"
                };
            }
            var url = "<?php echo asset('admin/filetypes_body') ?>";
            $.post(url,json,function(result){
                $(".filetypes_body").html(result);
            })
        }

        function ProvinceDelete(facility_id){
            $(".province_id").val(facility_id);
        }

        @if(Session::get('types'))
        Lobibox.notify('success', {
            title: "",
            msg: "<?php echo Session::get("types_message"); ?>",
            size: 'mini',
            rounded: true
        });
        <?php
        Session::put("types",false);
        Session::put("types_message",false);
        ?>
        @endif
    </script>
@endsection

