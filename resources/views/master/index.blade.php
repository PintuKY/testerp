@extends('layouts.app')
@section('title', __('lang_v1.master_list'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('lang_v1.master_list')
        </h1>
    </section>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.master_list')])
        @include('master.partials.master_list')
    @endcomponent

    <!-- Main content -->
    {{-- <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sell_list_filter_location_id',  __('purchase.business_location') . ':') !!}

                    {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sell_list_filter_customer_id',  __('contact.customer') . ':') !!}
                    {!! Form::select('sell_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                </div>
            </div>
            @can('access_sell_return')
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('created_by',  __('report.user') . ':') !!}
                    {!! Form::select('created_by', $sales_representative, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                </div>
            </div>
            @endcan
        @endcomponent
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.sell_return')])
            @include('sell_return.partials.sell_return_list')
        @endcomponent
        <div class="modal fade payment_modal" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog"
            aria-labelledby="gridSystemModalLabel">
        </div>
    </section> --}}

    <!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/master_list.js?v=' . $asset_v) }}"></script>
@endsection
