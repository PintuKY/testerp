@extends('layouts.app')
@section('title', __('supplier.view_supplier'))

@section('content')

<!-- Main content -->
<section class="content no-print">
    <div class="row no-print">
        <div class="col-md-4">
            <h3>@lang('supplier.view_supplier')</h3>
        </div>
        <div class="col-md-4 col-xs-12 mt-15 pull-right">
            {!! Form::select('$supplier_id', $supplier_dropdown, $supplier->id , ['class' => 'form-control select2', 'id' => 'supplier_id']); !!}
        </div>
    </div>
    <div class="hide print_table_part">
        <style type="text/css">
            .info_col {
                width: 25%;
                float: left;
                padding-left: 10px;
                padding-right: 10px;
            }
        </style>
        <div style="width: 100%;">
            <div class="info_col">
                @include('supplier.supplier_basic_info')
            </div>
            <div class="info_col">
                @include('supplier.supplier_tax_info')
            </div>
            <div class="info_col">
                @include('supplier.supplier_payment_info')
            </div>
        </div>
    </div>
    <input type="hidden" id="sell_list_filter_customer_id" value="{{$supplier->id}}">
    <input type="hidden" id="purchase_list_filter_supplier_id" value="{{$supplier->id}}">
    <br>
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-body">
                    @include('supplier.partials.supplier_info_tab')
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs nav-justified">
                    <li class="
                            @if(!empty($view_type) &&  $view_type == 'ledger')
                                active
                            @else
                                ''
                            @endif">
                        <a href="#ledger_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-scroll" aria-hidden="true"></i> @lang('lang_v1.ledger')</a>
                    </li>
                    
                    <li class="
                        @if(!empty($view_type) &&  $view_type == 'purchase')
                            active
                        @else
                            ''
                        @endif">
                        <a href="#purchases_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-arrow-circle-down" aria-hidden="true"></i> @lang( 'purchase.purchases')</a>
                    </li>
                    <li class="
                        @if(!empty($view_type) &&  $view_type == 'stock_report')
                            active
                        @else
                            ''
                        @endif">
                        <a href="#stock_report_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-hourglass-half" aria-hidden="true"></i> @lang( 'report.stock_report')</a>
                    </li>
                    <li class="
                            @if(!empty($view_type) &&  $view_type == 'documents_and_notes')
                                active
                            @else
                                ''
                            @endif
                            ">
                        <a href="#documents_and_notes_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-paperclip" aria-hidden="true"></i> @lang('lang_v1.documents_and_notes')</a>
                    </li>
                    <li class="
                            @if(!empty($view_type) &&  $view_type == 'payments')
                                active
                            @else
                                ''
                            @endif">
                        <a href="#payments_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-money-bill-alt" aria-hidden="true"></i> @lang('sale.payments')</a>
                    </li>

                    {{-- @if( in_array($supplier->type, ['customer', 'both']) && session('business.enable_rp'))
                        <li class="
                            @if(!empty($view_type) &&  $view_type == 'reward_point')
                                active
                            @else
                                ''
                            @endif">
                            <a href="#reward_point_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-gift" aria-hidden="true"></i> {{ session('business.rp_name') ?? __( 'lang_v1.reward_points')}}</a>
                        </li>
                    @endif --}}

                    <li class="
                        @if(!empty($view_type) &&  $view_type == 'activities')
                            active
                        @else
                            ''
                        @endif">
                        <a href="#activities_tab" data-toggle="tab" aria-expanded="true"><i class="fas fa-pen-square" aria-hidden="true"></i> @lang('lang_v1.activities')</a>
                        </li>

                    @if(!empty($supplier_view_tabs))
                        @foreach($supplier_view_tabs as $key => $tabs)
                            @foreach ($tabs as $index => $value)
                                @if(!empty($value['tab_menu_path']))
                                    @php
                                        $tab_data = !empty($value['tab_data']) ? $value['tab_data'] : [];
                                    @endphp
                                    @include($value['tab_menu_path'], $tab_data)
                                @endif
                            @endforeach
                        @endforeach
                    @endif

                </ul>

                <div class="tab-content">
                    <div class="tab-pane
                                @if(!empty($view_type) &&  $view_type == 'ledger')
                                    active
                                @else
                                    ''
                                @endif"
                            id="ledger_tab">
                        @include('supplier.partials.ledger_tab')
                    </div>
                    
                        <div class="tab-pane demo
                            @if(!empty($view_type) &&  $view_type == 'purchase')
                                active
                            @else
                                ''
                            @endif"
                        id="purchases_tab" >
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('purchase_list_filter_date_range', __('report.date_range') . ':') !!}
                                        {!! Form::text('purchase_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    @include('supplier_purchase.partials.purchase_table')
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane 
                            @if(!empty($view_type) &&  $view_type == 'stock_report')
                                active
                            @else
                                ''
                            @endif" id="stock_report_tab">
                            @include('supplier.partials.stock_report_tab')
                        </div>
                   
                    
                        <div class="tab-pane 
                            @if(!empty($view_type) &&  $view_type == 'sales')
                                active
                            @else
                                ''
                            @endif"
                        id="sales_tab">
                            <div class="row">
                                <div class="col-md-12">
                                    @component('components.widget')
                                        @include('sell.partials.sell_list_filters', ['only' => ['sell_list_filter_payment_status', 'sell_list_filter_date_range', 'only_subscriptions']])
                                    @endcomponent
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    @include('sale_pos.partials.sales_table')
                                </div>
                            </div>
                        </div>
                        @if(in_array('subscription', $enabled_modules))
                            @include('supplier.partials.subscriptions')
                        @endif
                    
                    <div class="tab-pane
                            @if(!empty($view_type) &&  $view_type == 'documents_and_notes')
                                active
                            @else
                                ''
                            @endif"
                        id="documents_and_notes_tab">
                        @include('supplier.partials.documents_and_notes_tab')
                    </div>
                    <div class="tab-pane 
                        @if(!empty($view_type) &&  $view_type == 'payments')
                            active
                        @else
                            ''
                        @endif" id="payments_tab">
                        <div id="supplier_payments_div" style="height: 500px;overflow-y: scroll;"></div>
                    </div>
                    @if( in_array($supplier->type, ['customer', 'both']) && session('business.enable_rp'))
                        <div class="tab-pane
                            @if(!empty($view_type) &&  $view_type == 'reward_point')
                                active
                            @else
                                ''
                            @endif"
                        id="reward_point_tab">
                        <br>
                            <div class="row">
                            @if($reward_enabled)
                                <div class="col-md-3">
                                    <div class="info-box bg-yellow">
                                        <span class="info-box-icon"><i class="fa fa-gift"></i></span>

                                        <div class="info-box-content">
                                          <span class="info-box-text">{{session('business.rp_name')}}</span>
                                          <span class="info-box-number">{{$supplier->total_rp ?? 0}}</span>
                                        </div>
                                        <!-- /.info-box-content -->
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" 
                                    id="rp_log_table" width="100%">
                                        <thead>
                                            <tr>
                                                <th>@lang('messages.date')</th>
                                                <th>@lang('sale.invoice_no')</th>
                                                <th>@lang('lang_v1.earned')</th>
                                                <th>@lang('lang_v1.redeemed')</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                        </div>
                    @endif

                    <div class="tab-pane"
                        id="activities_tab">
                        @include('activity_log.activities')
                    </div>

                    @if(!empty($supplier_view_tabs))
                        @foreach($supplier_view_tabs as $key => $tabs)
                            @foreach ($tabs as $index => $value)
                                @if(!empty($value['tab_content_path']))
                                    @php
                                        $tab_data = !empty($value['tab_data']) ? $value['tab_data'] : [];
                                    @endphp
                                    @include($value['tab_content_path'], $tab_data)
                                @endif
                            @endforeach
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade pay_contact_due_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel"></div>
@stop
@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
    $('#ledger_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#ledger_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        }
    );
    $('#ledger_date_range').change( function(){
        get_supplier_ledger();
    });
    get_supplier_ledger();

    rp_log_table = $('#rp_log_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']],
        ajax: '/sells?customer_id={{ $supplier->id }}&rewards_only=true',
        columns: [
            { data: 'transaction_date', name: 'transactions.transaction_date'  },
            { data: 'invoice_no', name: 'transactions.invoice_no'},
            { data: 'rp_earned', name: 'transactions.rp_earned'},
            { data: 'rp_redeemed', name: 'transactions.rp_redeemed'},
        ]
    });

    supplier_stock_report_table = $('#supplier_stock_report_table').DataTable({
        processing: true,
        serverSide: true,
        'ajax': {
            url: "{{action('SupplierController@getSupplierStockReport', [$supplier->id])}}",
            data: function (d) {
                d.location_id = $('#sr_location_id').val();
            }
        },
        columns: [
            { data: 'product_name', name: 'p.name'  },
            { data: 'sub_sku', name: 'v.sub_sku'  },
            { data: 'purchase_quantity', name: 'purchase_quantity', searchable: false},
            { data: 'total_quantity_sold', name: 'total_quantity_sold', searchable: false},
            { data: 'total_quantity_returned', name: 'total_quantity_returned', searchable: false},
            { data: 'current_stock', name: 'current_stock', searchable: false},
            { data: 'stock_price', name: 'stock_price', searchable: false}
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#supplier_stock_report_table'));
        },
    });

    $('#sr_location_id').change( function() {
        supplier_stock_report_table.ajax.reload();
    });

    $('#supplier_id').change( function() {
        if ($(this).val()) {
            window.location = "{{url('/supplier')}}/" + $(this).val();
        }
    });

    $('a[href="#sales_tab"]').on('shown.bs.tab', function (e) {
        sell_table.ajax.reload();
    });
});

