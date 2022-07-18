@foreach ($supplier_products as $supplier_product)

<tr>
    <td><span class="sr_number"></span></td>
    {{-- start::name --}}
    <td>
        {{ $supplier_product->name }} ({{$supplier_product->sku }})
    </td>
    
    {{-- end::name --}}

    {{-- start::Quantity --}}
    <td>
        @if(!empty($purchase_order_line))
            {!! Form::hidden('purchases[' . $row_count . '][purchase_order_line_id]', $purchase_order_line->id ); !!}
        @endif
        {!! Form::hidden('purchases[' . $row_count . '][product_id]', $supplier_product->id ); !!}

        @php
        // $check_decimal = 'false';
        // if($product->unit->allow_decimal == 0){
        //     $check_decimal = 'true';
        // }
        $currency_precision = config('constants.currency_precision', 2);
        $quantity_precision = config('constants.quantity_precision', 2);

        $quantity_value = !empty($purchase_order_line) ? $purchase_order_line->quantity : 1;
        $max_quantity   = !empty($purchase_order_line) ? $purchase_order_line->quantity - $purchase_order_line->po_quantity_purchased : 0;

        $quantity_value = !empty($imported_data) ? $imported_data['quantity'] : $quantity_value;
        @endphp

        <input type="text" 
        name="purchases[{{$row_count}}][quantity]" 
        value="{{@format_quantity($quantity_value)}}"
        class="form-control input-sm purchase_quantity input_number mousetrap"
        required
        {{-- data-rule-abs_digit={{$check_decimal}}
        data-msg-abs_digit="{{__('lang_v1.decimal_value_not_allowed')}}" --}}
        @if(!empty($max_quantity))
            data-rule-max-value="{{$max_quantity}}"
            data-msg-max-value="{{__('lang_v1.max_quantity_quantity_allowed', ['quantity' => $max_quantity])}}" 
        @endif
        >
        <input type="hidden" name="purchases[{{$row_count}}][product_unit_id]" value="{{$supplier_product->unit->id}}">

    </td>
    {{-- end::Quantity --}}


    {{-- start::purchase price before discount --}}
    <td>
        @php
            $pp_without_discount = !empty($purchase_order_line) ? $purchase_order_line->pp_without_discount/$purchase_order->exchange_rate : $supplier_product->purchase_price;

            $discount_percent = !empty($purchase_order_line) ? $purchase_order_line->discount_percent : 0;

            $purchase_price = !empty($purchase_order_line) ? $purchase_order_line->purchase_price/$purchase_order->exchange_rate :  $supplier_product->purchase_price;

            $tax_id = !empty($purchase_order_line) ? $purchase_order_line->tax_id : $supplier_product->tax;

            $tax_id = !empty($imported_data['tax_id']) ? $imported_data['tax_id'] : $tax_id;

            $pp_without_discount = !empty($imported_data['unit_cost_before_discount']) ? $imported_data['unit_cost_before_discount'] : $pp_without_discount;

            $discount_percent = !empty($imported_data['discount_percent']) ? $imported_data['discount_percent'] : $discount_percent;
        @endphp
        {!! Form::text('purchases[' . $row_count . '][pp_without_discount]',
        number_format($pp_without_discount, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input-sm purchase_unit_cost_without_discount input_number', 'required']); !!}
    </td>
    {{-- end::purchase price before discount --}}

    {{-- start::discount percent --}}
    <td>
        {!! Form::text('purchases[' . $row_count . '][discount_percent]', number_format($discount_percent, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input-sm inline_discounts input_number', 'required']); !!}
    </td>
    {{-- end::discount percent --}}

    {{-- start::purchase price (without tax)--}}
    <td>
        {!! Form::text('purchases[' . $row_count . '][purchase_price]',
        number_format($purchase_price, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input-sm purchase_unit_cost input_number', 'required']); !!}
    </td>
    {{-- end::purchase price (without tax)--}}

    {{-- start::subtotal before tax--}}
    <td class="">
        <span class="row_subtotal_before_tax display_currency">0</span>
        <input type="hidden" class="row_subtotal_before_tax_hidden" value=0>
    </td>
    {{-- end::subtotal before tax--}}

    {{-- start::Tax dropdown (inline tax)--}}
    <td class="">
        <div class="input-group">
            <select name="purchases[{{ $row_count }}][purchase_line_tax_id]" class="form-control select2 input-sm purchase_line_tax_id" placeholder="'Please Select'">
                <option value="" data-tax_amount="0"
                selected >@lang('lang_v1.none')</option>
                @foreach($taxes as $tax)
                    <option value="{{ $tax->id }}" data-tax_amount="{{ $tax->amount }}" @if( $tax->id == $supplier_product->tax) selected @endif >{{ $tax->name }}</option>
                @endforeach
            </select>
            {!! Form::hidden('purchases[' . $row_count . '][item_tax]', 0, ['class' => 'purchase_product_unit_tax']); !!}
            <span class="input-group-addon purchase_product_unit_tax_text">
                0.00</span>
        </div>
    </td>
    {!! Form::hidden('purchases[' . $row_count . '][item_tax]', $supplier_product->product_tax->amount ?? 0, ['class' => 'purchase_product_unit_tax']); !!}
     <span class="input-group-addon purchase_product_unit_tax_text">
    {{-- end::Tax dropdown (inline tax)--}}

    {{-- start:: Net cost--}}
    <td class="">
        @php
            $dpp_inc_tax = number_format($supplier_product->purchase_price_inc_tax, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator);
            $dpp_inc_tax = !empty($purchase_order_line) ? number_format($purchase_order_line->purchase_price_inc_tax/$purchase_order->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator) : $dpp_inc_tax;
        @endphp
        {!! Form::text('purchases[' . $row_count . '][purchase_price_inc_tax]', $dpp_inc_tax, ['class' => 'form-control input-sm purchase_unit_cost_after_tax input_number', 'required']); !!}
    </td>
    {{-- end:: Net cost--}}

    {{-- start::Line total--}}
    <td>
        <span class="row_subtotal_after_tax display_currency">0</span>
        <input type="hidden" class="row_subtotal_after_tax_hidden" value=0>
    </td>
    {{-- start::Line total--}}

    {{-- start::unit purchase price--}}
    <td class="">
        @php
            $dpp_inc_tax = number_format($supplier_product->purchase_price_inc_tax, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator);
            if($hide_tax == 'hide'){
                $dpp_inc_tax = number_format($supplier_product->purchase_price_inc_tax, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator);
            }

            $dpp_inc_tax = !empty($purchase_order_line) ? number_format($purchase_order_line->purchase_price_inc_tax/$purchase_order->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator) : $dpp_inc_tax;
        @endphp
        {!! Form::text('purchases[' . $row_count . '][sell_price_inc_tax]', $dpp_inc_tax, ['class' => 'form-control input-sm purchase_unit_cost_after_tax input_number', 'required']); !!}
    </td>
    <td><i class="fa fa-times remove_purchase_entry_row text-danger" title="Remove" style="cursor:pointer;"></i></td>

    {{-- end::unit purchase price--}}
        <?php $row_count++ ;?>


</tr>

<input type="hidden" id="row_count" value="{{ $row_count }}">
@endforeach