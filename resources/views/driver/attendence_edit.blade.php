@extends('layouts.app')

@section('title', __( 'driver.edit_driver_attendence' ))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'driver.edit_driver_attendence' )</h1>
    </section>
    @php
        if($driver->is_half_day == \App\Utils\AppConstant::HALF_DAY_YES){
            $half_day = true;
        }else{
            $half_day = false;
        }

        if($driver->in_or_out == \App\Utils\AppConstant::ATTENDANCE_IN){
            $attendance = true;
        }else{
            $attendance = false;
        }
    @endphp
    <!-- Main content -->
    <section class="content">
        {!! Form::open(['url' => action('DriverAttendenceController@update', [$driver->id]), 'method' => 'PUT', 'id' => 'driver_edit_form' ]) !!}
        <div class="row">
            <div class="col-md-12">
                <input type="hidden" id="driver_hidden_id" value="{{ $driver->id }}">
                @component('components.widget')
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('name', __( 'driver.name' ) . ':*') !!}
                            {!! Form::text('name', $driver->driver->name, ['class' => 'form-control', 'readonly', 'placeholder' => __( 'driver.name' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('driver_type',__('driver.driver_type') . ':') !!}
                            {!! Form::select('driver_type', driverTypes(), $driver->driver->driver_type, ['placeholder' => 'Select Please', 'class' => 'form-control select2', 'disabled','style' => 'width:100%']); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('attendence_date',__('driver.attendence_date') . ':') !!}
                            <div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span> {!! Form::text("attendence_date", $driver->attendance_date, ['class' => 'form-control attendence_date', 'required']); !!}
                            </div>
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('leave_reason',__('driver.leave_reason') . ':') !!}
                            {!! Form::select('leave_reason', LeaveReasonTypes(), $driver->leave_reason, ['class' => 'form-control select2 leave_reason','style' => 'width:100%']); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('in_or_out',__('driver.in_out') . ':') !!}
                            {!! Form::checkbox('in_or_out', $driver->in_or_out, $attendance, ['class' => 'status in_or_out']); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('is_half_day',__('driver.half_day') . ':') !!}
                            {!! Form::checkbox('is_half_day', $driver->is_half_day, $half_day, ['class' => 'status is_half_day']); !!}
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('leave_reason_description',__('driver.leave_reason_description') . ':') !!}
                            {!! Form::textarea('leave_reason_description', $driver->leave_reason_description, ['class' => 'form-control', 'rows' => 3]); !!}
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-center">
                <button type="submit" class="btn btn-primary btn-big"
                        id="submit_user_button">@lang( 'messages.update' )</button>
            </div>
        </div>
        {!! Form::close() !!}
        @stop
        @section('javascript')
            <script src="{{ asset('js/driver.js?v=' . $asset_v) }}"></script>
            <script>
                var half_day_yes = '{{\App\Utils\AppConstant::HALF_DAY_YES}}';
                var half_day_no = '{{\App\Utils\AppConstant::HALF_DAY_NO}}';
                $(document).on('change', '.is_half_day', function () {
                    var checkbox = $(this), // Selected or current checkbox
                        value = checkbox.val(); // Value of checkbox

                    if (checkbox.is(':checked')) {
                        $(this).val(half_day_yes);
                    } else {
                        $(this).val(half_day_no);
                    }
                });
                var attendance_in = '{{\App\Utils\AppConstant::ATTENDANCE_IN}}';
                var attendance_out = '{{\App\Utils\AppConstant::ATTENDANCE_OUT}}';
                $(document).on('change', '.in_or_out', function () {
                    var checkbox = $(this), // Selected or current checkbox
                        value = checkbox.val(); // Value of checkbox

                    if (checkbox.is(':checked')) {
                        $(this).val(attendance_in);
                    } else {
                        $(this).val(attendance_out);
                    }
                });
            </script>
@endsection
