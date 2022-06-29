@extends('layouts.app')

@section('title', __( 'driver.driver_attendence' ))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'driver.driver_attendence' )</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        @component('components.filters', ['title' => __('report.filters')])
            {{--<div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('select_date', __('driver.select_date') . ':*') !!}
                    <div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
                        {!! Form::text("select_date", $default_date, ['class' => 'form-control select_date', 'required']); !!}
                    </div>
                </div>

            </div>--}}
            @if(empty($only) || in_array('driver_list_filter_date_range', $only))
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('driver_list_filter_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('driver_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                    </div>
                </div>
            @endif
        @endcomponent

        @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.all_sales')])

                @slot('tool')
                    <div class="box-tools">
                        <a class="btn btn-block btn-primary" href="{{action('DriverController@create')}}">
                            <i class="fa fa-plus"></i> @lang('messages.add')</a>
                    </div>
                @endslot

            <table class="table table-bordered table-striped ajax_view" id="driver_attendence">
                <thead>
                <tr>
                    <th class="text-center">@lang('driver.name')</th>
                    <th class="text-center">@lang('driver.email')</th>
                    <th class="text-center">@lang('driver.driver_type')</th>
                    <th class="text-center">@lang('driver.attendence')</th>
                    <th class="text-center">@lang('driver.attendence_date')</th>
                    <th class="text-center">@lang('driver.half_day')</th>
                    <th class="text-center">@lang('driver.leave_reason')</th>
                    <th class="text-center">@lang('driver.leave_reason_description')</th>
                    <th class="text-center">@lang('driver.action')</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        @endcomponent
    </section>
@stop
@section('javascript')
    <script src="{{ asset('js/driver.js?v=' . $asset_v) }}"></script>
@endsection
