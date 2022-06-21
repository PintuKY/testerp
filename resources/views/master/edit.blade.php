@extends('layouts.app')

@section('title', __( 'master.edit_master_list' ))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'master.edit_master_list' )</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        {!! Form::open(['url' => action('MasterController@update', [$master_list->id]), 'method' => 'PUT', 'id' => 'master_list_edit_form' ]) !!}
        <div class="row">

            @component('components.widget')
                <div class="col-md-12">
                    <input type="hidden" id="master_list_hidden_id" value="{{ $master_list->id }}">
                    <input type="hidden" id="status" name="status" value="1">
                    <div class="col-md-4">
                        <label for="time_slot">{{__('master.cancel_reason')}}:*</label>
                        <div class="form-group">
                            <select class="form-control select2" id="cancel_reason"
                                    name="cancel_reason">
                                <option selected>please select</option>
                                @foreach(reasonCancelOrder() as $key => $types)
                                    <option
                                        value="{{$key}}"
                                        @if($master_list->cancel_reason == $key) selected @endif>{{ $types }}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('contacts_name', __('master.contacts_name') . ':*' ) !!}

                            {!! Form::text('contacts_name', $master_list->contacts_name, ['class' => 'form-control','placeholder' => __('master.contacts_name'),'readonly', 'required']); !!}

                        </div>
                    </div>

                </div>

                <div class="clearfix"></div>

                <div class="col-md-12">
                    <input type="hidden" id="master_list_hidden_id" value="{{ $master_list->id }}">
                    <input type="hidden" id="status" name="status" value="1">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_address_line_1', __('master.shipping_address_line_1') . ':*' ) !!}

                            {!! Form::text('shipping_address_line_1', $master_list->shipping_address_line_1, ['class' => 'form-control','placeholder' => __('master.shipping_address_line_1'),'readonly', 'required']); !!}

                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_address_line_2', __('master.shipping_address_line_2') . ':*' ) !!}

                            {!! Form::text('shipping_address_line_2', $master_list->shipping_address_line_2, ['class' => 'form-control','placeholder' => __('master.shipping_address_line_2'),'readonly', 'required']); !!}

                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_city', __('master.shipping_city') . ':*' ) !!}

                            {!! Form::text('shipping_city', $master_list->shipping_city, ['class' => 'form-control','placeholder' => __('master.shipping_city'),'readonly', 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_state', __('master.shipping_state') . ':*' ) !!}

                            {!! Form::text('shipping_state', $master_list->shipping_state, ['class' => 'form-control','placeholder' => __('master.shipping_state'),'readonly', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_country', __('master.shipping_country') . ':*' ) !!}

                            {!! Form::text('shipping_country', $master_list->shipping_country, ['class' => 'form-control','placeholder' => __('master.shipping_country'),'readonly', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_zip_code', __('master.shipping_zip_code') . ':*' ) !!}

                            {!! Form::text('shipping_zip_code', $master_list->shipping_zip_code, ['class' => 'form-control','placeholder' => __('master.shipping_zip_code'),'readonly', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_phone', __('master.shipping_phone') . ':*' ) !!}

                            {!! Form::text('shipping_phone', $master_list->shipping_phone, ['class' => 'form-control','placeholder' => __('master.shipping_phone'),'readonly', 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('staff_notes', __('master.staff_notes') . ':*' ) !!}

                            {!! Form::text('staff_notes', $master_list->staff_notes, ['class' => 'form-control','placeholder' => __('master.staff_notes'),'readonly', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('additional_notes', __('master.additional_notes') . ':*' ) !!}

                            {!! Form::text('additional_notes', $master_list->additional_notes, ['class' => 'form-control','placeholder' => __('master.additional_notes'),'readonly', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('delivery_notes', __('master.delivery_notes') . ':*' ) !!}

                            {!! Form::text('delivery_notes', $master_list->delivery_notes, ['class' => 'form-control','placeholder' => __('master.delivery_notes'),'readonly', 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="col-md-4">
                        <div class="form-group">

                            <label for="compensate">{{__('master.compensate')}}:*</label>
                            <div class="form-group">
                                <select class="form-control select2" id="compensate"
                                        name="compensate"
                                        required disabled>
                                    <option selected>please select</option>
                                    @foreach(compensateTypes() as $key => $compensate)
                                        <option value="{{$key}}" @if($master_list->is_compensate == $key) selected @endif>{{ $compensate }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="time_slot">Meal Type:*</label>
                            <div class="form-group">
                                <select class="form-control select2" id="time_slot"
                                        name="time_slot"
                                        required disabled>
                                    <option selected>please select</option>
                                    @foreach(mealTypes() as $key => $deliveryDays)
                                        <option value="{{$key}}" @if($master_list->time_slot == $key) selected @endif>{{ $deliveryDays }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        </div>
                    </div>
                </div>
            @endcomponent

        </div>
        <div class="row">
            <div class="col-md-12 text-center">
                <button type="submit" class="btn btn-primary btn-big"
                        id="submit_user_button">@lang( 'messages.update' )</button>
            </div>
        </div>
        {!! Form::close() !!}
    </section>
        @stop
        @section('javascript')
            <script src="{{ asset('js/driver.js?v=' . $asset_v) }}"></script>
@endsection
