@php
    $common_settings = session()->get('business.common_settings');
    $multiplier = 1;
@endphp

@foreach($sub_units as $key => $value)
    @if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key)
        @php
            $multiplier = $value['multiplier'];
        @endphp
    @endif
@endforeach
@if(empty($product->quantity))
    @php
        $product->quantity = 1;
    @endphp
@endif

@php
    $allow_decimal = true;
    if($product->sub_unit && $product->sub_unit->allow_decimal != 1) {
        $allow_decimal = false;
    }
@endphp

@if(!empty($varients->id))
    <input type="hidden" name="products[{{$varients->id}}][transaction_sell_lines_id]" class="form-control"
           value="{{$varients->id}}">
@endif
<input type="hidden" name="products[{{$varients->id}}][product_id]" class="form-control product_id"
       value="{{$product->product_id}}">

<input type="hidden" class="form-control product_variation_value input_number mousetrap input_quantity" readonly
       value="{{($varients->value) ? $varients->value : 'None'}}" name="products[{{$product->variation_id}}][variation_value]">


<tr class="product_row" data-row_index="{{$row_count}}" data-productId="{{$product->product_id}}"
    @if(!empty($so_line)) data-so_id="{{$so_line->transaction_id}}" @endif>
    <td>
        @if(!empty($so_line))
            <input type="hidden"
                   name="products[{{$product->variation_id}}][so_line_id]"
                   value="{{$so_line->id}}">
        @endif


        @if( ($edit_price || $edit_discount) && empty($is_direct_sell) )
            <div title="@lang('lang_v1.pos_edit_product_price_help')">
		<span class="text-link text-info cursor-pointer" data-toggle="modal"
              data-target="#row_edit_product_price_modal_{{$row_count}}">
			{!! $varients->pax !!}
			&nbsp;<i class="fa fa-info-circle"></i>
		</span>
            </div>
        @else
            {!! $varients->pax !!}
        @endif
        <input type="hidden" class="enable_sr_no" value="{{$product->enable_sr_no}}">
        <input type="hidden"
               class="product_type"
               name="products[{{$product->variation_id}}][product_type]"
               value="{{$product->product_type}}">

        @php
            $hide_tax = 'hide';
            if(session()->get('business.enable_inline_tax') == 1){
                $hide_tax = '';
            }

            $tax_id = $product->tax_id;
            $item_tax = !empty($product->item_tax) ? $product->item_tax : 0;
            $unit_price_inc_tax = $product->unit_price_inc_tax;

            if(!empty($so_line)) {
                $tax_id = $so_line->tax_id;
                $item_tax = $so_line->item_tax;
            }

            if($hide_tax == 'hide'){
                $tax_id = null;
                $unit_price_inc_tax = $product->unit_price;
            }

            $discount_type = !empty($product->line_discount_type) ? $product->line_discount_type : 'fixed';
            $discount_amount = !empty($product->line_discount_amount) ? $product->line_discount_amount : 0;

            if(!empty($discount)) {
                $discount_type = $discount->discount_type;
                $discount_amount = $discount->discount_amount;
            }

            if(!empty($so_line)) {
                $discount_type = $so_line->line_discount_type;
                $discount_amount = $so_line->line_discount_amount;
            }

              $sell_line_note = '';
              if(!empty($product->sell_line_note)){
                  $sell_line_note = $product->sell_line_note;
              }
        @endphp

        @if(!empty($discount))
            {!! Form::hidden("products[$row_count][discount_id]", $discount->id); !!}
        @endif

        @php
            if($discount_type == 'fixed') {
                $discount_amount = $discount_amount * $multiplier;
            }
        @endphp

        @if(empty($is_direct_sell))
            <div class="modal fade row_edit_product_price_model" id="row_edit_product_price_modal_{{$row_count}}"
                 tabindex="-1" role="dialog">
                @include('sale_pos.partials.row_edit_product_price_modal')
            </div>
        @endif

        <!-- Description modal end -->
        @if(in_array('modifiers' , $enabled_modules))
            <div class="modifiers_html">
                @if(!empty($product->product_ms))
                    @include('restaurant.product_modifier_set.modifier_for_product', array('edit_modifiers' => true, 'row_count' => $loop->index, 'product_ms' => $product->product_ms ) )
                @endif
            </div>
        @endif

        @php
            $max_quantity = $product->qty_available;
            $formatted_max_quantity = $product->formatted_qty_available;

            if(!empty($action) && $action == 'edit') {
                if(!empty($so_line)) {
                    $qty_available = $so_line->quantity - $so_line->so_quantity_invoiced + $product->quantity;
                    $max_quantity = $qty_available;
                    $formatted_max_quantity = number_format($qty_available, config('constants.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']);
                }
            } else {
                if(!empty($so_line) && $so_line->qty_available <= $max_quantity) {
                    $max_quantity = $so_line->qty_available;
                    $formatted_max_quantity = $so_line->formatted_qty_available;
                }
            }


            $max_qty_rule = $max_quantity;
            $max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $formatted_max_quantity, 'unit' => $product->unit  ]);
        @endphp

        @if( session()->get('business.enable_lot_number') == 1 || session()->get('business.enable_product_expiry') == 1)
            @php
                $lot_enabled = session()->get('business.enable_lot_number');
                $exp_enabled = session()->get('business.enable_product_expiry');
                $lot_no_line_id = '';
                if(!empty($product->lot_no_line_id)){
                    $lot_no_line_id = $product->lot_no_line_id;
                }
            @endphp
            @if(!empty($product->lot_numbers) && empty($is_sales_order))
                <select class="form-control lot_number input-sm" name="products[{{$row_count}}][lot_no_line_id]"
                        @if(!empty($varients->id)) disabled @endif>
                    <option value="">@lang('lang_v1.lot_n_expiry')</option>
                    @foreach($product->lot_numbers as $lot_number)
                        @php
                            $selected = "";
                            if($lot_number->purchase_line_id == $lot_no_line_id){
                                $selected = "selected";

                                $max_qty_rule = $lot_number->qty_available;
                                $max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $lot_number->qty_formated, 'unit' => $product->unit  ]);
                            }

                            $expiry_text = '';
                            if($exp_enabled == 1 && !empty($lot_number->exp_date)){
                                if( \Carbon\Carbon::now()->gt(\Carbon\Carbon::createFromFormat('Y-m-d', $lot_number->exp_date)) ){
                                    $expiry_text = '(' . __('report.expired') . ')';
                                }
                            }

                            //preselected lot number if product searched by lot number
                            if(!empty($purchase_line_id) && $purchase_line_id == $lot_number->purchase_line_id) {
                                $selected = "selected";

                                $max_qty_rule = $lot_number->qty_available;
                                $max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $lot_number->qty_formated, 'unit' => $product->unit  ]);
                            }
                        @endphp
                        <option value="{{$lot_number->purchase_line_id}}"
                                data-qty_available="{{$lot_number->qty_available}}"
                                data-msg-max="@lang('lang_v1.quantity_error_msg_in_lot', ['qty'=> $lot_number->qty_formated, 'unit' => $product->unit  ])" {{$selected}}>@if(!empty($lot_number->lot_number) && $lot_enabled == 1)
                                {{$lot_number->lot_number}}
                            @endif @if($lot_enabled == 1 && $exp_enabled == 1)
                                -
                            @endif @if($exp_enabled == 1 && !empty($lot_number->exp_date))
                                @lang('product.exp_date'): {{@format_date($lot_number->exp_date)}}
                            @endif {{$expiry_text}}</option>
                    @endforeach
                </select>
            @endif
        @endif
        {{-- @if(!empty($is_direct_sell))
              <br>
              <textarea class="form-control" name="products[{{$row_count}}][sell_line_note]" rows="2">{{$sell_line_note}}</textarea>
              <p class="help-block"><small>@lang('lang_v1.sell_line_description_help')</small></p>
        @endif --}}
    </td>
    <div class="input-group input-number hide">
        <input type="hidden" name="products[{{$product->variation_id}}][product_unit_id]" value="{{$product->unit_id}}">
        @if(count($sub_units) > 0)
            <br>
            <select name="products[{{$product->variation_id}}][sub_unit_id]" class="hide form-control input-sm sub_unit">
                @foreach($sub_units as $key => $value)
                    <option value="{{$key}}" data-multiplier="{{$value['multiplier']}}"
                            data-unit_name="{{$value['name']}}" data-allow_decimal="{{$value['allow_decimal']}}"
                            @if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key) selected @endif>
                        {{$value['name']}}
                    </option>
                @endforeach
            </select>
       {{-- @else
            {{$product->unit}}--}}
        @endif

        <input type="hidden" class="base_unit_multiplier"
               name="products[{{$product->variation_id}}][base_unit_multiplier]" value="{{$multiplier}}">

        <input type="hidden" class="hidden_base_unit_sell_price" value="{{$product->unit_price / $multiplier}}">

        {{-- Hidden fields for combo products --}}
        @if($product->product_type == 'combo'&& !empty($product->combo_products))

            @foreach($product->combo_products as $k => $combo_product)

                @if(isset($action) && $action == 'edit')
                    @php
                        $combo_product['qty_required'] = $combo_product['quantity'] / $product->quantity;

                        $qty_total = $combo_product['quantity'];
                    @endphp
                @else
                    @php
                        $qty_total = $combo_product['qty_required'];
                    @endphp
                @endif

                <input type="hidden"
                       name="products[{{$product->variation_id}}][combo][{{$k}}][product_id]"
                       value="{{$combo_product['product_id']}}">

                <input type="hidden"
                       name="products[{{$product->variation_id}}][combo][{{$k}}][variation_id]"
                       value="{{$combo_product['variation_id']}}">

                <input type="hidden"
                       class="combo_product_qty"
                       name="products[{{$product->variation_id}}][combo][{{$k}}][quantity]"
                       data-unit_quantity="{{$combo_product['qty_required']}}"
                       value="{{$qty_total}}">

                @if(isset($action) && $action == 'edit')
                    <input type="hidden"
                           name="products[{{$product->variation_id}}][combo][{{$k}}][transaction_sell_lines_id]"
                           value="{{$combo_product['id']}}">
                @endif

            @endforeach
        @endif
        {{-- <span class="input-group-btn"><button type="button" class="btn btn-default btn-flat quantity-up"><i class="fa fa-plus text-success"></i></button></span> --}}
    </div>


    @if(!empty($is_direct_sell))
        @if(!empty($pos_settings['inline_service_staff']))
            <td>
                <div class="form-group">
                    <div class="input-group">
                        {!! Form::select("products[" . $product->variation_id . "][res_service_staff_id]", $waiters, !empty($product->res_service_staff_id) ? $product->res_service_staff_id : null, ['class' => 'form-control select2 order_line_service_staff', 'placeholder' => __('restaurant.select_service_staff'), 'required' => (!empty($pos_settings['is_service_staff_required']) && $pos_settings['is_service_staff_required'] == 1) ? true : false ]); !!}
                    </div>
                </div>
            </td>
        @endif
        @php
            $pos_unit_price = !empty($product->unit_price_before_discount) ? $product->unit_price_before_discount : $product->unit_price;

            if(!empty($so_line)) {
                $pos_unit_price = $so_line->unit_price_before_discount;
            }
        @endphp
        {{--<td class="@if(!auth()->user()->can('edit_product_price_from_sale_screen')) hide @endif">
            <input type="text" name="products[{{$product->variation_id}}][unit_price]" readonly class="form-control product_pos_unit_price input_number mousetrap" value="{{@num_format($pos_unit_price)}}" @if(!empty($pos_settings['enable_msp'])) data-rule-min-value="{{$pos_unit_price}}" data-msg-min-value="{{__('lang_v1.minimum_selling_price_error_msg', ['price' => @num_format($pos_unit_price)])}}" @endif>
        </td>--}}
        {{--<td @if(!$edit_discount) class="hide" @endif>
            {!! Form::text("products[$product->variation_id][line_discount_amount]", @num_format($discount_amount), ['class' => 'form-control input_number row_discount_amount','readonly']); !!}<br>
            {!! Form::select("products[$product->variation_id][line_discount_type]", ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')], $discount_type , ['class' => 'form-control row_discount_type','readonly']); !!}
            @if(!empty($discount))
                <p class="help-block">{!! __('lang_v1.applied_discount_text', ['discount_name' => $discount->name, 'starts_at' => $discount->formated_starts_at, 'ends_at' => $discount->formated_ends_at]) !!}</p>
            @endif
        </td>--}}
        <td class="text-center {{$hide_tax}}">
            {!! Form::hidden("products[$product->variation_id][item_tax]", @num_format($item_tax), ['class' => 'item_tax']); !!}

            {!! Form::select("products[$product->variation_id][tax_id]", $tax_dropdown['tax_rates'], $tax_id, ['placeholder' => 'Select', 'class' => 'form-control tax_id','readonly'], $tax_dropdown['attributes']); !!}
        </td>

    @else
        @if(!empty($pos_settings['inline_service_staff']))
            <td>
                <div class="form-group">
                    <div class="input-group">
                        {!! Form::select("products[" . $product->variation_id . "][res_service_staff_id]", $waiters, !empty($product->res_service_staff_id) ? $product->res_service_staff_id : null, ['class' => 'form-control select2 order_line_service_staff', 'placeholder' => __('restaurant.select_service_staff'), 'required' => (!empty($pos_settings['is_service_staff_required']) && $pos_settings['is_service_staff_required'] == 1) ? true : false ]); !!}
                    </div>
                </div>
            </td>
        @endif
    @endif
    <td class="{{$hide_tax}} hide">
        <input type="text" name="product[{{$sell_line->product_id}}][unit_price_inc_tax]"
               class="form-control pos_unit_price_inc_tax input_number" value="{{@num_format($unit_price_inc_tax)}}"
               @if(!$edit_price) readonly
               @endif @if(!empty($pos_settings['enable_msp'])) data-rule-min-value="{{$unit_price_inc_tax}}"
               data-msg-min-value="{{__('lang_v1.minimum_selling_price_error_msg', ['price' => @num_format($unit_price_inc_tax)])}}" @endif>
    </td>
    <td>
       {{$varients->name}}
    </td>

    <td class="text-center">
        @php
            $subtotal_type = !empty($pos_settings['is_pos_subtotal_editable']) ? 'text' : 'hidden';

        @endphp
        <input type="{{$subtotal_type}}"
               class="form-control pos_line_totals pos_line_total_{{$pid}} @if(!empty($pos_settings['is_pos_subtotal_editable'])) input_number @endif"
               value="{{'$'.@num_format((float)$varients->value)}}">
        <input type="{{$subtotal_type}}"
               class="form-control pos_line_total pos_line_total_{{$pid}} @if(!empty($pos_settings['is_pos_subtotal_editable'])) input_number @endif"
               value="{{'$'.@num_format((float)$varients->value)}}">
        <span
            class="hide display_currency pos_line_total_text  pos_line_total_text_{{$pid}} @if(!empty($pos_settings['is_pos_subtotal_editable'])) hide @endif"
            data-currency_symbol="true">{{'$'.@num_format((float)$varients->value)}}</span>
        <span
            class="display_currency pos_line_total_texts  pos_line_total_text_{{$pid}} @if(!empty($pos_settings['is_pos_subtotal_editable'])) hide @endif"
            data-currency_symbol="true">{{'$'.@num_format((float)$varients->value)}}</span>
    </td>
    {{-- <td class="text-center v-center">
        <i class="fa fa-times text-danger pos_remove_row cursor-pointer" aria-hidden="true"></i>
    </td> --}}
</tr>
