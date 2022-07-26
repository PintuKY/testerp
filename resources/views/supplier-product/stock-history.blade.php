@extends('layouts.app')
@section('title', __('lang_v1.product_stock_history'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.product_stock_history')</h1>
</section>

<!-- Main content -->
<section class="content">
<div class="row">
    <div class="col-md-12">
    @component('components.widget', ['title' => $product->name])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id',  __('kitchen.kitchen_location') . ':') !!}
                {!! Form::select('location_id', $kitchen_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
        </div>
     
            <input type="hidden" id="product_id" name="product_id" value="{{$product->id}}">
    @endcomponent
    @component('components.widget')
        <div id="product_stock_history" style="display: none;"></div>
    @endcomponent
    </div>
</div>

</section>
<!-- /.content -->
@endsection

@section('javascript')
   <script type="text/javascript">
        $(document).ready( function(){
            load_stock_history($('#product_id').val(), $('#location_id').val());
        });

       function load_stock_history(product_id, location_id) {
            $('#product_stock_history').fadeOut();
            $.ajax({
                url: '/supplier-products/stock-history/' + product_id + "?location_id=" + location_id,
                dataType: 'html',
                success: function(result) {
                    $('#product_stock_history')
                        .html(result)
                        .fadeIn();

                    __currency_convert_recursively($('#product_stock_history'));

                    $('#stock_history_table').DataTable({
                        searching: false,
                        ordering: false
                    });
                },
            });
       }

       $(document).on('change', '#product_id, #location_id', function(){
            load_stock_history($('#product_id').val(), $('#location_id').val());
       });
   </script>
@endsection