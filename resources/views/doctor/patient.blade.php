<?php
    $user = Session::get('auth');
?>

@extends('layouts.app')

@section('content')
    <style>
        .ui-autocomplete
        {
            background-color: white;
            width: 20%;
            z-index: 1100;
            max-height: 300px;
            overflow-y: auto;
            /* prevent horizontal scrollbar */
            overflow-x: hidden;
        }
        .ui-menu-item {
            cursor: pointer;
        }

    </style>
    <div class="col-md-3">
        @include('sidebar.'.$sidebar)
    </div>
    <div class="col-md-9">
        <div class="jim-content">
            <h3 class="page-header">{{ $title }}</h3>
            @if(count($data))
                <div class="table-responsive">
                    <table class="table table-striped"  style="white-space:nowrap;">
                        <tbody>
                        <tr>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Age / DOB</th>
                            <th>Barangay</th>
                            <th style="width:18%;">Action</th>
                        </tr>
                        @foreach($data as $row)
                        <?php
                            $modal = ($row->type=='normal') ? '#normalFormModal' : '#pregnantFormModal';
                        ?>
                        <tr>
                            <td>
                                <b>
                                    <a href="#patient_modal"
                                       data-toggle="modal"
                                       data-id = "{{ $row->id }}"
                                       onclick="PatientBody('<?php echo $row->id ?>')"
                                       class="update_info">
                                        {{ $row->lname }}, {{ $row->fname }} {{ $row->mname }}
                                    </a>
                                </b><br>
                                <small class="text-success">{{ $row->contact }}</small>
                            </td>
                            <td>
                                {{ $row->sex }}<br>
                                <small class="text-success">{{ $row->civil_status }}</small>
                            </td>
                            <td>
                                <?php $age = \App\Http\Controllers\ParamCtrl::getAge($row->dob);?>
                                {{ $age }} years old
                                <br />
                                <small class="text-muted">{{ date('M d, Y',strtotime($row->dob)) }}</small>
                            </td>
                            <td>
                                <?php
                                    $brgy_id = ($source=='tsekap') ? $row->barangay_id: $row->brgy;
                                    $city_id = ($source=='tsekap') ? $row->muncity_id: $row->muncity;
                                    $phic_id = ($source=='tsekap') ? $row->phicID: $row->phic_id;
                                    $phic_id_stat = 0;
                                    if($phic_id){
                                        $phic_id_stat = 1;
                                    }
                                ?>
                                @if($brgy_id!=0)
                                {{ $brgy = \App\Barangay::find($brgy_id)->description }}<br />
                                <small class="text-success">{{ $city = \App\Muncity::find($city_id)->description }}</small>
                                @else
                                    {{ $row->address }}
                                @endif
                            </td>
                            <td>
                                @if($row->sex=='Female' && ($age >= 10 && $age <= 49))
                                    <a href="#pregnantModal"
                                       data-patient_id = "{{ $row->id }}"
                                       data-toggle="modal"
                                       class="btn btn-primary btn-xs profile_info hide">
                                        <i class="fa fa-stethoscope"></i>
                                        Refer
                                    </a>
                                    <a href="#pregnantModalWalkIn"
                                       data-patient_id = "{{ $row->id }}"
                                       data-toggle="modal"
                                       class="btn btn-warning btn-xs profile_info hide">
                                        <i class="fa fa-ambulance"></i>
                                        Walk-In
                                    </a>
                                @else
                                    <a href="#normalFormModal"
                                       data-patient_id = "{{ $row->id }}"
                                       data-backdrop="static"
                                       data-toggle="modal"
                                       class="btn btn-primary btn-xs profile_info hide">
                                        <i class="fa fa-stethoscope"></i>
                                        Refer
                                    </a>
                                    <a href="#normalFormModalWalkIn"
                                       data-patient_id = "{{ $row->id }}"
                                       data-backdrop="static"
                                       data-toggle="modal"
                                       class="btn btn-warning btn-xs profile_info hide">
                                        <i class="fa fa-ambulance"></i>
                                        Walk-In
                                    </a>
                                @endif
                                   <a href="#vital_modal"
                                      onclick="VitalBody('<?php echo $row->id ?>')"
                                       data-patient_id = "{{ $row->id }}"
                                       data-backdrop="static"
                                       data-toggle="modal"
                                       class="btn btn-success btn-xs vital_info hide">
                                        <i class="fa fa-plus"></i>
                                        V.S / P.E
                                    </a>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <ul class="pagination pagination-sm no-margin pull-right">
                        {{ $data->links() }}
                </ul>

            @else
                <div class="alert alert-warning">
                <span class="text-warning">
                    <i class="fa fa-warning"></i> Patient not found!
                </span>
                </div>
            @endif
            <div class="clearfix"></div>
        </div>
    </div>
    @include('modal.pregnantModal')
    @include('modal.normal_form_editable')
    @include('modal.normal_form_editable_walkin')
    @include('modal.pregnant_form_editable')
    @include('modal.pregnant_form_editable_walkin')
    @include('modal.addvital')
    @include('modal.pexam')
@endsection

@section('js')
@include('script.filterMuncity')
@include('script.firebase')
@include('script.datetime')
<script>
function VitalBody(patient_id){
    $('.loading').show();
        console.log(patient_id);
        var url = "<?php echo asset('doctor/patient/vitalbody'); ?>";
        var json = {
            "patient_id" : patient_id,
            "_token" : "<?php echo csrf_token(); ?>"
        };
        $.post(url,json,function(result){
            $('.loading').hide();
            $(".vital_body").html(result);
        });
    }
    //custom autocomplete (category selection)
    /*jQuery(function($) {
        $.widget("custom.catcomplete", $.ui.autocomplete, {
            _create: function () {
                this._super();
                this.widget().menu("option", "items", "> :not(.ui-autocomplete-category)");
            },
            _renderMenu: function (ul, items) {
                var that = this, currentCategory = "";
                $.each(items, function (index, item) {
                    that._renderItemData(ul, item);
                    return index < 500;
                });
            }
        });

        var icd10 = [];
        $.each(<?php /*echo json_encode($icd10); */?>, function (x, data) {
            icd10.push({label: data.description, id: data.id, icd_code: data.code});
        });

        var icd_code = '';
        $("#diagnosis").catcomplete({
            delay: 0,
            source: icd10,
            select: function (e, ui) {
                keyword = ui.item.value;
                icd_code = ui.item.icd_code;
                $("#icd_code").val(icd_code);
                console.log(ui.item);
            }
        });
        $("#diagnosis_walkin").catcomplete({
            delay: 0,
            source: icd10,
            select: function (e, ui) {
                keyword = ui.item.value;
                icd_code = ui.item.icd_code;
                $("#icd_code_walkin").val(icd_code);
                console.log(ui.item);
            }
        });

    });

    function Icd10Checker(data){
        if(data.val() != icd_code){
            $("#icd_code").val("");
        }
    }

    function Icd10Checker_walkin(data){
        if(data.val() != icd_code){
            $("#icd_code_walkin").val("");
        }
    }*/

    $(document).ready(function(){
            $(document).on("click",".btn_add_diag", function(e){
            e.preventDefault();
            var markup = "<span><input list='diagnosis' name='diagnosis[]' class='form-control' rows='7' style='resize: none;width: 90%;'><datalist id = 'diagnosis'>@foreach($data as $dataa)<option value='{{ $dataa->diagcode }} {{ $dataa->diagdesc}}'></option>@endforeach</datalist><input class='checkbox' type='checkbox'></span>";
            $(".add_col").append(markup);
        });

        $(document).on("click",".btn_delete_diag", function(e){
        var val = []; 
        e.preventDefault(); 
            $(':checkbox:checked').each(function(i){
            val[i] = $(this).val();
            if($(this).val() == "on"){
                    $(this).parents("span").remove();
                }
            });

        });
    });   

    function PatientBody(patient_id){
        console.log(patient_id);
        var url = "<?php echo asset('doctor/patient/update'); ?>";
        var json = {
            "patient_id" : patient_id,
            "_token" : "<?php echo csrf_token(); ?>"
        };
        $.post(url,json,function(result){
            $(".patient_body").html(result);
        });
    }

    $(".select2").select2({ width: '100%' });
    var referred_facility = 0;
    var referring_facility = "{{ $user->facility_id }}";
    var referred_facility = '';
    var referring_facility_name = $(".referring_name").val();
    var patient_form_id = 0;
    var referring_md = "{{ $user->fname }} {{ $user->mname }} {{ $user->lname }}";
    var name,
        age,
        sex,
        address,
        form_type,
        reason,
        patient_id,
        civil_status,
        phic_status,
        phic_id,
        department_id,
        department_name;

    $('.form-submit').on('submit',function(){
        $('.loading').show();
        $('.btn-submit').attr('disabled',true);
    });

    $('.select_facility').on('change',function(){
        var id = $(this).val();
        referred_facility = id;
        var url = "{{ url('location/facility/') }}";
        $.ajax({
            url: url+'/'+id,
            type: 'GET',
            success: function(data){
                /*$.get("<?php echo asset('inventory/append').'/'; ?>"+data.facility_id,function(inventory_body){
                    $(".inventory_body").html(inventory_body);
                });*/
                $('.facility_address').html(data.address);

                $('.select_department').empty()
                    .append($('<option>', {
                        value: '',
                        text : 'Select Department...'
                    }));
                jQuery.each(data.departments, function(i,val){
                    $('.select_department').append($('<option>', {
                        value: val.id,
                        text : val.description
                    }));

                });
            },
            error: function(error){
                $('#serverModal').modal();
            }
        });
    });

    $('.select_facility_walkin').on('change',function(){
        var id = $(this).val();
        referred_facility = "{{ $user->facility_id }}";
        var url = "{{ url('location/facility/') }}";
        referring_facility_name = $(this).find(':selected').data('name');

        $.ajax({
            url: url+'/'+id,
            type: 'GET',
            success: function(data){
                console.log(data);
                $('.facility_address').html(data.address);
            },
            error: function(){
                $('#serverModal').modal();
            }
        });
    });

    $('.select_department').on('change',function(){
        var id = $(this).val();
        var list = "{{ url('list/doctor') }}";
        if(referred_facility==0){
            referred_facility = "{{ $user->facility_id }}";
        }
        $.ajax({
            url: list+'/'+referred_facility+'/'+id,
            type: 'GET',
            success: function(result){
                console.log(result)
                $('.referred_md').empty()
                    .append($('<option>', {
                        value: '',
                        text : 'Any...'
                    }));
                jQuery.each(result, function(i,val){
                    $('.referred_md').append($('<option>', {
                        value: val.id,
                        text : 'Dr. '+val.fname+' '+val.mname+' '+val.lname+' - '+val.contact
                    }));

                });
            },
            error:function(){
                $('#serverModal').modal();
            }
        });
    });
  
   
    $('.profile_info').removeClass('hide');
    $('.vital_info').removeClass('hide');
    $('.profile_info').on('click',function(){
        patient_id = $(this).data('patient_id');
        $.ajax({
            url: "{{ url('doctor/patient/info/') }}/"+patient_id,
            type: "GET",
            success: function(data){
                patient_id = data.id;
                name = data.patient_name;
                sex = data.sex;
                age = data.age;
                civil_status = data.civil_status;
                phic_status = data.phic_status;
                phic_id = data.phic_id;
                address = data.address;

         
                        
                    $.ajax({
                    url: "{{ url('doctor/patient/caseinfo/') }}/"+patient_id,
                    type: "GET",
                    success: function(result){
                        var data = result;
                        if(data == '')
                        {
                            var val = 'No Vital Signs encoded';
                        }else
                        {
                        var now = new Date(result.consultation_date);
                        var day = now.getDate() < 10 ? "0" + now.getDate() : now.getDate();
                        var month = now.getMonth() < 10 ? "0" + (now.getMonth() + 1) : (now.getMonth() + 1);
                        var hrs = now.getHours() < 10 ? "0" + now.getHours(): now.getHours();
                        hrs = hrs % 12;
                        hrs = hrs ? hrs : 12;
                        var am_pm = now.getHours() >= 12 ? "PM": "AM"
                        var min = now.getMinutes() < 10 ? "0" + now.getMinutes(): now.getMinutes();
                        var today = now.getFullYear()+"-"+(month)+"-"+(day)+" "+(hrs)+":"+(min)+" "+(am_pm) ;

                        var vs = 'VITAL SIGNS' + "\n";
                        var bps =  result.bps != "" ? 'Systolic/Diastolic: ' + result.bps + '/' + result.bpd + "\n" : 'Systolic/Diastolic: ' + "\n";
                        var res = result.respiratory_rate != "" ? 'Respirator Rate: ' + result.respiratory_rate + "\n" : 'Respirator Rate: ' + "\n";        
                        var body = result.body_temperatur != "" ? 'Body Temperature: ' + result.body_temperature + "\n" : 'Body Temperature: ' + "\n";
                        var heart = result.heart_rate != "" ? 'Heart Rate: ' + result.heart_rate + "\n" : 'Heart Rate: ' + "\n";
                        var pulse = result.pulse_rate != "" ? 'Pulse Rate: ' + result.pulse_rate + "\n": 'Pulse Rate: ' + "\n";
                        var cons = today != "" ? 'VS Consultation Date: ' + today + "\n" : 'VS Consultation Date: ' + "\n";
                         
                        var val = vs + bps + res + body + heart + pulse + cons;
                       }
                    
                                    $.ajax({
                                    url: "{{ url('doctor/patient/pexaminfo/') }}/"+patient_id,
                                    type: "GET",
                                    success: function(results){
                                        var datas = results;
                                        if(datas == '')
                                        {
                                            var val2 = 'No Physical Exam encoded';
                                        }
                                        else{
                                        var now1 = new Date(results.consultation_date);
                                        var day1 = now1.getDate() < 10 ? "0" + now1.getDate() : now1.getDate();
                                        var month1 = now1.getMonth() < 10 ? "0" + (now1.getMonth() + 1) : (now1.getMonth() + 1);
                                        var hrs1 = now1.getHours() < 10 ? "0" + now1.getHours(): now1.getHours();
                                        hrs1 = hrs1 % 12;
                                        hrs1 = hrs1 ? hrs1 : 12;
                                        var am_pm1 = now1.getHours() >= 12 ? "PM": "AM"
                                        var min1 = now1.getMinutes() < 10 ? "0" + now1.getMinutes(): now1.getMinutes();
                                        var today1 = now1.getFullYear()+"-"+(month1)+"-"+(day1)+" "+(hrs1)+":"+(min1)+" "+(am_pm1);
                                        
                                        var pexam = 'PHYSICAL EXAM' + "\n";
                                        var neck = results.neck != "" ? 'Neck:' + results.neck.split(',') + "\n" : " ";
                                        var breast = results.breast != "" ? 'Breast :' + results.breast.split(',') + "\n" : " ";
                                        var thorax = results.thorax != "" ? 'Thorax :' + results.thorax.split(',') + "\n" : " ";
                                        var abdomen =  results.abdomen != "" ? 'Abdomen :' + results.abdomen.split(',') + "\n" : " ";
                                        var genitals = results.genitals != "" ? 'Genitals : ' + results.genitals.split(',') + "\n" : " ";
                                        var extremities = results.extremities != "" ? "Extremities : " + results.extremities.split(',') + "\n"  : " ";
                                        var conjunctiva = results.conjunctiva != "" ? 'Conjunctiva : ' + results.conjunctiva.split(',') + "\n" : " ";

                                        var heigth = results.heigth != "" ? 'Heigth/Weigth: ' + results.heigth + "/" + results.weigth + "\n" : " ";
                                        var head =  results.head != "" ? 'Head: ' + results.head + "\n" : " ";
                                        var conjunctiva_remarks = results.conjunctiva_remarks != "" ? 'Conjuctiva Remarks: ' + results.conjunctiva_remarks + "\n" : " ";
                                        var chest = results.chest != "" ? 'Chest: ' + results.chest + "\n" : "";
                                        var breast_remarks = results.breast_remarks != "" ? 'Breast Remarks: ' + results.breast_remarks + "\n" : " ";
                                        var thorax_remarks = results.thorax_remarks != "" ? 'Thorax Remarks: ' + results.thorax_remarks + "\n" : " ";
                                        var abdomen_remarks = results.abdomen_remarks != "" ? 'Abdomen Remarks: ' + results.abdomen_remarks + "\n" : " ";
                                        var genitals_remarks = results.genitals_remarks != "" ? 'Genitals Remarks: ' + results.genitals_remarks + "\n" : " ";
                                        var extremities_remarks = results.extremities_remarks != "" ? 'Extremities Remarks: ' + results.extremities_remarks + "\n" : " ";
                                        var cons1 = results.consultation_date != "" ? 'PE Consultation Date: ' + today1 + "\n" : " ";

                                        var val2 = pexam + heigth + head + neck + breast + breast_remarks + thorax + thorax_remarks + abdomen + abdomen_remarks + genitals + genitals_remarks 
                                        + extremities + extremities_remarks + conjunctiva + conjunctiva_remarks + cons1;
                                        }
                                        var major = val + val2;
                                    
                    
                                    // alert(result.bps);
                                // alert(result.bpd);
                                $('.patient_name').html(name);
                                $('.patient_address').html(address);
                                $('input[name="phic_status"][value="'+phic_status+'"]').attr('checked',true);
                                $('.phic_id').val(phic_id);
                                $('.patient_sex').val(sex);
                                $('.patient_age').html(age);
                                $("#woman_age").val(age);
                                $('.civil_status').val(civil_status);
                                $('.patient_id').val(patient_id);
                            
                               
                                $('textarea#case_summary1').val(val2);
                                $('textarea#woman_major_findings').val(major);
   
                                    },
                                 
                                    error: function(){
                                        $('#serverModal').modal();
                                    }
                                });
                                $('textarea#case_summary').val(val);
                },
                    error: function(){
                        $('#serverModal').modal();
                    }
                });
            
            },
            error: function(){
                $('#serverModal').modal();
            }
        });

    });

    $('.normal_form').on('submit',function(e){
        e.preventDefault();
        reason = $('.reason_referral').val();
        form_type = '#normalFormModal';
        department_id = $('.select_department_normal').val();
        department_name = $('.select_department_normal :selected').text();
        $(this).ajaxSubmit({
            url: "{{ url('doctor/patient/refer/normal') }}",
            type: 'POST',
            success: function(data){
                console.log(data);
                //location.reload();
                sendNormalData(data);
            },
            error: function(){
                $('#serverModal').modal();
            }
        });

    });

    $('.normal_form_walkin').on('submit',function(e){
        e.preventDefault();
        reason = $('.reason_referral').val();
        form_type = '#normalFormModal';
        department_id = $('.select_department_normal').val();
        department_name = $('.select_department_normal :selected').text();
        $(this).ajaxSubmit({
            url: "{{ url('doctor/patient/refer/walkin/normal') }}",
            type: 'POST',
            success: function(data){
                console.log(data);
                setTimeout(function(){
                    window.location.reload(false);
                },500);
            },
            error: function(){
                $('#serverModal').modal();
            }
        });

    });

    $('.pregnant_form').on('submit',function(e){
        e.preventDefault();
        form_type = '#pregnantFormModal';
        sex = 'Female';
        reason = $('.woman_information_given').val();
        department_id = $('.select_department_pregnant').val();
        department_name = $('.select_department_pregnant :selected').text();
        $(this).ajaxSubmit({
            url: "{{ url('doctor/patient/refer/pregnant') }}",
            type: 'POST',
            success: function(data){
                //console.log(data);
                sendNormalData(data);
            },
            error: function(){
                $('#serverModal').modal();
            }
        });

    });

    $('.pregnant_form_walkin').on('submit',function(e){
        e.preventDefault();
        form_type = '#pregnantFormModal';
        sex = 'Female';
        reason = $('.woman_information_given').val();
        department_id = $('.select_department_pregnant').val();
        department_name = $('.select_department_pregnant :selected').text();
        $(this).ajaxSubmit({
            url: "{{ url('doctor/patient/refer/walkin/pregnant') }}",
            type: 'POST',
            success: function(data){
                console.log(data);
                setTimeout(function(){
                    window.location.reload(false);
                },500);
            },
            error: function(){
                $('#serverModal').modal();
            }
        });

    });

    function sendNormalData(data)
    {
        console.log("ni sud!");
        if(data.id!=0){
            var form_data = {
                referring_name: referring_facility_name,
                patient_code: data.patient_code,
                name: name,
                age: age,
                sex: sex,
                date: data.referred_date,
                form_type: form_type,
                tracking_id: data.id,
                referring_md: referring_md,
                referred_from: referring_facility,
                department_id: department_id,
                department_name: department_name
            };
            var dbRef = firebase.database();
            var connRef = dbRef.ref('Referral');
            connRef.child(referred_facility).push(form_data);

            var data = {
                "to": "/topics/ReferralSystem"+referred_facility,
                "data": {
                    "subject": "New Referral",
                    "date": data.referred_date,
                    "body": name+" was referred to your facility from "+referring_facility_name+"!"
                }
            };
            $.ajax({
                url: 'https://fcm.googleapis.com/fcm/send',
                type: 'post',
                data: JSON.stringify(data),
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'key=AAAAJjRh3xQ:APA91bFJ3YMPNZZkuGMZq8MU8IKCMwF2PpuwmQHnUi84y9bKiozphvLFiWXa5I8T-lP4aHVup0Ch83PIxx8XwdkUZnyY-LutEUGvzk2mu_YWPar8PmPXYlftZnsJCazvpma3y5BI7QHP'
                },
                dataType: 'json',
                success: function (data) {
                    console.log(data);
                    console.info(data);
                    //window.location.reload(false);
                    setTimeout(function () {
                        console.log("Force refresh!");
                        window.location.reload(false);
                    },15000);
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    console.log("Status: " + textStatus); console.log("Error: " + errorThrown);
                }
            });
            connRef.on('child_added',function(data){
                setTimeout(function(){
                    connRef.child(data.key).remove();
                    window.location.reload(false);
                },500);
            });
        }else{
            console.log("error else");
            setTimeout(function(){
                window.location.reload(false);
            },500);
        }
        console.log("ni lahus sa last!");
    }

    @if(Session::get('patient_update_save'))
        Lobibox.notify('success', {
            title: "",
            msg: "<?php echo Session::get("patient_message"); ?>",
            size: 'mini',
            rounded: true
        });
    <?php
        Session::put("patient_update_save",false);
        Session::put("patient_message",false)
    ?>
    @endif

    @if(Session::get('remove_var'))
        Lobibox.notify('success', {
            title: "",
            msg: "<?php echo Session::get("remove_message"); ?>",
            size: 'mini',
            rounded: true
        });
    <?php
        Session::put("remove_var",false);
        Session::put("remove_message",false)
    ?>
    @endif


    
    @if(Session::get('vital_sign_update'))
        Lobibox.notify('success', {
            title: "",
            msg: "<?php echo Session::get("vital_update_message"); ?>",
            size: 'mini',
            rounded: true
        });
        $('#vital_modal').modal('show');

        var patient_id = "<?php echo Session::get("patient_id_unique"); ?>"
        var url = "<?php echo asset('doctor/patient/vitalbody'); ?>";
        var json = {
            "patient_id" : patient_id,
            "_token" : "<?php echo csrf_token(); ?>"
        };
        $.post(url,json,function(result){
            $(".vital_body").html(result);
        });
    <?php
        Session::put("vital_sign_update",false);
        Session::put("vital_update_message",false)
    ?>
    @endif

    @if(Session::get('vital_sign_save'))
        Lobibox.notify('success', {
            title: "",
            msg: "<?php echo Session::get("vital_sign_message"); ?>",
            size: 'mini',
            rounded: true
        });
        $('#vital_modal').modal('show');

        var patient_id = "<?php echo Session::get("patient_id_unique"); ?>"
        var url = "<?php echo asset('doctor/patient/vitalbody'); ?>";
        var json = {
            "patient_id" : patient_id,
            "_token" : "<?php echo csrf_token(); ?>"
        };
        $.post(url,json,function(result){
            $(".vital_body").html(result);
        });
    <?php
        Session::put("vital_sign_save",false);
        Session::put("vital_sign_message",false)
    ?>
    @endif

    @if(Session::get('physical_exam_save'))
        Lobibox.notify('success', {
            title: "",
            msg: "<?php echo Session::get("physical_exam_message"); ?>",
            size: 'mini',
            rounded: true
        });
        $('#vital_modal').modal('show');

        var patient_id = "<?php echo Session::get("patient_id_unique"); ?>"
        var url = "<?php echo asset('doctor/patient/vitalbody'); ?>";
        var json = {
            "patient_id" : patient_id,
            "_token" : "<?php echo csrf_token(); ?>"
        };
        $.post(url,json,function(result){
            $(".vital_body").html(result);
        });
        <?php
            Session::put("physical_exam_save",false);
            Session::put("physical_exam_message",false)
        ?>
    @endif

    @if(Session::get('refer_patient'))
        Lobibox.notify('success', {
            title: "Sucessfully",
            msg: "Referred Patient!"
        });
        <?php
        Session::put("refer_patient",false);
        ?>
    @endif

</script>
@endsection

