@php
	$common_settings = session()->get('business.common_settings');
	$multiplier = 1;
@endphp

{{--@foreach($sub_units as $key => $value)
	@if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key)
		@php
			$multiplier = $value['multiplier'];
		@endphp
	@endif
@endforeach--}}
@foreach($productDatas as $key=>$productData)
    @php
        $selected_variation = \App\Models\Variation::with('product_variation', 'product_variation')->where('product_variation_id', $productData->product_variation->id)
                        ->get();

        echo 'key=>>>>'.$key;
        if($key == 9){
          $selected_variation = \App\Models\Variation::with('product_variation', 'product_variation')->where('product_variation_id', $productData->product_variation->id)
                        ->get();
//dd($selected_variation);
        }

    @endphp
	<tr class="product_row" data-row_index="{{$row_count}}" @if(!empty($so_line)) data-so_id="{{($so_line != '')?$so_line->transaction_id:''}}" @endif>
		<td>
			@if(!empty($so_line))
				<input type="hidden"
				name="products[{{$row_count}}][so_line_id]"
				value="{{$so_line->id}}">
			@endif
			@php
				$product_name = $productData->product->name;
				if(!empty($productData->product->brand)){ $product_name .= ' ' . $productData->product->brand->name ;}
			@endphp

			@if( ($edit_price || $edit_discount) && empty($is_direct_sell) )
			<div title="@lang('lang_v1.pos_edit_product_price_help')">
			<span class="text-link text-info cursor-pointer" data-toggle="modal" data-target="#row_edit_product_price_modal_{{$row_count}}">
				{!! $product_name !!}
				&nbsp;<i class="fa fa-info-circle"></i>
			</span>
			</div>
			@else
				{!! $product_name !!}
			@endif
			<input type="hidden" class="enable_sr_no" value="{{$productData->product->enable_sr_no}}">
			<input type="hidden"
				class="product_type"
				name="products[{{$productData->id}}][product_type]"
				value="{{$productData->product->type}}">

			@php
				$hide_tax = 'hide';
				if(session()->get('business.enable_inline_tax') == 1){
					$hide_tax = '';
				}

				$tax_id = $productData->product->tax_id;
				$item_tax = !empty($productData->item_tax) ? $productData->item_tax : 0;
				$unit_price_inc_tax = $productData->sell_price_inc_tax;

				if(!empty($so_line)) {
					$tax_id = $so_line->tax_id;
					$item_tax = $so_line->item_tax;
				}

				if($hide_tax == 'hide'){
					$tax_id = null;
					$unit_price_inc_tax = $productData->default_sell_price;
				}

				$discount_type = !empty($productData->line_discount_type) ? $productData->line_discount_type : 'fixed';
				$discount_amount = !empty($productData->line_discount_amount) ? $productData->line_discount_amount : 0;

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
					$sell_line_note = $productData->sell_line_note;
				}
			@endphp

			@if(!empty($discount))
				{!! Form::hidden("products[$row_count][discount_id]", $discount->id); !!}
			@endif

			@php
				$warranty_id = !empty($action) && $action == 'edit' && !empty($productData->warranties->first())  ? $productData->warranties->first()->id : $productData->warranty_id;

				if($discount_type == 'fixed') {
					$discount_amount = $discount_amount * $multiplier;
				}
			@endphp

			@if(empty($is_direct_sell))
			<div class="modal fade row_edit_product_price_model" id="row_edit_product_price_modal_{{$row_count}}" tabindex="-1" role="dialog">
				@include('sale_pos.partials.row_edit_product_price_modal')
			</div>
			@endif

			<!-- Description modal end -->
			@if(in_array('modifiers' , $enabled_modules))
				<div class="modifiers_html">
					@if(!empty($productData->product_ms))
						@include('restaurant.product_modifier_set.modifier_for_product', array('edit_modifiers' => true, 'row_count' => $loop->index, 'product_ms' => $productData->product_ms ) )
					@endif
				</div>
			@endif

			@php
				$max_quantity = $productData->qty_available;
				$formatted_max_quantity = $productData->formatted_qty_available;

				if(!empty($action) && $action == 'edit') {
					if(!empty($so_line)) {
						$qty_available = $so_line->quantity - $so_line->so_quantity_invoiced + $productData->quantity_ordered;
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
				$max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $formatted_max_quantity, 'unit' => $productData->product->unit->short_name  ]);
			@endphp

			@if( session()->get('business.enable_lot_number') == 1 || session()->get('business.enable_product_expiry') == 1)
			@php
				$lot_enabled = session()->get('business.enable_lot_number');
				$exp_enabled = session()->get('business.enable_product_expiry');
				$lot_no_line_id = '';
				if(!empty($productData->lot_no_line_id)){
					$lot_no_line_id = $productData->lot_no_line_id;
				}
			@endphp
			@if(!empty($productData->lot_numbers) && empty($is_sales_order))
				<select class="form-control lot_number input-sm" name="products[{{$row_count}}][lot_no_line_id]" @if(!empty($product->transaction_sell_lines_id)) disabled @endif>
					<option value="">@lang('lang_v1.lot_n_expiry')</option>
					@foreach($productData->lot_numbers as $lot_number)
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
						<option value="{{$lot_number->purchase_line_id}}" data-qty_available="{{$lot_number->qty_available}}" data-msg-max="@lang('lang_v1.quantity_error_msg_in_lot', ['qty'=> $lot_number->qty_formated, 'unit' => $product->unit  ])" {{$selected}}>@if(!empty($lot_number->lot_number) && $lot_enabled == 1){{$lot_number->lot_number}} @endif @if($lot_enabled == 1 && $exp_enabled == 1) - @endif @if($exp_enabled == 1 && !empty($lot_number->exp_date)) @lang('product.exp_date'): {{@format_date($lot_number->exp_date)}} @endif {{$expiry_text}}</option>
					@endforeach
				</select>
			@endif
		@endif
		@if(!empty($is_direct_sell))
			<br>
			<textarea class="form-control" name="products[{{$productData->id}}][sell_line_note]" rows="2">{{$sell_line_note}}</textarea>
			<p class="help-block"><small>@lang('lang_v1.sell_line_description_help')</small></p>
		@endif
		</td>

		<td>
			{{-- If edit then transaction sell lines will be present --}}
			@if(!empty($product->transaction_sell_lines_id))
				<input type="hidden" name="products[{{$productData->id}}][transaction_sell_lines_id]" class="form-control" value="{{$productData->transaction_sell_lines_id}}">
			@endif

			<input type="hidden" name="products[{{$productData->id}}][product_id]" class="form-control product_id" value="{{$productData->product->id}}">

			@if(empty($productData->quantity_ordered))
				@php
					$productData->quantity_ordered = 1;
				@endphp
			@endif

			@php
				$allow_decimal = true;
				if($productData->unit_allow_decimal != 1) {
					$allow_decimal = false;
				}
			@endphp
			@foreach($sub_units as $key => $value)
				@if(!empty($productData->sub_unit_id) && $productData->sub_unit_id == $key)
					@php
						$max_qty_rule = $max_qty_rule / $multiplier;
						$unit_name = $value['name'];
						$max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $max_qty_rule, 'unit' => $unit_name  ]);

						if(!empty($productData->lot_no_line_id)){
							$max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $max_qty_rule, 'unit' => $unit_name  ]);
						}

						if($value['allow_decimal']) {
							$allow_decimal = true;
						}
					@endphp
				@endif
			@endforeach
			<div class="input-group input-number">
				<span class="input-group-btn"><button type="button" class="btn btn-default btn-flat product-quantity-down"><i class="fa fa-minus text-danger"></i></button></span>
			<input type="text" data-min="1"
				class="form-control pos_quantity input_number mousetrap input_quantity"
				value="{{@format_quantity($productData->quantity_ordered)}}" name="products[{{$productData->id}}][quantity]" data-allow-overselling="@if(empty($pos_settings['allow_overselling'])){{'false'}}@else{{'true'}}@endif"
				@if($allow_decimal)
					data-decimal=1
				@else
					data-decimal=0
					data-rule-abs_digit="true"
					data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')"
				@endif
				data-rule-required="true"
				data-msg-required="@lang('validation.custom-messages.this_field_is_required')"

			>
			<span class="input-group-btn"><button type="button" class="btn btn-default btn-flat product-quantity-up"><i class="fa fa-plus text-success"></i></button></span>
			</div>

			<input type="hidden" name="products[{{$productData->id}}][product_unit_id]" value="{{$productData->product->unit->id}}">
			@if(count($sub_units) > 0)
				<br>
				<select name="products[{{$productData->id}}][sub_unit_id]" class="form-control input-sm sub_unit">
					@foreach($sub_units as $key => $value)
						<option value="{{$key}}" data-multiplier="{{$value['multiplier']}}" data-unit_name="{{$value['name']}}" data-allow_decimal="{{$value['allow_decimal']}}" @if(!empty($productData->sub_unit_id) && $productData->sub_unit_id == $key) selected @endif>
							{{$value['name']}}
						</option>
					@endforeach
			</select>
			@else
				{{$productData->product->unit->short_name}}
			@endif

			<input type="hidden" class="base_unit_multiplier" name="products[{{$productData->id}}][base_unit_multiplier]" value="{{$multiplier}}">

			<input type="hidden" class="hidden_base_unit_sell_price" value="{{$productData->default_sell_price / $multiplier}}">

			{{-- Hidden fields for combo products --}}

		</td>
		@if(!empty($is_direct_sell))
			@if(!empty($pos_settings['inline_service_staff']))
				<td>
					<div class="form-group">
						<div class="input-group">
							{!! Form::select("products[" . $productData->id . "][res_service_staff_id]", $waiters, !empty($productData->res_service_staff_id) ? $productData->res_service_staff_id : null, ['class' => 'form-control select2 order_line_service_staff', 'placeholder' => __('restaurant.select_service_staff'), 'required' => (!empty($pos_settings['is_service_staff_required']) && $pos_settings['is_service_staff_required'] == 1) ? true : false ]); !!}
						</div>
					</div>
				</td>
			@endif
			@php
				$pos_unit_price = !empty($productData->unit_price_before_discount) ? $productData->unit_price_before_discount : $productData->default_sell_price;

				if(!empty($so_line)) {
					$pos_unit_price = $so_line->unit_price_before_discount;
				}
			@endphp
			<td class="@if(!auth()->user()->can('edit_product_price_from_sale_screen')) hide @endif">
				<input type="text" name="products[{{$productData->id}}][unit_price]" class="form-control product_pos_unit_price input_number mousetrap" value="{{@num_format($pos_unit_price)}}" @if(!empty($pos_settings['enable_msp'])) data-rule-min-value="{{$pos_unit_price}}" data-msg-min-value="{{__('lang_v1.minimum_selling_price_error_msg', ['price' => @num_format($pos_unit_price)])}}" @endif>
			</td>
			<td @if(!$edit_discount) class="hide" @endif>
				{!! Form::text("products[$productData->id][line_discount_amount]", @num_format($discount_amount), ['class' => 'form-control input_number discount_amount']); !!}<br>
				{!! Form::select("products[$productData->id][line_discount_type]", ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')], $discount_type , ['class' => 'form-control product_row_discount_type']); !!}
				@if(!empty($discount))
					<p class="help-block">{!! __('lang_v1.applied_discount_text', ['discount_name' => $discount->name, 'starts_at' => $discount->formated_starts_at, 'ends_at' => $discount->formated_ends_at]) !!}</p>
				@endif
			</td>
			<td class="text-center {{$hide_tax}}">
				{!! Form::hidden("products[$productData->id][item_tax]", @num_format($item_tax), ['class' => 'item_tax']); !!}

				{!! Form::select("products[$productData->id][tax_id]", $tax_dropdown['tax_rates'], $tax_id, ['placeholder' => 'Select', 'class' => 'form-control tax_id'], $tax_dropdown['attributes']); !!}
			</td>

		@else
			@if(!empty($pos_settings['inline_service_staff']))
				<td>
					<div class="form-group">
						<div class="input-group">
							{!! Form::select("products[" . $productData->id . "][res_service_staff_id]", $waiters, !empty($productData->res_service_staff_id) ? $productData->res_service_staff_id : null, ['class' => 'form-control select2 order_line_service_staff', 'placeholder' => __('restaurant.select_service_staff'), 'required' => (!empty($pos_settings['is_service_staff_required']) && $pos_settings['is_service_staff_required'] == 1) ? true : false ]); !!}
						</div>
					</div>
				</td>
			@endif
		@endif
		<td class="{{$hide_tax}}">
			<input type="text" name="products[{{$productData->id}}][unit_price_inc_tax]" class="form-control pos_unit_price_inc_tax input_number" value="{{@num_format($unit_price_inc_tax)}}" @if(!$edit_price) readonly @endif @if(!empty($pos_settings['enable_msp'])) data-rule-min-value="{{$unit_price_inc_tax}}" data-msg-min-value="{{__('lang_v1.minimum_selling_price_error_msg', ['price' => @num_format($unit_price_inc_tax)])}}" @endif>
		</td>


		@if(!empty($productData->product_variation) && $productData->product_variation->variation_template->type == 1)
			<td class="text-center v-center">
				<select class="form-control select_variation_value select2" required name="products[{{$productData->id}}][variation_value_id]">
					<option value="">Please Select</option>
					@foreach ($selected_variation as $key => $product_variation_name_data)
						<option value="{{$product_variation_name_data->id}}" data-price="{{$product_variation_name_data->default_sell_price}}" data-products-variation-id="{{$productData->id}}">{{$product_variation_name_data->name}} - {{$product_variation_name_data->default_sell_price}}</option>
					@endforeach
				</select>
			</td>
			<td class="text-center v-center">
				<input type="text" class="product_selectd_variation_value" value="" readonly required>
			</td>
		@endif

		@if( isset($productData->product_variation) && $productData->product_variation->variation_template->type == 2)
		<td>
			@foreach($selected_variation as $key => $product_variation_name_data)
				<label class="radio-inline">
					<input type="radio" class="radio_variation_value" data-products-variation-id="{{$productData->id}}"  data-price="{{$product_variation_name_data->default_sell_price}}" name="products[{{$productData->id}}][variation_value_id]" value="{{$product_variation_name_data->id}}">{{$product_variation_name_data->name}} - {{$product_variation_name_data->default_sell_price}}
				</label><br>
			@endforeach
		</td>
		<td>
			<input type="text" class="product_radio_variation_value" value="" readonly required>
		</td>
		@endif
		<td class="text-center">
			@php
				$subtotal_type = !empty($pos_settings['is_pos_subtotal_editable']) ? 'text' : 'hidden';
			@endphp
			<input type="{{$subtotal_type}}" class="form-control pos_line_total @if(!empty($pos_settings['is_pos_subtotal_editable'])) input_number @endif" value="{{@num_format($productData->quantity_ordered*$unit_price_inc_tax )}}">
			<span class="display_currency pos_line_total_text @if(!empty($pos_settings['is_pos_subtotal_editable'])) hide @endif" data-currency_symbol="true">{{$productData->quantity_ordered*$unit_price_inc_tax}}</span>
		</td>
		<td class="text-center v-center">
			<i class="fa fa-times text-danger pos_remove_row cursor-pointer" aria-hidden="true"></i>
		</td>
	</tr>
@endforeach
