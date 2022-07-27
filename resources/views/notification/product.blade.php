<p>Dear {contact_name},</p>

<p>Your order status is {sell_status}<br/>
<p>Your invoice number is {invoice_number}<br/>
<p>Invoice Url <a href="{invoice_url}">{invoice_url}</a><br/>
    Total amount: {total_amount}<br/>
    Paid amount: {received_amount}</p>


<div class="row  mt-5">
    <div class="col-xs-12">
        @foreach($product_ids as $key=>$productId)
            <table class="table table-bordered table-no-top-cell-border table-slim mb-12">
                <thead>

                <tr style="background-color: #357ca5 !important; color: white !important; font-size: 20px !important"
                    class="table-no-side-cell-border table-no-top-cell-border text-center">
                    <td style="background-color: #357ca5 !important; color: white !important; width: 5% !important">#
                    </td>

                    <td style="background-color: #357ca5 !important; color: white !important; width: 30% !important">
                        {{ __('sale.product') }}({{$product_names[$key]}}
                    </td>


                    <td style="background-color: #357ca5 !important; color: white !important; width: 15% !important;">
                        {{ __('sale.subtotal') }}
                    </td>
                </tr>


                @foreach($transaction->sell_lines as $sell_line)
                    @if($sell_line->product->id == $productId)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="text-center">
                                @if( $sell_line->product->type == 'variable')
                                    {{ $sell_line->variations->product_variation->name ?? ''}}
                                    - {{ $sell_line->variations->name ?? ''}}
                                @endif


                                @if(!empty($sell_line->sell_line_note))
                                    <br> {{$sell_line->sell_line_note}}
                                @endif


                                @if(in_array('kitchen', $enabled_modules))
                                    <br><span
                                        class="label @if($sell_line->res_line_order_status == 'cooked' ) bg-red @elseif($sell_line->res_line_order_status == 'served')  @else bg-light-blue @endif">@lang('restaurant.order_statuses.' . $sell_line->res_line_order_status) </span>
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
                                <td class="text-center">
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
                            <span class="display_currency text-right"
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
                        <span class="display_currency text-right"
                              data-currency_symbol="true">{{ $modifier->unit_price_inc_tax }}</span>
                                </td>
                                <td>
                        <span class="display_currency text-right"
                              data-currency_symbol="true">{{  ($sell_line->transactionSellLinesVariants->isNotEmpty()) ? $sell_line->transactionSellLinesVariants[0]->value : '0' }}</span>
                                </td>
                            </tr>
                @endforeach
                @endif
                @endforeach
            </table>

            <div class="row invoice-info " style="page-break-inside: avoid !important">
                <div class="col-md-6 invoice-col width-50">
                    <table class="table table-slim">
                        @if(!empty($transaction->payment_lines))
                            @foreach($transaction->payment_lines as $payment)
                                <tr>
                                    <td>{{$payment->method}}</td>
                                    <td>{{$payment->amount}}</td>
                                    <td>{{$payment->date}}</td>
                                </tr>
                            @endforeach
                        @endif
                    </table>
                </div>


                <div class="col-md-6 invoice-col width-50">
                    <table class="table-no-side-cell-border table-no-top-cell-border width-100 table-slim">
                        <tr>
                            <td class="price_cal">
                                <div style="float: right !important;">
                                    <b>@lang('sale.item'):</b>
                                    <span
                                        class="total_quantity">{{  @num_format($edit_product[$productId]['quantity']) }}</span>
                                    @php
                                        $total_item_value = $edit_product[$productId]['total_item_value'];
                                        $total_quantity = $edit_product[$productId]['quantity']
                                    @endphp
                                    <b>@lang('sale.total'): </b>
                                    <span
                                        class="price_totals total_prices price_totals">${{round($total_item_value * $total_quantity,2)}}</span>
                                </div>
                            </td>
                        </tr>
                    </table>

                </div>
            </div>

        @endforeach
    </div>
</div>
<div class="row">
    @php
        $total_paid = 0;
    @endphp
    @if($transaction->type != 'sales_order')
        <div class="col-sm-12 col-xs-12">
            <h4>{{ __('sale.payment_info') }}:</h4>
        </div>
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="table-responsive">
                <table class="table bg-gray table-bordered table-no-top-cell-border table-slim mb-12">
                    <thead>
                    <tr style="background-color: #357ca5 !important; color: white !important; font-size: 20px !important"
                        class="table-no-side-cell-border table-no-top-cell-border text-center">
                        <td style="background-color: #357ca5 !important; color: white !important; width: 5% !important">
                            #
                        </td>
                        <td style="background-color: #357ca5 !important; color: white !important; width: 5% !important">{{ __('messages.date') }}</td>
                        <td style="background-color: #357ca5 !important; color: white !important; width: 5% !important">{{ __('purchase.ref_no') }}</td>
                        <td style="background-color: #357ca5 !important; color: white !important; width: 5% !important">{{ __('sale.amount') }}</td>
                        <td style="background-color: #357ca5 !important; color: white !important; width: 5% !important">{{ __('sale.payment_mode') }}</td>
                        <td style="background-color: #357ca5 !important; color: white !important; width: 5% !important">{{ __('sale.payment_note') }}</td>
                    </tr>
                    </thead>
                    @foreach($transaction->payment_lines as $payment_line)
                        @php
                            if($payment_line->is_return == 1){
                              $total_paid -= $payment_line->amount;
                            } else {
                              $total_paid += $payment_line->amount;
                            }
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ @format_date($payment_line->paid_on) }}</td>
                            <td>{{ $payment_line->payment_ref_no }}</td>
                            <td><span class="display_currency"
                                      data-currency_symbol="true">{{ $payment_line->amount }}</span></td>
                            <td>
                                {{ $payment_types[$payment_line->method] ?? $payment_line->method }}
                                @if($payment_line->is_return == 1)
                                    <br/>
                                    ( {{ __('lang_v1.change_return') }} )
                                @endif
                            </td>
                            <td>@if($payment_line->note)
                                    {{ ucfirst($payment_line->note) }}
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif
    <div class="col-md-6 col-sm-12 col-xs-12 @if($transaction->type == 'sales_order') col-md-offset-6 @endif">
        <div class="table-responsive">
            <table class="table bg-gray">
                <tr>
                    <th>{{ __('sale.total') }}:</th>
                    <td></td>
                    <td><span class="display_currency pull-right"
                              data-currency_symbol="true">{{ $transaction->total }}</span></td>
                </tr>
                <tr>
                    <th>{{ __('sale.discount') }}:</th>
                    <td><b>(-)</b></td>
                    <td>
                        <div class="pull-right"><span class="display_currency"
                                                      @if( $transaction->discount_type == 'fixed') data-currency_symbol="true" @endif>{{ $transaction->discount_amount }}</span> @if( $transaction->discount_type == 'percentage')
                                {{ '%'}}
                            @endif</span></div>
                    </td>
                </tr>
                @if(in_array('types_of_service' ,$enabled_modules) && !empty($transaction->packing_charge))
                    <tr>
                        <th>{{ __('lang_v1.packing_charge') }}:</th>
                        <td><b>(+)</b></td>
                        <td>
                            <div class="pull-right"><span class="display_currency"
                                                          @if( $transaction->packing_charge_type == 'fixed') data-currency_symbol="true" @endif>{{ $transaction->packing_charge }}</span> @if( $transaction->packing_charge_type == 'percent')
                                    {{ '%'}}
                                @endif </div>
                        </td>
                    </tr>
                @endif
                @if(session('business.enable_rp') == 1 && !empty($transaction->rp_redeemed) )
                    <tr>
                        <th>{{session('business.rp_name')}}:</th>
                        <td><b>(-)</b></td>
                        <td><span class="display_currency pull-right"
                                  data-currency_symbol="true">{{ $transaction->rp_redeemed_amount }}</span></td>
                    </tr>
                @endif
                <tr>
                    <th>{{ __('sale.order_tax') }}:</th>
                    <td><b>(+)</b></td>
                    <td class="text-right">
                        @if(!empty($order_taxes))
                            @foreach($order_taxes as $k => $v)
                                <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right"
                                                                               data-currency_symbol="true">{{ $v }}</span>
                                <br>
                            @endforeach
                        @else
                            0.00
                        @endif
                    </td>
                </tr>
                @if(!empty($line_taxes))
                    <tr>
                        <th>{{ __('lang_v1.line_taxes') }}:</th>
                        <td></td>
                        <td class="text-right">
                            @if(!empty($line_taxes))
                                @foreach($line_taxes as $k => $v)
                                    <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right"
                                                                                   data-currency_symbol="true">{{ $v }}</span>
                                    <br>
                                @endforeach
                            @else
                                0.00
                            @endif
                        </td>
                    </tr>
                @endif
                <tr>
                    <th>{{ __('sale.shipping') }}: @if($transaction->shipping_details)
                            ({{$transaction->shipping_details}})
                        @endif</th>
                    <td><b>(+)</b></td>
                    <td><span class="display_currency pull-right"
                              data-currency_symbol="true">{{ $transaction->shipping_charges }}</span></td>
                </tr>

                @if( !empty( $transaction->additional_expense_value_1 )  && !empty( $transaction->additional_expense_key_1 ))
                    <tr>
                        <th>{{ $transaction->additional_expense_key_1 }}:</th>
                        <td><b>(+)</b></td>
                        <td><span
                                class="display_currency pull-right">{{ $transaction->additional_expense_value_1 }}</span>
                        </td>
                    </tr>
                @endif
                @if( !empty( $transaction->additional_expense_value_2 )  && !empty( $transaction->additional_expense_key_2 ))
                    <tr>
                        <th>{{ $transaction->additional_expense_key_2 }}:</th>
                        <td><b>(+)</b></td>
                        <td><span
                                class="display_currency pull-right">{{ $transaction->additional_expense_value_2 }}</span>
                        </td>
                    </tr>
                @endif
                @if( !empty( $transaction->additional_expense_value_3 )  && !empty( $transaction->additional_expense_key_3 ))
                    <tr>
                        <th>{{ $transaction->additional_expense_key_3 }}:</th>
                        <td><b>(+)</b></td>
                        <td><span
                                class="display_currency pull-right">{{ $transaction->additional_expense_value_3 }}</span>
                        </td>
                    </tr>
                @endif
                @if( !empty( $transaction->additional_expense_value_4 ) && !empty( $transaction->additional_expense_key_4 ))
                    <tr>
                        <th>{{ $transaction->additional_expense_key_4 }}:</th>
                        <td><b>(+)</b></td>
                        <td><span
                                class="display_currency pull-right">{{ $transaction->additional_expense_value_4 }}</span>
                        </td>
                    </tr>
                @endif
                <tr>
                    <th>{{ __('lang_v1.round_off') }}:</th>
                    <td></td>
                    <td><span class="display_currency pull-right"
                              data-currency_symbol="true">{{ $transaction->round_off_amount }}</span></td>
                </tr>
                <tr>
                    <th>{{ __('sale.total_payable') }}:</th>
                    <td></td>
                    <td><span class="display_currency pull-right"
                              data-currency_symbol="true">{{ $transaction->final_total }}</span></td>
                </tr>
                @if($transaction->type != 'sales_order')
                    <tr>
                        <th>{{ __('sale.total_paid') }}:</th>
                        <td></td>
                        <td><span class="display_currency pull-right"
                                  data-currency_symbol="true">{{ $total_paid }}</span></td>
                    </tr>
                    <tr>
                        <th>{{ __('sale.total_remaining') }}:</th>
                        <td></td>
                        <td>
                            <!-- Converting total paid to string for floating point substraction issue -->
                            @php
                                $total_paid = (string) $total_paid;
                            @endphp
                            <span class="display_currency pull-right"
                                  data-currency_symbol="true">{{ $transaction->final_total - $total_paid }}</span></td>
                    </tr>
                @endif
            </table>

        </div>
    </div>

</div>

<br>
<p>Thank you for shopping with us.</p>

<p>{business_logo}</p>

<p>&nbsp;</p>