$("input.transaction_types, input#show_payments").on('ifChanged', function (e) {
    get_supplier_ledger();
});

$(document).one('shown.bs.tab', 'a[href="#payments_tab"]', function(){
    get_supplier_payments();
})

$(document).on('click', '#contact_payments_pagination a', function(e){
    e.preventDefault();
    get_supplier_payments($(this).attr('href'));
})

function get_supplier_payments(url = null) {
    if (!url) {
        url = "{{action('SupplierController@getSupplierPayments', [$supplier->id])}}";
    }
    $.ajax({
        url: url,
        dataType: 'html',
        success: function(result) {
            $('#supplier_payments_div').fadeOut(400, function(){
                $('#supplier_payments_div')
                .html(result).fadeIn(400);
            });
        },
    });
}

function get_supplier_ledger() {

    var start_date = '';
    var end_date = '';
    var transaction_types = $('input.transaction_types:checked').map(function(i, e) {return e.value}).toArray();
    var show_payments = $('input#show_payments').is(':checked');

    if($('#ledger_date_range').val()) {
        start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
    }
    $.ajax({
        url: '/suppliers/ledger?supplier_id={{$supplier->id}}&start_date=' + start_date + '&transaction_types=' + transaction_types + '&show_payments=' + show_payments + '&end_date=' + end_date,
        dataType: 'html',
        success: function(result) {
            $('#supplier_ledger_div')
                .html(result);
            __currency_convert_recursively($('#supplier_ledger_div'));

            $('#ledger_table').DataTable({
                searching: false,
                ordering:false,
                paging:false,
                dom: 't'
            });
        },
    });
}

