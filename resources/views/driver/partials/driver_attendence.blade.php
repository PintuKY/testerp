@extends('layouts.app')

@section('title', __( 'driver.driver_attendence' ))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'driver.driver_attendence' )</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        @component('components.filters', ['title' => __('report.filters')])
            {{--<div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('select_date', __('driver.select_date') . ':*') !!}
                    <div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
                        {!! Form::text("select_date", $default_date, ['class' => 'form-control select_date', 'required']); !!}
                    </div>
                </div>

            </div>--}}
            @if(empty($only) || in_array('driver_list_filter_date_range', $only))
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('driver_list_filter_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('driver_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control']); !!}
                    </div>
                </div>
            @endif
        @endcomponent

        @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.all_sales')])

            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{action('DriverAttendenceController@create')}}">
                        <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot

            <table class="table table-bordered table-striped ajax_view" id="driver_attendence">
                <thead>
                <tr>
                    <th class="text-center">@lang('driver.name')</th>
                    <th class="text-center">@lang('driver.email')</th>
                    <th class="text-center">@lang('driver.attendence')</th>
                    <th class="text-center">@lang('driver.attendence_date')</th>
                    <th class="text-center">@lang('driver.half_day')</th>
                    <th class="text-center">@lang('driver.leave_reason')</th>
                    <th class="text-center">@lang('driver.leave_reason_description')</th>
                    <th class="text-center">@lang('driver.action')</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        @endcomponent
    </section>
@stop
@section('javascript')
    <script src="{{ asset('js/driver.js?v=' . $asset_v) }}"></script>
    <script>
        $(document).ready(function () {
            var driver_attendence_table = $('#driver_attendence').DataTable({
                processing: true,
                serverSide: true,
                "ajax": {
                    "url": "/driver/attendence",
                    "data": function (d) {
                        if ($('#driver_list_filter_date_range').val()) {
                            var start = $('#driver_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#driver_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        /*d.select_date = $('.select_date').val();*/
                    }
                },
                columnDefs: [{
                    "targets": [0, 1, 2],
                    "orderable": false,
                    "searchable": false
                }],
                columns: [
                    {data: 'name', name: 'name'},
                    {data: 'email', name: 'email'},
                    {data: 'in_or_out', name: 'in_or_out'},
                    {data: 'attendance_date', name: 'attendance_date'},
                    {data: 'is_half_day', name: 'is_half_day'},
                    {data: 'leave_reason', name: 'leave_reason'},
                    {data: 'leave_reason_description', name: 'leave_reason_description'},
                    {data: 'action', name: 'action'},
                ]
            });

            $('.select_date').on('dp.change', function (e) {
                driver_attendence_table.ajax.reload();
            })

            $('#driver_list_filter_date_range').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#driver_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    driver_table.ajax.reload();
                    driver_attendence_table.ajax.reload();
                }
            );
            $(document).on('click', 'button.delete_driver_attendence_button', function () {
                swal({
                    title: LANG.sure,
                    text: LANG.confirm_delete_user,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        var data = $(this).serialize();
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            data: data,
                            success: function (result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    driver_attendence_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $('.attendence_date').datetimepicker({
                format: 'YYYY-MM-DD',
                widgetPositioning: {
                    horizontal: 'auto',
                    vertical: 'bottom'
                }
            })
        })

    </script>
@endsection
