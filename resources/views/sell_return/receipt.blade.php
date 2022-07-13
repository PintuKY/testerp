<br/>
<table style="width:100%;">
	<thead>
		<tr>
			<td>

			@if(!empty($receipt_details->invoice_heading))
				<p class="text-right text-muted-imp" style="font-weight: bold; font-size: 18px !important">{!! $receipt_details->invoice_heading !!}</p>
			@endif

			<p class="text-right">
				@if(!empty($receipt_details->invoice_no_prefix))
					{!! $receipt_details->invoice_no_prefix !!}
				@endif

				{{$receipt_details->invoice_no}}
			</p>

			</td>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td>

@if(!empty($receipt_details->header_text))
	<div class="row invoice-info">
		<div class="col-xs-12">
			{!! $receipt_details->header_text !!}
		</div>
	</div>
@endif

<!-- business information here -->
<div class="row invoice-info">

	<div class="col-md-6 invoice-col width-50 color-555">

		<!-- Logo -->
		@if(!empty($receipt_details->logo))
			<img src="{{$receipt_details->logo}}" class="img">
			<br/>
		@endif

		<!-- Shop & Location Name  -->
		@if(!empty($receipt_details->display_name))
			<p>
				{{$receipt_details->display_name}}<br/>
				{!! $receipt_details->address !!}

				@if(!empty($receipt_details->contact))
					<br/>{{ $receipt_details->contact }}
				@endif

				@if(!empty($receipt_details->website))
					<br/>{{ $receipt_details->website }}
				@endif

				@if(!empty($receipt_details->tax_info1))
					<br/>{{ $receipt_details->tax_label1 }} {{ $receipt_details->tax_info1 }}
				@endif

				@if(!empty($receipt_details->tax_info2))
					<br/>{{ $receipt_details->tax_label2 }} {{ $receipt_details->tax_info2 }}
				@endif

				@if(!empty($receipt_details->location_custom_fields))
					<br/>{{ $receipt_details->location_custom_fields }}
				@endif
			</p>
		@endif

		<!-- Table information-->
        @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
        	<p>
				@if(!empty($receipt_details->table_label))
					{!! $receipt_details->table_label !!}
				@endif
				{{$receipt_details->table}}
			</p>
        @endif

		<!-- Waiter info -->
		@if(!empty($receipt_details->waiter_label) || !empty($receipt_details->waiter))
        	<p>
				@if(!empty($receipt_details->waiter_label))
					{!! $receipt_details->waiter_label !!}
				@endif
				{{$receipt_details->waiter}}
			</p>
        @endif
	</div>

	<div class="col-md-6 invoice-col width-50">

		<p class="text-right font-30">
			@if(!empty($receipt_details->invoice_no_prefix))
				<span class="pull-left">{!! $receipt_details->invoice_no_prefix !!}</span>
			@endif

			{{$receipt_details->invoice_no}}
		</p>

		<p class="text-right">
			@if(!empty($receipt_details->parent_invoice_no_prefix))
				<span class="pull-left">{!! $receipt_details->parent_invoice_no_prefix !!}</span>
			@endif

			{{$receipt_details->parent_invoice_no}}
		</p>


		{{--
			<table class="table table-condensed">

				@if(!empty($receipt_details->payments))
					@foreach($receipt_details->payments as $payment)
						<tr>
							<td>{{$payment['method']}}</td>
							<td>{{$payment['amount']}}</td>
						</tr>
					@endforeach
				@endif
			</table>
		--}}

		<!-- Total Due-->
		@if(!empty($receipt_details->total_due))
			<p class="bg-light-blue-active text-right font-23 padding-5">
				<span class="pull-left bg-light-blue-active">
					{!! $receipt_details->total_due_label !!}
				</span>

				{{$receipt_details->total_due}}
			</p>
		@endif

		<!-- Total Paid-->
		@if(!empty($receipt_details->total_paid))
			<p class="text-right font-23 color-555">
				<span class="pull-left">{!! $receipt_details->total_paid_label !!}</span>
				{{$receipt_details->total_paid}}
			</p>
		@endif

		<!-- Date-->
		@if(!empty($receipt_details->date_label))
			<p class="text-right font-23 color-555">
				<span class="pull-left">
					{{$receipt_details->date_label}}
				</span>

				{{$receipt_details->invoice_date}}
			</p>
		@endif
	</div>
