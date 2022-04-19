<span id="view_contact_page"></span>
<div class="row">
    <div class="col-md-12">
        <div class="col-sm-3">
            @include('supplier.supplier_basic_info')
        </div>
        
        <div class="col-sm-3 mt-56">
            @include('supplier.supplier_tax_info')
        </div>
        {{--
            <div class="col-sm-3 mt-56">
                @include('supplier.supplier_payment_info') 
            </div>
            @if( $contact->type == 'customer' || $contact->type == 'both')
                <div class="col-sm-3 @if($contact->type != 'both') mt-56 @endif">
                    <strong>@lang('lang_v1.total_sell_return')</strong>
                    <p class="text-muted">
                        <span class="display_currency" data-currency_symbol="true">
                        {{ $supplier->total_sell_return }}</span>
                    </p>
                    <strong>@lang('lang_v1.total_sell_return_due')</strong>
                    <p class="text-muted">
                        <span class="display_currency" data-currency_symbol="true">
                        {{ $supplier->total_sell_return -  $supplier->total_sell_return_paid }}</span>
                    </p>
                </div>
            @endif
        --}}

       
            <div class="clearfix"></div>
            <div class="col-sm-12">
                @if(($supplier->total_purchase - $supplier->purchase_paid) > 0)
                    <a href="{{action('SupplierTransactionPaymentController@getPayContactDue', [$supplier->id])}}?type=purchase" class="pay_purchase_due btn btn-primary btn-sm pull-right"><i class="fas fa-money-bill-alt" aria-hidden="true"></i> @lang("contact.pay_due_amount")</a>
                @endif
            </div>
    </div>
</div>