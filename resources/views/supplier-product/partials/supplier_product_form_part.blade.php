@if(!session('business.enable_price_tax'))
  @php
    $default = 0;
    $class = 'hide';
  @endphp
@else
  @php
    $default = null;
    $class = '';
  @endphp
@endif

<div class="table-responsive">
    <table class="table table-bordered add-product-price-table table-condensed {{$class}}">
        <tr>
          <th>Tax</th>
          <th>@lang('product.default_purchase_price')</th>
          <th>Purchase price including Tax</th>
        </tr>
        <tr>
          <td>
            <div class="col-12 @if(!session('business.enable_price_tax')) hide @endif">
              <div class="form-group">
                {!! Form::label('tax', 'Tax' . ':') !!} 
                {!! Form::select('tax', $taxes, null, ['placeholder' => __('messages.please_select'),'id'=>'tax', 'style'=>"width:100%" ,'class' => 'form-control select2'], $tax_attributes); !!}
              </div>
            </div>
          </td>
          <td>
            <div class="col-12">
              <div class="form-group">
              {!! Form::label('purchase_price', trans('product.exc_of_tax') . ':*') !!}
              {!! Form::text('purchase_price', $default, ['class' => 'form-control input-sm dpp input_number', 'placeholder' => __('product.exc_of_tax'), 'required']); !!}
            </div>
          </div>
        </td>
          <td>
            <div class="col-12">
              <div class="form-group">
              {!! Form::label('purchase_price_inc_tax', trans('product.inc_of_tax') . ':*') !!}
              {!! Form::text('purchase_price_inc_tax', $default, ['class' => 'form-control input-sm dpp_inc_tax input_number', 'placeholder' => __('product.inc_of_tax'), 'required']); !!}
            </div>
          </div>
          </td>
        </tr>
    </table>
</div>
