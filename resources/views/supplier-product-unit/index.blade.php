@extends('layouts.app')
@section('title', __( 'unit.units' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'unit.units' )
        <small>@lang( 'unit.manage_your_units' )</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'unit.all_your_units' )])
        @can('unit.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-block btn-primary btn-modal"  data-href="{{route('supplier-product-units.create')}}" title="@lang('unit.add_unit')" data-container=".add_unit_modal"><i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
        @endcan
        @can('unit.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" style="width:100%"id="supplier_product_unit_table">
                    <thead>
                        <tr>
                            <th>@lang( 'unit.name' )</th>
                            <th>@lang( 'unit.short_name' )</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
        <div class="modal fade add_unit_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
        <div class="modal fade edit_unit_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    @endcomponent



</section>
<!-- /.content -->
@endsection

@section('javascript')
<script src="{{ asset('js/supplier-product-unit.js?v=' . $asset_v) }}"></script>

@endsection
