@extends('layouts.app')
@section('title', __( 'driver.driver' ))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'driver.driver' )
            <small>@lang( 'driver.manage_driver' )</small>
        </h1>
        <!-- <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
        </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">

        @component('components.filters', ['title' => __('report.filters')])
            @include('driver.partials.driver_list_filters')
        @endcomponent
        @component('components.widget', ['class' => 'box-primary', 'title' => __( 'driver.all_drivers' )])
            @can('driver.create')
                @slot('tool')
                    <div class="box-tools margin-r-5">
                        <a class="btn btn-block btn-primary"
                           href="{{action('DriverController@create')}}">
                            <i class="fa fa-plus"></i> @lang( 'messages.add' )</a>
                    </div>

                    <div class="box-tools margin-r-5">
                        <button type="button" class="btn btn-block btn-primary btn-modal"
                                data-href="{{action('DriverController@editAll')}}"
                                data-container=".driver_edit_modals">
                            <i class="fa fa-plus"></i> @lang( 'driver.edit_all_driver' )</button>
                    </div>
                    <div class="box-tools margin-r-5">
                        <a class="btn btn-block btn-primary"
                           href="{{action('DriverAttendenceController@index')}}">
                            @lang( 'driver.driver_attendence' )</a>
                    </div>

                @endslot
            @endcan
            @can('driver.view')
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="driver_table">
                        <thead>
                        <tr>
                            <th>@lang( 'driver.name' )</th>
                            <th>@lang( 'driver.email' )</th>
                            <th>@lang( 'driver.phone' )</th>
                            <th>@lang( 'driver.address_line_1' )</th>
                            <th>@lang( 'driver.address_line_2' )</th>
                            <th>@lang( 'driver.city' )</th>
                            <th>@lang( 'driver.state' )</th>
                            <th>@lang( 'driver.country' )</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            @endcan
        @endcomponent

        <div class="modal fade user_modal" tabindex="-1" role="dialog"
             aria-labelledby="gridSystemModalLabel">
        </div>

    </section>
    <div class="modal fade driver_edit_modals" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>
    <!-- /.content -->
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
