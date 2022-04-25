@extends('layouts.app')
@section('title', __('kitchen.kitchen_locations'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'kitchen.kitchen_locations' )
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'kitchen.all_your_kitchen_locations' )])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal"
                    data-href="{{action('KitchenLocationController@create')}}"
                    data-container=".kitchen_location_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="kitchen_location_table">
                <thead>
                    <tr>
                        <th>@lang( 'invoice.name' )</th>
                        <th>@lang( 'business.landmark' )</th>
                        <th>@lang( 'business.city' )</th>
                        <th>@lang( 'business.state' )</th>
                        <th>@lang( 'business.country' )</th>
                        <th>@lang( 'business.zip_code' )</th>
                        <th>@lang('business.email')</th>
                        <th>@lang('business.mobile')</th>
                        <th>@lang( 'business.alternate_number')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade kitchen_location_modal" tabindex="-1" role="dialog"
    	aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade kitchen_location_edit_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection
