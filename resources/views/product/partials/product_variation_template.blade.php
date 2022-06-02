@forelse( $template->values as $value )

    @include('product.partials.variation_value_row', ['variation_index' => $row_index, 'value_index' => $loop->index, 'variation_name' => $value->name,'variation_price' => $value->value , 'variation_value_id' => $value->id])

@empty

    @include('product.partials.variation_value_row', ['variation_index' => $row_index, 'value_index' => 0])

@endforelse
