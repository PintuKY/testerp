@extends('layouts.app')

@section('title', 'Supplier Product Category')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Supplier Product Category
        <small>
            {{ __( 'category.manage_your_categories' ) }}
        </small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
   
    @php
        $can_add = true;
        if(request()->get('type') == 'product' && !auth()->user()->can('category.create')) {
            $can_add = false;
        }
    @endphp
    @component('components.widget', ['class' => 'box-solid', 'can_add' => $can_add])
            @if($can_add)
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal"
                    data-href="{{action('SupplierProductCategoryController@create')}}"
                    data-container=".supplier_product_category_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="supplier_product_category_table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>@lang( 'lang_v1.description' )</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
    @endcomponent

    <div class="modal fade supplier_product_category_modal" tabindex="-1" role="dialog"
    	aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade update_supplier_product_category_modal" tabindex="-1" role="dialog"
     aria-labelledby="gridSystemModalLabel">
    </div>
    

</section>
<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/supplier-product-category.js?v=' . $asset_v) }}"></script>
@endsection
