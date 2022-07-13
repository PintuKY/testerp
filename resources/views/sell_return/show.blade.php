<div class="modal-dialog modal-xl no-print" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.sell_return') (<b>@lang('sale.invoice_no')
                    :</b> {{ $sell->return_parent->invoice_no }})
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-6 col-xs-6">
                    <h4>@lang('lang_v1.sell_return_details'):</h4>
                    <strong>@lang('lang_v1.return_date')
                        :</strong> {{@format_date($sell->return_parent->transaction_date)}}<br>
                    <strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }} <br>
                    <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }}
                </div>
                <div class="col-sm-6 col-xs-6">
                    <h4>@lang('lang_v1.sell_details'):</h4>
                    <strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
                    <strong>@lang('messages.date'):</strong> {{@format_date($sell->transaction_date)}}
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-sm-12">
                    <br>
                    @foreach($product_ids as $key=>$productId)
                        <table class="table bg-gray" id="sell_return_table">
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
                      data-currency_symbol="true">{{  ($sell_line->transactionSellLinesVariants->isNotEmpty()) ? $sell_line->transactionSellLinesVariants[0]->value : '0'}}</span>
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
                            <span class="display_currency"
                                  data-currency_symbol="true">{{ $modifier->unit_price }}</span>
                                            </td>
                                            <td>
                                                &nbsp;
                                            </td>
                                            <td>
                                                <span class="display_currency"
                                                      data-currency_symbol="true">{{ $modifier->item_tax }}</span>
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
                              data-currency_symbol="true">{{  ($sell_line->transactionSellLinesVariants->isNotEmpty()) ? $sell_line->transactionSellLinesVariants[0]->value : '0' }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </table>
                        <div class="table-responsive">
                            <table
                                class="table table-condensed table-bordered table-striped pos_table_{{$productId}}">
                                <tr>
                                    <td class="price_cal">
                                        <div class="pull-right">
                                            <b>@lang('sale.item'):</b>
                                            <span
                                                class="total_quantity_{{$productId}} total_quantity">{{  @num_format($edit_product[$productId]['quantity']) }}</span>
                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                            <b>@lang('sale.total'): </b>
                                            @php
                                                $total_item_value = $edit_product[$productId]['total_item_value'];
                                                $total_quantity = $edit_product[$productId]['quantity']
                                            @endphp
                                            <span
                                                class="price_totals_{{$productId}} total_prices">${{round($total_item_value * $total_quantity,2)}}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                        </div>
                       @endforeach
                    @php
                        $total_before_tax = 0;
                           $unit_name = $sell_line->product->unit->short_name;

                           if(!empty($sell_line->sub_unit)) {
                             $unit_name = $sell_line->sub_unit->short_name;
                           }
                       $line_total = $sell_line->unit_price_inc_tax * $sell_line->quantity_returned;
                           $total_before_tax += $line_total ;
                    @endphp
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-sm-offset-6 col-xs-6 col-xs-offset-6">
                    <table class="table">
                        <tr>
                            <th>@lang('purchase.total'):</th>
                            <td></td>
                            <td><span class="display_currency pull-right"
                                      data-currency_symbol="true">{{ $sell->final_total }}</span></td>
                        </tr>
                        <tr>
                            <th>@lang('lang_v1.total_return'):</th>
                            <td></td>
                            <td><span class="display_currency pull-right"
                                      data-currency_symbol="true">{{ $sell->return_parent->total_return }}</span></td>
                        </tr>


                        <tr>
                            <th>@lang('lang_v1.return_total'):</th>
                            <td></td>
                            <td><span class="display_currency pull-right"
                                      data-currency_symbol="true">{{ $sell->return_parent->final_total }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <strong>{{ __('repair::lang.activities') }}:</strong><br>
                    @includeIf('activity_log.activities', ['activity_type' => 'sell'])
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#" class="print-invoice btn btn-primary"
               data-href="{{action('SellReturnController@printInvoice', [$sell->return_parent->id])}}"><i
                    class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a>
            <button type="button" class="btn btn-default no-print"
                    data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var element = $('div.modal-xl');
        __currency_convert_recursively(element);
    });
</script>