</div>

<div class="row invoice-info color-555">
	<br/>
	<div class="col-md-6 invoice-col width-50 word-wrap">
		<b>{{ $receipt_details->customer_label ?? '' }}</b><br/>

		<!-- customer info -->
		@if(!empty($receipt_details->customer_name))
			{!! $receipt_details->customer_name !!}<br>
		@endif
		@if(!empty($receipt_details->customer_info))
			{!! $receipt_details->customer_info !!}
		@endif
		@if(!empty($receipt_details->client_id_label))
			<br/>
			{{ $receipt_details->client_id_label }} {{ $receipt_details->client_id }}
		@endif
		@if(!empty($receipt_details->customer_tax_label))
			<br/>
			{{ $receipt_details->customer_tax_label }} {{ $receipt_details->customer_tax_number }}
		@endif
		@if(!empty($receipt_details->customer_custom_fields))
			<br/>{!! $receipt_details->customer_custom_fields !!}
		@endif
	</div>


	<div class="col-md-6 invoice-col width-50 word-wrap">
		<p>
			@if(!empty($receipt_details->sub_heading_line1))
				{{ $receipt_details->sub_heading_line1 }}
			@endif
			@if(!empty($receipt_details->sub_heading_line2))
				<br>{{ $receipt_details->sub_heading_line2 }}
			@endif
			@if(!empty($receipt_details->sub_heading_line3))
				<br>{{ $receipt_details->sub_heading_line3 }}
			@endif
			@if(!empty($receipt_details->sub_heading_line4))
				<br>{{ $receipt_details->sub_heading_line4 }}
			@endif
			@if(!empty($receipt_details->sub_heading_line5))
				<br>{{ $receipt_details->sub_heading_line5 }}
			@endif
		</p>
	</div>

</div>

