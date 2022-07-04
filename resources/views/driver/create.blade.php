@extends('layouts.app')

@section('title', __( 'driver.add_driver' ))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'driver.add_driver' )</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        {!! Form::open(['url' => action('DriverController@store'), 'method' => 'post', 'id' => 'driver_add_form' ]) !!}
        <div class="row">
            <div class="col-md-12">
                @component('components.widget')
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('name', __( 'driver.name' ) . ':*') !!}
                            {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'driver.name' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('email', __( 'driver.email' ) . ':*') !!}
                            {!! Form::text('email', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'driver.email' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('mobile', __('contact.mobile') . ':*') !!}
                            <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-mobile"></i>
                    </span>
                                {!! Form::text('phone', null, ['class' => 'form-control', 'required', 'placeholder' => __('contact.mobile'),'id' => "phone"]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address_line_1', __('lang_v1.address_line_1') . ':*') !!}
                            {!! Form::text('address_line_1', null, ['class' => 'form-control', 'required','placeholder' => __('lang_v1.address_line_1'), 'rows' => 3]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address_line_2', __('lang_v1.address_line_2') . ':') !!}
                            {!! Form::text('address_line_2', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.address_line_2'), 'rows' => 3]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('city', __('driver.city') . ':*') !!}
                            <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-map-marker"></i>
                  </span>
                                {!! Form::text('city', null, ['class' => 'form-control', 'required','placeholder' => __('driver.city')]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('state', __('driver.state') . ':*') !!}
                            <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-map-marker"></i>
                  </span>
                                {!! Form::text('state', null, ['class' => 'form-control','required', 'placeholder' => __('driver.state')]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('country', __('driver.country') . ':*') !!}
                            <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-globe"></i>
                  </span>
                                {!! Form::text('country', null, ['class' => 'form-control', 'required','placeholder' => __('driver.country')]); !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {{ Form::label('kitchen_location_id', __('kitchen.kitchen_locations') . ':*') }}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-globe"></i>
                                </span>
                                {!! Form::select('kitchen_location_id', $kitchens, null, ['placeholder' => 'Select Please', 'class' => 'form-control select2', 'required','style' => 'width:100%']); !!}
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('driver_type',__('driver.driver_type') . ':') !!}
                            {!! Form::select('driver_type', driverTypes(), null, ['placeholder' => 'Select Please', 'class' => 'form-control select2', 'required','style' => 'width:100%']); !!}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="checkbox">
                                <br/>
                                <label>
                                    {!! Form::checkbox('is_active', \App\Utils\AppConstant::STATUS_ACTIVE, true, ['class' => 'input-icheck status','required']); !!} {{ __('lang_v1.status_for_user') }}
                                </label>
                                @show_tooltip(__('lang_v1.tooltip_enable_user_active'))
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 text-center">
                <button type="submit" class="btn btn-primary btn-big"
                        id="submit_user_button">@lang( 'messages.save' )</button>
            </div>
        </div>
        {!! Form::close() !!}
        @stop
        @section('javascript')
            <script src="{{ asset('js/driver.js?v=' . $asset_v) }}"></script>
@endsection


