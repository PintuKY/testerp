<!-- app css -->
@if(!empty($for_pdf))
	<link rel="stylesheet" href="{{ asset('css/app.css?v='.$asset_v) }}">
@endif
<div class="col-md-12 col-sm-12 @if(!empty($for_pdf)) width-100 align-right @endif">
        <p class="text-right align-right"><strong>{{$supplier->business->name}}</strong><br>{!! $supplier->business->business_address !!}</p>
</div>
<div class="col-md-6 col-sm-6 col-xs-6 @if(!empty($for_pdf)) width-50 f-left @endif">
	<p class="blue-heading p-4 width-50">@lang('lang_v1.to'):</p>
	<p><strong>{{$supplier->name}}</strong><br> {!! $supplier->address !!} @if(!empty($supplier->email)) <br>@lang('business.email'): {{$supplier->email}} @endif
	<br>@lang('supplier.mobile'): {{$supplier->mobile}}
	@if(!empty($supplier->tax_number)) <br>@lang('supplier.tax_no'): {{$supplier->tax_number}} @endif
</p>
</div>
<div class="col-md-6 col-sm-6 col-xs-6 text-right align-right @if(!empty($for_pdf)) width-50 f-left @endif">
	<h3 class="mb-0 blue-heading p-4">@lang('lang_v1.account_summary')</h3>
	<small>{{$ledger_details['start_date']}} @lang('lang_v1.to') {{$ledger_details['end_date']}}</small>
	<hr>
	<table class="table table-condensed text-left align-left no-border @if(!empty($for_pdf)) table-pdf @endif">
		<tr>
			<td>@lang('lang_v1.opening_balance')</td>
			<td class="align-right">@format_currency($ledger_details['beginning_balance'])</td>
		</tr>
	
		<tr>
			<td>@lang('report.total_purchase')</td>
			<td class="align-right">@format_currency($ledger_details['total_purchase'])</td>
		</tr>
		<tr>
			<td>@lang('sale.total_paid')</td>
			<td class="align-right">@format_currency($ledger_details['total_paid'])</td>
		</tr>
		<tr>
			<td>@lang('lang_v1.advance_balance')</td>
			<td class="align-right">@format_currency($supplier->balance)</td>
		</tr>
		<tr>
			<td><strong>@lang('lang_v1.balance_due')</strong></td>
			<td class="align-right">@format_currency($ledger_details['balance_due'])</td>
		</tr>
	</table>
</div>
<div class="col-md-12 col-sm-12 @if(!empty($for_pdf)) width-100 @endif">
	<p class="text-center" style="text-align: center;"><strong>@lang('lang_v1.ledger_table_heading', ['start_date' => $ledger_details['start_date'], 'end_date' => $ledger_details['end_date']])</strong></p>
	<div class="table-responsive">
	<table class="table table-striped @if(!empty($for_pdf)) table-pdf td-border @endif" id="ledger_table">
		<thead>
			<tr class="row-border blue-heading">
				<th width="18%" class="text-center">@lang('lang_v1.date')</th>
				<th width="9%" class="text-center">@lang('purchase.ref_no')</th>
				<th width="8%" class="text-center">@lang('lang_v1.type')</th>
				<th width="10%" class="text-center">@lang('sale.location')</th>
				<th width="5%" class="text-center">@lang('sale.payment_status')</th>
				{{--<th width="10%" class="text-center">@lang('sale.total')</th>--}}
				<th width="10%" class="text-center">@lang('account.debit')</th>
				<th width="10%" class="text-center">@lang('account.credit')</th>
				<th width="10%" class="text-center">@lang('lang_v1.balance')</th>
				<th width="5%" class="text-center">@lang('lang_v1.payment_method')</th>
				<th width="15%" class="text-center">@lang('report.others')</th>
			</tr>
		</thead>
		<tbody>
			@foreach($ledger_details['ledger'] as $data)
				<tr @if(!empty($for_pdf) && $loop->iteration % 2 == 0) class="odd" @endif>
					<td class="row-border">{{@format_datetime($data['date'])}}</td>
					<td>{{$data['ref_no']}}</td>
					<td>{{$data['type']}}</td>
					<td>{{$data['location']}}</td>
					<td>{{$data['payment_status']}}</td>
					{{--<td class="ws-nowrap align-right">@if($data['total'] !== '') @format_currency($data['total']) @endif</td>--}}
					<td class="ws-nowrap align-right">@if($data['debit'] != '') @format_currency($data['debit']) @endif</td>
					<td class="ws-nowrap align-right">@if($data['credit'] != '') @format_currency($data['credit']) @endif</td>
					<td class="ws-nowrap align-right">{{$data['balance']}}</td>
					<td>{{$data['payment_method']}}</td>
					<td>{!! $data['others'] !!}</td>
				</tr>
			@endforeach
		</tbody>
	</table>
	</div>
</div>