$(document).on('click', '#send_ledger', function() {
    var start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
    var end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');

    var url = "{{action('NotificationController@getTemplate', [$supplier->id, 'send_ledger'])}}" + '?start_date=' + start_date + '&end_date=' + end_date;

    $.ajax({
        url: url,
        dataType: 'html',
        success: function(result) {
            $('.view_modal')
                .html(result)
                .modal('show');
        },
    });
})

$(document).on('click', '#print_ledger_pdf', function() {
    var start_date = $('#ledger_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
    var end_date = $('#ledger_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');

    var url = $(this).data('href') + '&start_date=' + start_date + '&end_date=' + end_date;
    window.open(url);
});

</script>
@include('sale_pos.partials.sale_table_javascript')
<script src="{{ asset('js/supplier_purchase.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>


<!-- document & note.js -->
@include('documents_and_notes.document_and_note_js')
@if(!empty($supplier_view_tabs))
    @foreach($supplier_view_tabs as $key => $tabs)
        @foreach ($tabs as $index => $value)
            @if(!empty($value['module_js_path']))
                @include($value['module_js_path'])
            @endif
        @endforeach
    @endforeach
@endif

<script type="text/javascript">
    $(document).ready( function(){
        $('#purchase_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
               purchase_table.ajax.reload();
            }
        );
        $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#purchase_list_filter_date_range').val('');
            purchase_table.ajax.reload();
        });
    });
</script>
@include('sale_pos.partials.subscriptions_table_javascript', ['supplier_id' => $supplier->id])
@endsection
