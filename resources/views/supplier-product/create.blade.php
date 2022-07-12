@extends('layouts.app')
@section('title', __('product.add_new_product'))

@section('content')

<section class="content-header">
    <h1>Supplier Product</h1>
</section>

<!-- Main content -->
<section class="content">
  @if ($errors->any())
  <div class="alert alert-danger">
      <ul>
          @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
          @endforeach
      </ul>
  </div>
@endif
    {{Form::open(['url'=>'/supplier-products', 'method'=>'post','id'=>'supplier-product_form'])}}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    {{ Form::label('name', __('product.product_name') . ':*') }}
                    {{ Form::text('name', null, ['class' => 'form-control', 'required',
                    'placeholder' => __('product.product_name')]); }}
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="form-group">
                  {{ Form::label('supplier_id', __('purchase.supplier') . ':*') }}
                  <div class="input-group">
                      {{ Form::select('supplier_id', [], null, ['class' => 'form-control','style' => 'width:100%', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'supplier_id']); }}
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-default bg-white btn-flat add_new_supplier" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                      </span>
                  </div>
              </div>    
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-6 ">
              <div class="form-group d-block">
                 {{ Form::label('unit', __('product.unit') . ':') }} 
                 <div class="input-group">
                  {{ Form::select('unit_id', $units, null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'style' => 'width:100%','id'=>'unit_id']); }}
                  <span class="input-group-btn">                   
                    <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{route('supplierProductUnits.create')}}" title="@lang('unit.add_unit')" data-container=".add_unit_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                  </span>
              </div>
              </div>
              <div class="form-group d-block">
                 {{ Form::label('category', __('product.category') . ':') }}
                  <div class="input-group">
                    {{ Form::select('category_id', $categories, null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'style' => 'width:100%','id'=>'category_id']); }}
                    <span class="input-group-btn">                   
                      <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{route('supplierProductCategories.create')}}" title="@lang('category.add_category')" data-container=".add_category_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                    </span>
                  </div>
                </div>
              <div class="form-group">
                  {{ Form::label('purchase_price', 'Purchase Price'. ':') }}
                  {{ Form::text('purchase_price', null, ['class' => 'form-control', 'required',
                  'placeholder' => __('product.product_name')]); }}
               </div>
            </div>
            <div class="col-12 col-md-6 col-lg-6">
                <div class="form-group">
                    {{ Form::label('description', __('lang_v1.product_description') . ':') }}
                    {{ Form::textarea('description', null, ['class' => 'form-control','rows'=>9]); }}
                </div>
            </div>
            <div class="text-center col-12">
              <button type="submit" value="submit" class="btn btn-primary float-right submit_supplier_product_form">@lang('messages.save')</button>
            </div>
          </div>
    @endcomponent
    {{Form::close()}}
</section>

  <div class="modal fade add_unit_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
  <div class="modal fade add_category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
  <div class="modal fade supplier_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('supplier.create', ['quick_add' => true])
</div>
@endsection

@section('javascript')
<script src="{{ asset('js/supplier_purchase.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/supplier.js?v=' . $asset_v) }}"></script>
@stop




