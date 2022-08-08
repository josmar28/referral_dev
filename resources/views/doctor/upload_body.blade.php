<form action="{{ url('doctor/uploadfile') }}" method="POST" class="form-submit" enctype="multipart/form-data">
{{ csrf_field() }}
        <input type="text" value="@if(isset($code)) {{$code}} @endif" class="form-submit" disabled>
        <input type="hidden" name="refer_code" value="@if(isset($code)) {{$code}} @endif" class="form-submit">
        
    <hr>
    <table>
        <thead>
            <tr>
                <th>File</th>
                <th>File Type</th>
            </tr>
        </thead>
        <tbody class="add_col form-submit">
            <tr>
                <td><input type="file" name="file[]" class="form-submit"></td>
                <?php
                    $type = \App\Filetypes::all();
                ?>
                <td>
                     <select name="file_type[]" class="form-control">
                     <option value ="">Select...</option>
                    @foreach($type as $row)
                      <option value="{{ $row->id }}">{{ $row->description }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
        </tbody>
</table>
    <input type="button" class="add-row" value="Add Row">
    <input type="submit" value="Submit">
    </form>
    <hr>
<div class="table-responsive">
                    <table class="table table-striped " id="test"  style="white-space:nowrap;">
                        <tbody>
                        <tr>
                            <th>Name</th>
                            <th>File Type</th>
                            <th>Date By</th>
                            <th>Date Uploaded</th>
                        </tr>   
                 
                   @foreach($data as $row)                      
                        <tr>
                            <td>
                            <a target="_blank" href="{{ url('doctor/fileView/'.$row->id) }}"
                                       data-toggle="modal"
                                       class="btn btn-info btn-xs">
                                       <i class="fa fa-file"></i>
                                       {{$row->name}}
                                    </a>
                                    <a href="{{ url('doctor/fileDelete/'.$row->id) }}"
                                       data-toggle="modal"
                                       class="btn btn-danger btn-xs">
                                       <i class="fa fa-remove"></i>
                                       Delete
                                    </a>
                            </td>
                            <td>
                                {{$row->file_type}}
                            </td>
                            <td>
                                {{$row->fname}} {{$row->mname}} {{$row->lname}}
                            </td>
                            <td>
                                {{$row->uploaded_date}}
                            </td>
                        </tr>
                        @endforeach
        
                        </tbody>
                        </table>
                        </div>
<script>

    $(document).ready(function(){
        $(".add-row").click(function(){
           <?php
             $type = \App\Filetypes::all();?>
            var markup = "<tr><td><input type='file' name='file[]'></td><td><select name='file_type[]' class='form-control'><option value =''>Select...</option> @foreach($type as $row) <option value='{{ $row->id }}'>{{ $row->description }}</option>  @endforeach</select></td></tr>";
            $(".add_col").append(markup);
        });
        
        $(".delete-row").click(function(){
            $("table tbody").find('input[name="record"]').each(function(){
                if($(this).is(":checked")){
                    $(this).parents("tr").remove();
                }
            });
        });
    });    
</script>