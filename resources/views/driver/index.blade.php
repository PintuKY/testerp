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
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'driver.all_drivers' )])
        @can('driver.create')
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary"
                    href="{{action('DriverController@create')}}" >
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</a>
                 </div>
            @endslot
        @endcan
        @can('driver.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="driver_table">
                    <thead>
                        <tr>
                            <th>@lang( 'name' )</th>
                            <th>@lang( 'email' )</th>
                            <th>@lang( 'phone' )</th>
                            <th>@lang( 'address_line_1' )</th>
                            <th>@lang( 'address_line_2' )</th>
                            <th>@lang( 'city' )</th>
                            <th>@lang( 'state' )</th>
                            <th>@lang( 'country' )</th>
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
<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/driver.js?v=' . $asset_v) }}"></script>
@endsection
