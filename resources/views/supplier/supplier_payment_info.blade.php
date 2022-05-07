
<strong>@lang('report.total_purchase')</strong>
<p class="text-muted">
<span class="display_currency" data-currency_symbol="true">
{{ $supplier->total_purchase }}</span>
</p>
<strong>@lang('supplier.total_purchase_paid')</strong>
<p class="text-muted">
<span class="display_currency" data-currency_symbol="true">
{{ $supplier->purchase_paid }}</span>
</p>
<strong>@lang('supplier.total_purchase_due')</strong>
<p class="text-muted">
<span class="display_currency" data-currency_symbol="true">
{{ $supplier->total_purchase - $supplier->purchase_paid }}</span>
</p>

@if(!empty($supplier->opening_balance) && $supplier->opening_balance != '0.00')
    <strong>@lang('lang_v1.opening_balance')</strong>
    <p class="text-muted">
    <span class="display_currency" data-currency_symbol="true">
    {{ $supplier->opening_balance }}</span>
    </p>
    <strong>@lang('lang_v1.opening_balance_due')</strong>
    <p class="text-muted">
    <span class="display_currency" data-currency_symbol="true">
    {{ $supplier->opening_balance - $supplier->opening_balance_paid }}</span>
    </p>
@endif
<strong>@lang('lang_v1.advance_balance')</strong>
<p class="text-muted">
    <span class="display_currency" data-currency_symbol="true">
    {{ $supplier->balance }}</span>
</p>