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
    {{Form::open(['url'=>route('supplier-products.update' , [$supplier_product->id]), 'method'=>'patch','id'=>'supplier-product_form','files' => true])}}
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    {{ Form::label('name', __('product.product_name') . ':*') }}
                    {{ Form::text('name', $supplier_product->name, ['class' => 'form-control', 'required',
                    'placeholder' => __('product.product_name')]); }}
                </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="form-group">
                {!! Form::label('sku', __('product.sku') . ':') !!} @show_tooltip(__('tooltip.sku'))
                {!! Form::text('sku', $supplier_product->sku, ['class' => 'form-control',
                  'placeholder' => __('product.sku')]); !!}
              </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-md-6 ">
              <div class="form-group d-block">
                 {{ Form::label('unit', __('product.unit') . ':') }} 
                 <div class="input-group">
                  {{ Form::select('unit_id', $units, $supplier_product->unit_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'style' => 'width:100%','id'=>'unit_id']); }}
                  <span class="input-group-btn">                   
                    <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{route('supplier-product-units.create',['quick_add' => true])}}" title="@lang('unit.add_unit')" data-container=".add_unit_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                  </span>
              </div>
              </div>
            </div>
            <div class="col-12 col-md-6 ">
              <div class="form-group d-block">
                 {{ Form::label('category', __('product.category') . ':') }}
                  <div class="input-group">
                    {{ Form::select('category_id', $categories, $supplier_product->category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'style' => 'width:100%','id'=>'category_id']); }}
                    <span class="input-group-btn">                   
                      <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{route('supplier-product-categories.create',['quick_add' => true])}}" title="@lang('category.add_category')" data-container=".add_category_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                    </span>
                  </div>
                </div>
            </div>
            <div class="col-12 col-md-6 ">
              <div class="form-group d-block">
                 {{ Form::label('brand', 'Brand' . ':') }}
                  <div class="input-group">
                    {{ Form::select('brand_id', $brands, $supplier_product->brand_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'style' => 'width:100%','id'=>'brand_id']); }}
                    <span class="input-group-btn">                   
                      <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{route('supplier-product-brands.create',['quick_add' => true])}}" title="Add Brands" data-container=".add_brand_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                    </span>
                  </div>
                </div>
              </div>
            <div class="col-12 col-md-6 ">
                <div class="form-group d-block">
                   {{ Form::label('weight','Weight' . ':') }}
                   {{ Form::text('weight',  $supplier_product->weight, ['class' => 'form-control',
                      'placeholder' => 'Weight']); }}
                  </div>
                </div>
                <div class="col-12 col-md-6" id="alert_quantity_div">
                  <div class="form-group">
                    {!! Form::label('alert_quantity',  __('product.alert_quantity') . ':') !!} @show_tooltip(__('tooltip.alert_quantity'))
                    {!! Form::text('alert_quantity',  $supplier_product->alert_quantity , ['class' => 'form-control input_number',
                    'placeholder' => __('product.alert_quantity'), 'min' => '0']); !!}
                  </div>
                </div>
          </div>
          <div class="row">
            <div class="col-12 col-md-6">
              <div class="form-group">
                {{ Form::label('image', __('lang_v1.product_image') . ':') }}
                {{ Form::file('supplier_product_image', ['id' => 'upload_supplier_image', 'accept' => 'image/*']); }}
                <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]) <br> @lang('lang_v1.aspect_ratio_should_be_1_1')</p></small>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="form-group">
                {!! Form::label('product_brochure', __('lang_v1.product_brochure') . ':') !!}
                {!! Form::file('product_brochure', ['id' => 'product_brochure', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                <small>
                    <p class="help-block">
                        @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                        @includeIf('components.document_help_text')
                    </p>
                </small>
              </div>
            </div>
            <div class="col-12 col-md-8">
                <div class="form-group">
                    {{ Form::label('description', __('lang_v1.product_description') . ':') }}
                    {{ Form::textarea('description',  $supplier_product->description, ['class' => 'form-control','rows'=>9]); }}
                </div>
            </div>
          </div>
          @component('components.widget', ['class' => 'box-primary'])
          <div class="row">
  
          @include('supplier-product.partials.supplier_edit_product_form_part')
          </div>
          <div class="clearfix"></div>
          @endcomponent
            <div class="text-center col-12">
              <button type="submit" value="submit" class="btn btn-primary float-right submit_supplier_product_form">@lang('messages.save')</button>
            </div>
    @endcomponent
    {{Form::close()}}
</section>

  <div class="modal fade add_unit_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
  <div class="modal fade add_category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
  <div class="modal fade add_brand_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
  <div class="modal fade supplier_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('supplier.create', ['quick_add' => true])
</div>
@endsection

@section('javascript')
<script src="{{ asset('js/supplier_purchase.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/supplier.js?v=' . $asset_v) }}"></script>
<script>
  $(document).ready(function() {
     //Start For product type single
  
      //If purchase price exc tax is changed
      $(document).on('change', 'input#purchase_price', function(e) {
          var purchase_exc_tax = __read_number($('input#purchase_price'));
          purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;
  
          var tax_rate = $('select#tax')
              .find(':selected')
              .data('rate');
          tax_rate = tax_rate == undefined ? 0 : tax_rate;
  
          var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
          __write_number($('input#purchase_price_inc_tax'), purchase_inc_tax);
          
      });
  
      //If tax rate is changed
      $(document).on('change', 'select#tax', function() {
              var purchase_exc_tax = __read_number($('input#purchase_price'));
              purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;
  
              var tax_rate = $('select#tax')
                  .find(':selected')
                  .data('rate');
              tax_rate = tax_rate == undefined ? 0 : tax_rate;
  
              var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
              __write_number($('input#purchase_price_inc_tax'), purchase_inc_tax);
      });
  
      //If purchase price inc tax is changed
      $(document).on('change', 'input#purchase_price_inc_tax', function(e) {
          var purchase_inc_tax = __read_number($('input#purchase_price_inc_tax'));
          purchase_inc_tax = purchase_inc_tax == undefined ? 0 : purchase_inc_tax;
  
          var tax_rate = $('select#tax')
              .find(':selected')
              .data('rate');
          tax_rate = tax_rate == undefined ? 0 : tax_rate;
  
          var purchase_exc_tax = __get_principle(purchase_inc_tax, tax_rate);
          __write_number($('input#purchase_price'), purchase_exc_tax);
          $('input#purchase_price').change();
  
          var profit_percent = __read_number($('#profit_percent'));
          profit_percent = profit_percent == undefined ? 0 : profit_percent;
          var selling_price = __add_percent(purchase_exc_tax, profit_percent);
          __write_number($('input#single_dsp'), selling_price);
  
      });
      });
  </script>
  <script>
    //Quick add brand
  $(document).on('submit', 'form#quick_add_brand_form', function(e) {
      e.preventDefault();
      var form = $(this);
      var data = form.serialize();
  
      $.ajax({
          method: 'POST',
          url: $(this).attr('action'),
          dataType: 'json',
          data: data,
          beforeSend: function(xhr) {
              __disable_submit_button(form.find('button[type="submit"]'));
          },
          success: function(result) {
              if (result.success == true) {
                  var newOption = new Option(result.data.name, result.data.id, true, true);
                  // Append it to the select
                  $('#brand_id')
                      .append(newOption)
                      .trigger('change');
                  $('div.add_brand_modal').modal('hide');
                  toastr.success(result.msg);
              } else {
                  toastr.error(result.msg);
              }
          },
      });
  });
  </script>
@stop
