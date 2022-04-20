@lang('purchase.supplier'):
<address>
    <strong>{{ $transaction->supplier->supplier_business_name }}</strong>
    {{ $transaction->supplier->name }}
    {!! $transaction->supplier->supplier_address !!}
    @if(!empty($transaction->supplier->tax_number))
        <br>@lang('supplier.tax_no'): {{$transaction->supplier->tax_number}}
    @endif
    @if(!empty($transaction->supplier->mobile))
        <br>@lang('supplier.mobile'): {{$transaction->supplier->mobile}}
    @endif
    @if(!empty($transaction->supplier->email))
        <br>Email: {{$transaction->supplier->email}}
    @endif
</address>