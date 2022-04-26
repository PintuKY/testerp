@extends('layouts.app')
@section('title', __('api-setting.api_setting'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'api-setting.api_setting' )
        <small>@lang( 'api-setting.manage_your_api_setting' )</small>
    </h1>

    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'api-setting.all_your_api_setting')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal d-inline-block m-2 float-right"
                data-href="{{action('ApiController@create')}}"
                data-container=".api_modal">
                <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
            </div>

            <div class="box-tools">
                <button type="button" class="btn btn-info d-inline-block m-2 float-right" ><span aria-hidden="true">@lang( 'messages.sync' )</span></button>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="apisetting_table">
            <thead>
                <tr>
                    <th>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="row mx-md-n5">
                                    {!! Form::checkbox('checkbox', 1, false, ['class' => 'form-check-label ', 'id' => '']); !!}
                                </label>
                            </div>
                        </div>
                    </th>
                    <th>@lang('api-setting.consumer_key')</th>
                    <th>@lang('api-setting.consumer_secret')</th>
                    <th>@lang('api-setting.url')</th>
                    <th>@lang('api-setting.business_name')</th>
                    <th>@lang('sale.status')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>

        </table>
    @endcomponent

    <div class="modal fade api_modal" tabindex="-1" role="dialog"
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->


@endsection
