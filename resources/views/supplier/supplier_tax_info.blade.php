<strong><i class="fa fa-info margin-r-5"></i> @lang('supplier.tax_no')</strong>
<p class="text-muted">
    {{ $supplier->tax_number }}
</p>
@if($supplier->pay_term_type)
    <strong><i class="fa fa-calendar margin-r-5"></i> @lang('supplier.pay_term_period')</strong>
    <p class="text-muted">
        {{ __('lang_v1.' . $supplier->pay_term_type) }}
    </p>
@endif
@if($supplier->pay_term_number)
    <strong><i class="fas fa fa-handshake margin-r-5"></i> @lang('supplier.pay_term')</strong>
    <p class="text-muted">
        {{ $supplier->pay_term_number }}
    </p>
@endif