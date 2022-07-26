@foreach($formatted_data as $data)
	@include('supplier_purchase.partials.purchase_entry_row', [
		'supplier_products' => [$data['supplier_product']],
		'row_count' => $row_count,
		'supplier_product_id' => $data['supplier_product']->id,
		'taxes' => $taxes,
		'currency_details' => $currency_details,
		'hide_tax' => $hide_tax,
		'imported_data' =>  $data
	])
	@php
		$row_count++;
	@endphp
@endforeach