<div class="row color-555">
	<div class="col-xs-12">
		<br/>
        @foreach($receipt_details->product_id as $key => $productId)
            <table class="table table-bordered table-no-top-cell-border table-slim mb-12">
                <thead>
                <tr style="background-color: #357ca5 !important; color: white !important; font-size: 20px !important"
                    class="table-no-side-cell-border table-no-top-cell-border text-center">
                    <td style="background-color: #357ca5 !important; color: white !important; width: 5% !important">{{$loop->iteration}}</td>

                    @php
                        $p_width = 40;
                    @endphp
                    @if($receipt_details->show_cat_code == 1)
                        @php
                            $p_width -= 10;
                        @endphp
                    @endif
                    @if(!empty($receipt_details->item_discount_label))
                        @php
                            $p_width -= 10;
                        @endphp
                    @endif
                    <td style="background-color: #357ca5 !important; color: white !important; width: {{$p_width}}% !important">
                        {{$receipt_details->table_product_label}} ({{$receipt_details->product_name[$key]}})
                    </td>

                    @if($receipt_details->show_cat_code == 1)
                        <td style="background-color: #357ca5 !important; color: white !important; width: 10% !important;">{{$receipt_details->cat_code_label}}</td>
                    @endif


                    <td style="background-color: #357ca5 !important; color: white !important; width: 15% !important;">
                        {{$receipt_details->table_subtotal_label}}
                    </td>
                </tr>
                </thead>
                <tbody>
                @php
                    $subtotal = 0;
                @endphp
                @foreach($receipt_details->lines as $line)
                    @if($line['product_id'] == $productId)
                        <tr>
                            <td class="text-center">

                            </td>
                            <td>
                                @if(!empty($line['image']))
                                    <img src="{{$line['image']}}" alt="Image" width="50"
                                         style="float: left; margin-right: 8px;">
                                @endif
                                {{$line['product_variation']}} {{$line['variation']}}

                                @if(!empty($line['product_custom_fields']))
                                    , {{$line['product_custom_fields']}}
                                @endif
                                @if(!empty($line['sell_line_note']))
                                    <br>
                                    <small>{{$line['sell_line_note']}}</small>
                                @endif
                                @if(!empty($line['lot_number']))
                                    <br> {{$line['lot_number_label']}}:  {{$line['lot_number']}}
                                @endif
                                @if(!empty($line['product_expiry']))
                                    , {{$line['product_expiry_label']}}:  {{$line['product_expiry']}}
                                @endif

                            </td>

                            @if($receipt_details->show_cat_code == 1)
                                <td>
                                    @if(!empty($line['cat_code']))
                                        {{$line['cat_code']}}
                                    @endif
                                </td>
                            @endif


                            <td class="text-right">
                                $ {{ $line['tran_sell_var_value'] }}
                            </td>
                        </tr>
                        @if(!empty($line['modifiers']))
                            @foreach($line['modifiers'] as $modifier)
                                <tr>
                                    <td class="text-center">
                                        &nbsp;
                                    </td>
                                    <td>
                                        {{$modifier['name']}} {{$modifier['variation']}}
                                        @if(!empty($modifier['sub_sku']))
                                            , {{$modifier['sub_sku']}}
                                        @endif
                                        @if(!empty($modifier['sell_line_note']))
                                            ({{$modifier['sell_line_note']}})
                                        @endif
                                    </td>

                                    @if($receipt_details->show_cat_code == 1)
                                        <td>
                                            @if(!empty($modifier['cat_code']))
                                                {{$modifier['cat_code']}}
                                            @endif
                                        </td>
                                    @endif

                                    <td class="text-right">
                                        {{$modifier['quantity']}} {{$modifier['units']}}
                                    </td>
                                    <td class="text-right">
                                        {{$modifier['unit_price_exc_tax']}}
                                    </td>
                                    @if(!empty($receipt_details->item_discount_label))
                                        <td class="text-right">0.00</td>
                                    @endif
                                    <td class="text-right">
                                        {{$modifier['line_total']}}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endif
                @endforeach

                @php
                    $lines = count($receipt_details->lines);
                @endphp

                </tbody>
            </table>
            <div class="table-responsive">
                <table class="table table-condensed table-bordered table-striped">
                    <tr>
                        <td class="price_cal">
                            <div class="pull-right">
                                <b>@lang('sale.item'):</b>
                                <span class="total_quantity">{{  @num_format($edit_product[$productId]['quantity']) }}</span>
                                @php
                                    $total_item_value = $edit_product[$productId]['total_item_value'];
                                    $total_quantity = $edit_product[$productId]['quantity']
                                @endphp
                                <b>@lang('sale.total'): </b>
                                <span class="price_totals total_prices price_totals">${{round($total_item_value * $total_quantity,2)}}</span>
                            </div>
                        </td>
                    </tr>
                </table>

            </div>
        @endforeach
		{{--<table class="table table-bordered table-no-top-cell-border">
			<thead>
				<tr style="background-color: #357ca5 !important; color: white !important; font-size: 20px !important" class="table-no-side-cell-border table-no-top-cell-border text-center">
					<td style="background-color: #357ca5 !important; color: white !important; width: 5% !important">No</td>

					@php
						$p_width = 35;
					@endphp
					@if($receipt_details->show_cat_code != 1)
						@php
							$p_width = 45;
						@endphp
					@endif
					<td style="background-color: #357ca5 !important; color: white !important; width: {{$p_width}}% !important">
						{{$receipt_details->table_product_label}}
					</td>

					<td style="background-color: #357ca5 !important; color: white !important; width: 20% !important">
						{{$receipt_details->table_subtotal_label}}
					</td>
				</tr>
			</thead>
			<tbody>
				@foreach($receipt_details->lines as $line)
					<tr>
						<td class="text-center">
							{{$loop->iteration}}
						</td>
						<td>
                            {{$line['name']}} {{$line['variation']}}
                            @if(!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif @if(!empty($line['brand'])), {{$line['brand']}} @endif
                            @if(!empty($line['sell_line_note']))({{$line['sell_line_note']}}) @endif
                        </td>

						@if($receipt_details->show_cat_code == 1)
	                        <td>
	                        	@if(!empty($line['cat_code']))
	                        		{{$line['cat_code']}}
	                        	@endif
	                        </td>
	                    @endif
						<td class="text-right">
							{{$line['line_total']}}
						</td>
					</tr>
				@endforeach

				@php
					$lines = count($receipt_details->lines);
				@endphp

				--}}{{--@for ($i = $lines; $i < 7; $i++)
    				<tr>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    					<td>&nbsp;</td>
    				</tr>
				@endfor--}}{{--

			</tbody>
		</table>--}}
	</div>
</div>

<div class="row invoice-info color-555" style="page-break-inside: avoid !important">
	<div class="col-md-6 invoice-col width-50">
		<b class="pull-left">Authorized Signatory</b>
	</div>

	<div class="col-md-6 invoice-col width-50">
		<table class="table-no-side-cell-border table-no-top-cell-border width-100">
			<tbody>
				<tr class="color-555">
					<td style="width:50%">
						{!! $receipt_details->subtotal_label !!}
					</td>
					<td class="text-right">
						{{$receipt_details->subtotal}}
					</td>
				</tr>

				<!-- Tax -->
				@if(!empty($receipt_details->taxes))
					@foreach($receipt_details->taxes as $k => $v)
						<tr class="color-555">
							<td>{{$k}}</td>
							<td class="text-right">{{$v}}</td>
						</tr>
					@endforeach
				@endif

				<!-- Discount -->
				@if( !empty($receipt_details->discount) )
					<tr class="color-555">
						<td>
                            @lang('lang_v1.total_return')
						</td>

						<td class="text-right">
							(-) {{$receipt_details->total_return}}
						</td>
					</tr>
				@endif

				{{--@if(!empty($receipt_details->group_tax_details))
					@foreach($receipt_details->group_tax_details as $key => $value)
						<tr class="color-555">
							<td>
								{!! $key !!}
							</td>
							<td class="text-right">
								(+) {{$value}}
							</td>
						</tr>
					@endforeach
				@else
					@if( !empty($receipt_details->tax) )
						<tr class="color-555">
							<td>
								{!! $receipt_details->tax_label !!}
							</td>
							<td class="text-right">
								(+) {{$receipt_details->tax}}
							</td>
						</tr>
					@endif
				@endif--}}

				<!-- Total -->
				<tr>
					<th style="background-color: #357ca5 !important; color: white !important" class="font-23 padding-10">
                        @lang('lang_v1.return_total'):
                    </th>
					<td class="text-right font-23 padding-10" style="background-color: #357ca5 !important; color: white !important">
						{{$receipt_details->total_return_amount}}
					</td>
				</tr>
			</tbody>
        </table>
	</div>
</div>

<div class="row color-555">
	<div class="col-xs-6">
		{{$receipt_details->additional_notes}}
	</div>

	{{-- Barcode --}}
	@if($receipt_details->show_barcode)
		<div class="col-xs-6">
			<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
		</div>
	@endif
</div>

@if(!empty($receipt_details->footer_text))
	<div class="row color-555">
		<div class="col-xs-12">
			{!! $receipt_details->footer_text !!}
		</div>
	</div>
@endif

			</td>
		</tr>
	</tbody>
</table>
