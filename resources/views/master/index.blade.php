
@extends('layouts.app')
@section('title', __('lang_v1.master_list'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('lang_v1.master_list')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('master_list_filter_location_id',  __('purchase.business_location') . ':') !!}

                {!! Form::select('master_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('master_list_type',  __('master.type') . ':') !!}
                {!! Form::select('master_list_type', $type, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('master_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('master_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
    @endcomponent
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.master_list')])
    @include('master.partials.master_list')
    @endcomponent
    <div class="modal fade payment_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>
</section>

<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script>
    $(document).ready(function(){
        var lunch = "{{$lunchTotal}}";
        var dinner = "{{$dinnerTotal}}";
        var addon_html = "{!! $addon_html !!}";
        $('.pax').html();
        $('.pax').html('Lunch:'+lunch+'<br/> Dinner:'+dinner);
        $('.addon').html();
        $('.addon').html(addon_html);
        $('#master_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#master_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                master_table.ajax.reload();
            }
        );
        $('#master_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#master_list_filter_date_range').val('');
            master_table.ajax.reload();
        });

            var columns = @json($masterListCols);
            var masterListCols = columns;
            master_table = $('#master_table').DataTable({
                processing: true,
                serverSide: false,
                aaSorting: [
                    [0, 'desc']
                ],
                "ajax": {
                    "url": "/master",
                    "data": function ( d ) {
                        if($('#master_list_filter_date_range').val()) {
                            var start = $('#master_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#master_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        if($('#master_list_type').val()) {
                            var type = $('#master_list_type').val();
                            d.type = type;
                        }
                        if($('#master_list_filter_location_id').val()) {
                            var location = $('#master_list_filter_location_id').val();
                            d.location = location;
                        }
                    }
                },
                columns: masterListCols,
            });

        $(document).on('change', '#master_list_filter_location_id, #master_list_type',  function() {
            master_table.ajax.reload();
            $.ajax({
                method: 'get',
                url: '/master/total',
                dataType: 'json',
                data: {
                    start_date: function () {
                        if($('#master_list_filter_date_range').val()) {
                            var start = $('#master_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');

                            return start;
                        }
                    },
                    end_date: function () {
                        if($('#master_list_filter_date_range').val()) {
                            var end = $('#master_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            return end;
                        }
                    },
                    type: function () {
                        if($('#master_list_type').val()) {
                            var type = $('#master_list_type').val();
                            return type;
                        }
                    },
                    location: function () {
                        if($('#master_list_filter_location_id').val()) {
                            var location = $('#master_list_filter_location_id').val();
                            return location;
                        }
                    },
                },
                success: function (result) {
                    $('.pax').html();
                    $('.addon').html();
                    $('.addon').html(result.addon_html);
                    $('.pax').html('Lunch:'+result.lunch+'<br/> Dinner:'+result.dinner);
                    console.log(result);
                },
            });
        });
    })

    // $(document).on('click', 'a.delete_sell_return', function(e) {
    //     e.preventDefault();
    //     swal({
    //         title: LANG.sure,
    //         icon: 'warning',
    //         buttons: true,
    //         dangerMode: true,
    //     }).then(willDelete => {
    //         if (willDelete) {
    //             var href = $(this).attr('href');
    //             var data = $(this).serialize();

    //             $.ajax({
    //                 method: 'DELETE',
    //                 url: href,
    //                 dataType: 'json',
    //                 data: data,
    //                 success: function(result) {
    //                     if (result.success == true) {
    //                         toastr.success(result.msg);
    //                         sell_return_table.ajax.reload();
    //                     } else {
    //                         toastr.error(result.msg);
    //                     }
    //                 },
    //             });
    //         }
    //     });
    // });
</script>

@endsection
