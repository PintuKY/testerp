<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('DriverController@updateAll'),  'id' => 'driver_edit_all_form' ]) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'driver.edit_driver' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">

                    <table class="table" id="table">
                        <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Name</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">Driver Type</th>
                            <th class="text-center">Attendance</th>
                            <th class="text-center">Attendance Date</th>
                            <th class="text-center">Half Day</th>
                            <th class="text-center">Leave Reason</th>
                            <th class="text-center">Leave Description</th>
                        </tr>
                        </thead>
                        <tbody>

                        @forelse($drivers as $driver_attendance)
                            @php
                                if($driver_attendance->is_half_day == \App\Utils\AppConstant::HALF_DAY_YES){
                                    $half_day = true;
                                }else{
                                    $half_day = false;
                                }

                                if($driver_attendance->in_or_out == \App\Utils\AppConstant::ATTENDANCE_IN){
                                    $attendance = true;
                                }else{
                                    $attendance = false;
                                }
                            @endphp
                            <input type="hidden" name="{{'drivers['.$driver_attendance->id.'][name]'}}"
                                   value="{{$driver_attendance->driver->name}}">
                            <input type="hidden" name="{{'drivers['.$driver_attendance->id.'][id]'}}"
                                   value="{{$driver_attendance->id}}">

                            <tr class="item{{$driver_attendance->driver->id}}">
                                <td>{{$driver_attendance->driver->id}}</td>
                                <td>{{$driver_attendance->driver->name}}</td>
                                <td>{{$driver_attendance->driver->email}}</td>
                                <td>{{getDriverType($driver_attendance->driver->driver_type)}}</td>
                                <td><label>
                                        {!! Form::checkbox('drivers['.$driver_attendance->id.'][in_or_out]', $driver_attendance->in_or_out, $attendance, ['class' => 'input-icheck status in_or_out']); !!} {{ __('driver.in') }}
                                    </label></td>
                                <td>{{$driver_attendance->attendance_date}}</td>
                                <td>
                                    <label>
                                        {!! Form::checkbox('drivers['.$driver_attendance->id.'][is_half_day]', $driver_attendance->is_half_day, $half_day, ['class' => 'input-icheck status is_half_day']); !!}
                                    </label>
                                </td>
                                <td>

                                    {!! Form::select('drivers['.$driver_attendance->id.'][leave_reason]', LeaveReasonTypes(), $driver_attendance->leave_reason, ['class' => 'input-icheck status leave_reason']); !!}
                                </td>
                                <td>{!! Form::textarea('drivers['.$driver_attendance->id.'][leave_reason_description]', $driver_attendance->leave_reason_description, ['class' => 'form-control', 'rows' => 3]); !!}
                                </td>
                            </tr>
                        @empty
                            @lang('purchase.no_records_found')
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" id="submit_user_button">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
