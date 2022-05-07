<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title no-print">
                @lang( 'purchase.view_payments' )
                (
                @if(in_array($transaction->type, ['purchase', 'expense', 'purchase_return', 'payroll']))
                    @lang('purchase.ref_no'): {{ $transaction->ref_no }}
                @elseif(in_array($transaction->type, ['sell', 'sell_return']))
                    @lang('sale.invoice_no'): {{ $transaction->invoice_no }}
                @endif
                )
            </h4>
            <h4 class="modal-title visible-print-block">
                @if(in_array($transaction->type, ['purchase', 'expense', 'purchase_return', 'payroll']))
                    @lang('purchase.ref_no'): {{ $transaction->ref_no }}
                @elseif($transaction->type == 'sell')
                    @lang('sale.invoice_no'): {{ $transaction->invoice_no }}
                @endif
            </h4>
        </div>

        <div class="modal-body">
            @if(in_array($transaction->type, ['purchase', 'purchase_return']))
                <div class="row invoice-info">
                    <div class="col-sm-4 invoice-col">
                        @include('supplier_transaction_payment.transaction_supplier_details')
                    </div>
                    <div class="col-md-4 invoice-col">
                        @include('supplier_transaction_payment.payment_business_details')
                    </div>

                    <div class="col-sm-4 invoice-col">
                        <b>@lang('purchase.ref_no'):</b> #{{ $transaction->ref_no }}<br/>
                        <b>@lang('messages.date'):</b> {{ @format_date($transaction->transaction_date) }}<br/>
                        <b>@lang('purchase.purchase_status'):</b> {{ __('lang_v1.' . $transaction->status) }}<br>
                        <b>@lang('purchase.payment_status'):</b> {{ __('lang_v1.' . $transaction->payment_status) }}<br>
                    </div>
                </div>
            @elseif(in_array($transaction->type, ['expense', 'expense_refund']))
                <div class="row invoice-info">
                    @if(!empty($transaction->supplier))
                        <div class="col-sm-4 invoice-col">
                            @lang('expense.expense_for'):
                            <address>
                                <strong>{{ $transaction->supplier->supplier_business_name }}</strong>
                                {{ $transaction->supplier->name }}
                                {!! $transaction->supplier->supplier_address !!}
                                @if(!empty($transaction->contact->tax_number))
                                    <br>@lang('supplier.tax_no'): {{$transaction->supplier->tax_number}}
                                @endif
                                @if(!empty($transaction->supplier->mobile))
                                    <br>@lang('supplier.mobile'): {{$transaction->supplier->mobile}}
                                @endif
                                @if(!empty($transaction->supplier->email))
                                    <br>@lang('business.email'): {{$transaction->supplier->email}}
                                @endif
                            </address>
                        </div>
                    @endif
                    <div class="col-md-4 invoice-col">
                        @include('supplier_transaction_payment.payment_business_details')
                    </div>

                    <div class="col-sm-4 invoice-col">
                        <b>@lang('purchase.ref_no'):</b> #{{ $transaction->ref_no }}<br/>
                        <b>@lang('messages.date'):</b> {{ @format_date($transaction->transaction_date) }}<br/>
                        <b>@lang('purchase.payment_status'):</b> {{ __('lang_v1.' . $transaction->payment_status) }}<br>
                    </div>
                </div>
            @elseif($transaction->type == 'payroll')
                <div class="row invoice-info">
                    <div class="col-sm-4 invoice-col">
                        @lang('essentials::lang.payroll_for'):
                        <address>
                            <strong>{{ $transaction->transactionFor->user_full_name }}</strong>
                            @if(!empty($transaction->transactionFor->address))
                                <br>{{$transaction->transactionFor->address}}
                            @endif
                            @if(!empty($transaction->transactionFor->contact_number))
                                <br>@lang('contact.mobile'): {{$transaction->transactionFor->contact_number}}
                            @endif
                            @if(!empty($transaction->transactionFor->email))
                                <br>@lang('business.email'): {{$transaction->transactionFor->email}}
                            @endif
                        </address>
                    </div>
                    <div class="col-md-4 invoice-col">
                        @include('supplier_transaction_payment.payment_business_details')
                    </div>
                    <div class="col-sm-4 invoice-col">
                        <b>@lang('purchase.ref_no'):</b> #{{ $transaction->ref_no }}<br/>
                        @php
                            $transaction_date = \Carbon\Carbon::parse($transaction->transaction_date);
                        @endphp
                        <b>@lang( 'essentials::lang.month_year' ):</b> {{ $transaction_date->format('F') }} {{ $transaction_date->format('Y') }}<br/>
                        <b>@lang('purchase.payment_status'):</b> {{ __('lang_v1.' . $transaction->payment_status) }}<br>
                    </div>
                </div>
            @else
                <div class="row invoice-info">
                    <div class="col-sm-4 invoice-col">
                        @lang('contact.customer'):
                        <address>
                            <strong>{{ $transaction->supplier->name }}</strong>

                            {!! $transaction->supplier->supplier_address !!}
                            @if(!empty($transaction->supplier->tax_number))
                                <br>@lang('supplier.tax_no'): {{$transaction->supplier->tax_number}}
                            @endif
                            @if(!empty($transaction->supplier->mobile))
                                <br>@lang('supplier.mobile'): {{$transaction->supplier->mobile}}
                            @endif
                            @if(!empty($transaction->supplier->email))
                                <br>@lang('business.email'): {{$transaction->supplier->email}}
                            @endif
                        </address>
                    </div>
                    <div class="col-md-4 invoice-col">
                        @include('supplier_transaction_payment.payment_business_details')
                    </div>
                    <div class="col-sm-4 invoice-col">
                        <b>@lang('sale.invoice_no'):</b> #{{ $transaction->invoice_no }}<br/>
                        <b>@lang('messages.date'):</b> {{ @format_date($transaction->transaction_date) }}<br/>
                        <b>@lang('purchase.payment_status'):</b> {{ __('lang_v1.' . $transaction->payment_status) }}<br>
                    </div>
                </div>
            @endif

            @can('send_notification')
                @if($transaction->type == 'purchase')
                    <div class="row no-print">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-info btn-modal btn-xs"
                            data-href="{{action('NotificationController@getTemplate', ['transaction_id' => $transaction->id,'template_for' => 'payment_paid'])}}" data-container=".view_modal"><i class="fa fa-envelope"></i> @lang('lang_v1.payment_paid_notification')</button>
                        </div>
                    </div>
                    <br>
                @endif
                @if($transaction->type == 'sell')
                    <div class="row no-print">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-info btn-modal btn-xs"
                            data-href="{{action('NotificationController@getTemplate', ['transaction_id' => $transaction->id,'template_for' => 'payment_received'])}}" data-container=".view_modal"><i class="fa fa-envelope"></i> @lang('lang_v1.payment_received_notification')</button>

                            @if($transaction->payment_status != 'paid')
                                &nbsp;
                                <button type="button" class="btn btn-warning btn-modal btn-xs" data-href="{{action('NotificationController@getTemplate', ['transaction_id' => $transaction->id,'template_for' => 'payment_reminder'])}}" data-container=".view_modal"><i class="fa fa-envelope"></i> @lang('lang_v1.send_payment_reminder')</button>
                            @endif
                        </div>
                    </div>
                    <br>
                @endif
            @endcan
            @if($transaction->payment_status != 'paid')
                <div class="row">
                    <div class="col-md-12">
                        @if((auth()->user()->can('purchase.payments') && (in_array($transaction->type, ['purchase', 'purchase_return']))) || (auth()->user()->can('sell.payments') && (in_array($transaction->type, ['sell', 'sell_return']))) || ((auth()->user()->can('all_expense.access') || auth()->user()->can('view_own_expense')) &&  $transaction->type == 'expense') )
                            <a href="{{ action('SupplierTransactionPaymentController@addPayment', [$transaction->id]) }}" class="btn btn-primary btn-xs pull-right add_payment_modal no-print"><i class="fa fa-plus" aria-hidden="true"></i> @lang("purchase.add_payment")</a>
                        @endif
                    </div>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-striped">
                        <tr>
                          <th>@lang('messages.date')</th>
                          <th>@lang('purchase.ref_no')</th>
                          <th>@lang('purchase.amount')</th>
                          <th>@lang('purchase.payment_method')</th>
                          <th>@lang('purchase.payment_note')</th>
                          @if($accounts_enabled)
                            <th>@lang('lang_v1.payment_account')</th>
                          @endif
                          <th class="no-print">@lang('messages.actions')</th>
                        </tr>
                        @forelse ($payments as $payment)
                            <tr>
                              <td>{{ @format_datetime($payment->paid_on) }}</td>
                              <td>{{ $payment->payment_ref_no }}</td>
                              <td><span class="display_currency" data-currency_symbol="true">{{ $payment->amount }}</span></td>
                              <td>{{ $payment_types[$payment->method] ?? '' }}</td>
                              <td>@if(!empty($payment->gateway)){{$payment->gateway}} - @endif {{ $payment->note }}</td>
                              @if($accounts_enabled)
                                <td>{{$payment->payment_account->name ?? ''}}</td>
                              @endif
                              <td class="no-print" style="display: flex;">
                              @if((auth()->user()->can('edit_purchase_payment') && (in_array($transaction->type, ['purchase', 'purchase_return']))) || (auth()->user()->can('edit_sell_payment') && (in_array($transaction->type, ['sell', 'sell_return']))) || ((auth()->user()->can('all_expense.access') || auth()->user()->can('view_own_expense')) && $transaction->type == 'expense') )
                                    @if($payment->method != 'advance')
                                        <button type="button" class="btn btn-info btn-xs edit_payment"
                                    data-href="{{action('SupplierTransactionPaymentController@edit', [$payment->id]) }}"><i class="glyphicon glyphicon-edit"></i></button>
                                    @endif
                                @endif

                                @if((auth()->user()->can('delete_purchase_payment') && (in_array($transaction->type, ['purchase', 'purchase_return']))) || (auth()->user()->can('delete_sell_payment') && (in_array($transaction->type, ['sell', 'sell_return']))) || ((auth()->user()->can('all_expense.access') || auth()->user()->can('view_own_expense')) && $transaction->type == 'expense') )
                                    &nbsp; <button type="button" class="btn btn-danger btn-xs delete_payment"
                                    data-href="{{ action('SupplierTransactionPaymentController@destroy', [$payment->id]) }}"
                                    ><i class="fa fa-trash" aria-hidden="true"></i></button>
                                @endif
                              &nbsp;
                                <button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action('SupplierTransactionPaymentController@viewPayment', [$payment->id]) }}">
                                  <i class="fa fa-eye" aria-hidden="true"></i>
                                </button>
                              @if(!empty($payment->document_path))
                                &nbsp;
                                <a href="{{$payment->document_path}}" class="btn btn-success btn-xs" download="{{$payment->document_name}}"><i class="fa fa-download" data-toggle="tooltip" title="{{__('purchase.download_document')}}"></i></a>
                                @if(isFileImage($payment->document_name))
                                &nbsp;
                                  <button data-href="{{$payment->document_path}}" class="btn btn-info btn-xs view_uploaded_document" data-toggle="tooltip" title="{{__('lang_v1.view_document')}}"><i class="fa fa-picture-o"></i></button>
                                @endif

                              @endif
                              </td>
                            </tr>
                        @empty
                            <tr class="text-center">
                              <td colspan="6">@lang('purchase.no_records_found')</td>
                            </tr>
                        @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-primary no-print"
              aria-label="Print"
                onclick="$(this).closest('div.modal').printThis();">
                <i class="fa fa-print"></i> @lang( 'messages.print' )
            </button>
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
