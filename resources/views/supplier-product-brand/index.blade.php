@extends('layouts.app')
@section('title', 'Brands')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Brands
        <small>Manage your brands</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => 'All your brands'])
        @can('brand.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                        data-href="{{action('SupplierProductBrandController@create')}}" 
                        data-container=".brands_modal">
                        <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
        @endcan
        @can('brand.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="brands_table">
                    <thead>
                        <tr>
                            <th>Brands</th>
                            <th>Note</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade brands_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection
