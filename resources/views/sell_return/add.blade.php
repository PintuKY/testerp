@extends('layouts.app')
@section('title', __('lang_v1.sell_return'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('lang_v1.sell_return')</h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">

        {!! Form::hidden('location_id', $sell->location->id, ['id' => 'location_id', 'data-receipt_printer_type' => $sell->location->receipt_printer_type ]); !!}

        {!! Form::open(['url' => action('SellReturnController@store'), 'method' => 'post', 'id' => 'sell_return_form','files' => true ]) !!}

        {!! Form::hidden('transaction_id', $sell->id); !!}
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">@lang('lang_v1.parent_sale')</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-4">
                        <strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
                        <strong>@lang('messages.date'):</strong> {{@format_date($sell->transaction_date)}}
                    </div>
                    <div class="col-sm-4">
                        <strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }} <br>
                        <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }}
                    </div>

                </div>
            </div>
        </div>
        <div class="box box-solid">
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('invoice_no', __('sale.invoice_no').':') !!}
                            {!! Form::text('invoice_no', !empty($sell->return_parent->invoice_no) ? $sell->return_parent->invoice_no : null, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('sell_document', __('purchase.attach_document') . ':') !!}
                            {!! Form::file('sell_document', ['id' => 'sell_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                            <p class="help-block">
                                @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                                @includeIf('components.document_help_text')
                            </p>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __('messages.date') . ':*') !!}
                            <div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
                                @php
                                    $transaction_date = !empty($sell->return_parent->transaction_date) ? $sell->return_parent->transaction_date : 'now';
                                @endphp
                                {!! Form::text('transaction_date', @format_datetime($transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}
                            </div>
                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <div class="col-sm-12">
                        @foreach($product_ids as $key=>$productId)
                            <table class="table bg-gray" id="sell_return_table" data-id="{{$productId}}">
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
                                @foreach($sell_details as $sell_line)
                                    @foreach($sell_line->transactionSellLinesVariants as $varients)

                                        @if($sell_line->product_id == $productId)
                                            {{--
            @if($sell_line->product->id == $productId)--}}
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    {{--@if( $sell_line->product->type == 'variable')
                                                        {{ $sell_line->variations->product_variation->name ?? ''}}
                                                        - {{ $sell_line->variations->name ?? ''}}
                                                    @endif--}}
                                                    @if( $sell_line->product->type == 'variable')
                                                        {{ $varients->pax ?? 'aa'}}
                                                        - {{ $varients->name ?? 'aa'}}
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
                      data-currency_symbol="true">{{  ($varients->value) ? $varients->value : '0'}}</span>
                                                </td>
                                            </tr>
                                        @endif
                                        @if(!empty($sell_line->modifiers))
                                            @foreach($sell_line->modifiers as $modifier)
                                                <tr>
                                                    <td>&nbsp;</td>
                                                    <td>
                                                        {{ $modifier->product->name }}
                                                        - {{ $modifier->variations->name ?? ''}}
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
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        {!! Form::label('total_return', __( 'lang_v1.total_return' ) . ':') !!}

                                        <input type="text" name="products[{{$productId}}][total_return]"
                                               value="{{ @num_format($edit_product[$productId]['return_amount']) }}"
                                               class="form-control input-sm input_number total_return total_return_{{$productId}} input_quantity"
                                               data-rule-abs_digit="{{$check_decimal = true}}"
                                               data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')"
                                               data-rule-max-value="{{$sell->final_total}}"
                                               data-msg-max-value="@lang('validation.custom-messages.return_amount_less_then_total_amount', ['total' => $sell->final_total ])"
                                        >

                                        <input type="hidden" name="total_return_value" id="total_return_value" value="">
                                    </div>
                                </div>
                            </div>
                            <input name="products[{{$productId}}][unit_price_inc_tax]" type="hidden" class="unit_price"
                                   value="{{@num_format($sell_line->unit_price_inc_tax)}}">
                            <input name="products[{{$productId}}][sell_line_id]" type="hidden"
                                   value="{{$sell_line->id}}">
                        @endforeach
                    </div>
                </div>
                <div class="row">
                    @php
                        $discount_type = !empty($sell->return_parent->discount_type) ? $sell->return_parent->discount_type : $sell->discount_type;
                        $discount_amount = !empty($sell->return_parent->discount_amount) ? $sell->return_parent->discount_amount : $sell->discount_amount;
                    @endphp
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('discount_type', __( 'purchase.discount_type' ) . ':') !!}
                            {!! Form::select('discount_type', [ '' => __('lang_v1.none'), 'fixed' => __( 'lang_v1.fixed' ), 'percentage' => __( 'lang_v1.percentage' )], $sell->discount_type, ['class' => 'form-control','disabled']); !!}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('discount_amount', __( 'purchase.discount_amount' ) . ':') !!}
                            {!! Form::text('discount_amount', @num_format($sell->discount_amount), ['class' => 'form-control input_number','readonly']); !!}
                        </div>
                    </div>
                </div>
                @php
                    $tax_percent = 0;
                    if(!empty($sell->tax)){
                        $tax_percent = $sell->tax->amount;
                    }
                @endphp
                {!! Form::hidden('tax_id', $sell->tax_id); !!}
                {!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
                {!! Form::hidden('tax_percent', $tax_percent, ['id' => 'tax_percent']); !!}
                <div class="row">
                    <div class="col-sm-12 text-right">
                        <strong>@lang('lang_v1.total'): </strong>&nbsp;
                        <span id="total">{{$sell->final_total}}</span>
                    </div>
                    <div class="col-sm-12 text-right">
                        <strong>@lang('lang_v1.total_return'): </strong>&nbsp;
                        <span id="return_amount">0</span>
                    </div>
                    <div class="col-sm-12 text-right">
                        <strong>@lang('lang_v1.return_total'): </strong>&nbsp;
                        <span id="net_return">0</span>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}

    </section>
@stop
@section('javascript')
    <script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/sell_return.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            //$('form#sell_return_form').validate();
            update_sell_return_total();
            //Date picker
            // $('#transaction_date').datepicker({
            //     autoclose: true,
            //     format: datepicker_date_format
            // });
        });
        $(document).on('change', 'input.return_qty,input.total_return, #discount_amount, #discount_type', function () {
            update_sell_return_total()
        });

        function update_sell_return_total() {
            var net_return = 0;
            var total_return = 0;
            var subtotal = 0;
            var return_total = '';
            var calculated_total_sum = 0;
            $('table#sell_return_table').each(function () {
                var product_id = $(this).attr('data-id');

                var get_textbox_value = $('.total_return_' + product_id).val();
                if ($.isNumeric(get_textbox_value)) {
                    calculated_total_sum += parseFloat(get_textbox_value);
                }

                return_total += parseFloat($('.total_return_' + product_id).val());
                var subtotal = old_total - return_total;
                /*var quantity = __read_number($(this).find('input.return_qty'));
                var unit_price = __read_number($(this).find('input.unit_price'));
                var subtotal = quantity * unit_price;*/
            });
            var old_total = $('#total').text();
            var subtotal = old_total - calculated_total_sum;
            $('input#total_return_value').val(subtotal);
            $('span#net_return').text(__currency_trans_from_en(subtotal, true));
            $('span#return_amount').text(__currency_trans_from_en(calculated_total_sum, true));
            /*var discount = 0;
            if($('#discount_type').val() == 'fixed'){
                discount = __read_number($("#discount_amount"));
            } else if($('#discount_type').val() == 'percentage'){
                var discount_percent = __read_number($("#discount_amount"));
                discount = __calculate_amount('percentage', discount_percent, net_return);
            }
            discounted_net_return = net_return - discount;

            var tax_percent = $('input#tax_percent').val();
            var total_tax = __calculate_amount('percentage', tax_percent, discounted_net_return);
            var net_return_inc_tax = total_tax + discounted_net_return;

            $('input#tax_amount').val(total_tax);
            $('span#total_return_discount').text(__currency_trans_from_en(discount, true));
            $('span#total_return_tax').text(__currency_trans_from_en(total_tax, true));
            $('span#net_return').text(__currency_trans_from_en(net_return_inc_tax, true));*/
        }
    </script>
@endsection
