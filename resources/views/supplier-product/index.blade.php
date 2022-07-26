
@extends('layouts.app')
@section('title', __('sale.products'))

@section('content')
<section class="content-header">
    <h1>@lang('sale.products')
        <small>@lang('lang_v1.manage_products')</small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('category_id', __('product.category') . ':') !!}
                    {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'supplier_product_list_filter_category_id', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>  
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unit_id', __('product.unit') . ':') !!}
                    {!! Form::select('unit_id', $units, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'supplier_product_list_filter_unit_id', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('tax','Tax' . ':') !!}
                    {!! Form::select('tax', $taxes, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'supplier_product_list_filter_tax', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <!-- Custom Tabs -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#product_list_tab" data-toggle="tab" aria-expanded="true"><i
                                class="fa fa-cubes" aria-hidden="true"></i> @lang('lang_v1.all_products')</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="product_list_tab">
                        @can('product.create')
                            <a class="btn btn-primary pull-right" href="{{route('supplier-products.create')}}">
                                <i class="fa fa-plus"></i> @lang('messages.add')</a>
                            <br><br>
                        @endcan
                        @include('supplier-product/partials/supplier_product_list')
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="view_supplier_product_modal" tabindex="-1" role="dialog"
aria-labelledby="gridSystemModalLabel">
</div>


@endsection

@section('javascript')
<script>
$(document).ready(function () {
    supplier_product_table = $('#supplier_product_table').DataTable({
       processing: true,
       serverSide: true,
       aaSorting: [[3, 'asc']],
       scrollY: "75vh",
       scrollX: true,
       scrollCollapse: true,
       "ajax": {
           "url": "/supplier-products",
           "data": function (d) {
               d.category_id = $('#supplier_product_list_filter_category_id').val();
               d.tax = $('#supplier_product_list_filter_tax').val();
               d.unit_id = $('#supplier_product_list_filter_unit_id').val();
               d = __datatable_ajax_callback(d);
           }
       },
        columns: [
                    // {data: 'mass_delete'},
                    {data: 'action', name: 'action'},
                    {data: 'name', name: 'name'},
                    {data: 'sku', name: 'sku'},
                    {data: 'price', name: 'purchase_price'},
                    {data: 'tax', name: 'tax_rates.name'},
                    {data: 'purchase_price_inc_tax', name: 'purchase_price_inc_tax'},
                    {data: 'category', name: 'supplier_product_categories.name'},
                    {data: 'unit', name: 'unit', searchable: false},
                    {data: 'weight', name: 'weight', searchable: false},
                    {data: 'alert_quantity', name: 'alert_quantity', searchable: false},
                ],
    });

$(document).on('change', '#supplier_product_list_filter_category_id, #supplier_product_list_filter_tax,#supplier_product_list_filter_unit_id',
function () {
    supplier_product_table.ajax.reload();
});
$(document).on('click', 'a.view-supplier-product', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('href'),
            dataType: 'html',
            success: function(result) {
                $('#view_supplier_product_modal')
                    .html(result)
                    .modal('show');
                __currency_convert_recursively($('#view_supplier_product_modal'));
            },
        });
    });
$('table#supplier_product_table tbody').on('click', 'a.delete-supplier-product', function (e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            success: function (result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    supplier_product_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });
        });



</script>
@endsection


