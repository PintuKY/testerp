
@foreach($product_ids as $key=>$productId)
    <table class="table bg-gray">
    <tr class="bg-green">
        <th>#</th>
        <th>{{ __('sale.product') }}({{$product_names[$key]}})</th>
        @if( session()->get('business.enable_lot_number') == 1)
            <th>{{ __('lang_v1.lot_n_expiry') }}</th>
        @endif
        @if($sell->type == 'sales_order')
            <th>@lang('lang_v1.quantity_remaining')</th>
        @endif

        @if(!empty($pos_settings['inline_service_staff']))
            <th>
                @lang('restaurant.service_staff')
            </th>
        @endif

        <th>{{ __('sale.subtotal') }}</th>
    </tr>
    @foreach($sell->sell_lines as $sell_line)
            @if($sell_line->product->id == $productId)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
                @if( $sell_line->product->type == 'variable')
                    {{ $sell_line->variations->product_variation->name ?? ''}}
                    - {{ $sell_line->variations->name ?? ''}}
                @endif


                @if(!empty($sell_line->sell_line_note))
                    <br> {{$sell_line->sell_line_note}}
                @endif


                @if(in_array('kitchen', $enabled_modules))
                    <br><span
                        class="label @if($sell_line->res_line_order_status == 'cooked' ) bg-red @elseif($sell_line->res_line_order_status == 'served') bg-green @else bg-light-blue @endif">@lang('restaurant.order_statuses.' . $sell_line->res_line_order_status) </span>
                @endif
            </td>
            @if( session()->get('business.enable_lot_number') == 1)
                <td>{{ $sell_line->lot_details->lot_number ?? '--' }}
                    @if( session()->get('business.enable_product_expiry') == 1 && !empty($sell_line->lot_details->exp_date))
                        ({{@format_date($sell_line->lot_details->exp_date)}})
                    @endif
                </td>
            @endif

            <td>
                <span class="display_currency"
                      data-currency_symbol="true">{{ $sell_line->transactionSellLinesVariants[0]->value }}</span>
            </td>
        </tr>
            @endif
        @if(!empty($sell_line->modifiers))
            @foreach($sell_line->modifiers as $modifier)
                <tr>
                    <td>&nbsp;</td>
                    <td>
                        {{ $modifier->product->name }} - {{ $modifier->variations->name ?? ''}}
                    </td>
                    @if( session()->get('business.enable_lot_number') == 1)
                        <td>&nbsp;</td>
                    @endif
                    <td>{{ $modifier->quantity }}</td>
                    @if(!empty($pos_settings['inline_service_staff']))
                        <td>
                            &nbsp;
                        </td>
                    @endif
                    <td>
                        <span class="display_currency" data-currency_symbol="true">{{ $modifier->unit_price }}</span>
                    </td>
                    <td>
                        &nbsp;
                    </td>
                    <td>
                        <span class="display_currency" data-currency_symbol="true">{{ $modifier->item_tax }}</span>
                        @if(!empty($taxes[$modifier->tax_id]))
                            ( {{ $taxes[$modifier->tax_id]}} )
                        @endif
                    </td>
                    <td>
                        <span class="display_currency"
                              data-currency_symbol="true">{{ $modifier->unit_price_inc_tax }}</span>
                    </td>
                    <td>
                        <span class="display_currency"
                              data-currency_symbol="true">{{ $sell_line->transactionSellLinesVariants[0]->value }}</span>
                    </td>
                </tr>
            @endforeach
        @endif
    @endforeach
</table>

@endforeach
