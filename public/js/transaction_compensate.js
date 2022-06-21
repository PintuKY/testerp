$(document).ready(function () {

    //transaction activity
    transaction_activity = $('#transaction_activity').DataTable({
        processing: true,
        serverSide: true,
        bPaginate: false,
        buttons: [],
        ajax: '/sells/transaction-activity/{{$transaction->id}}',
        columns: [
            {data: 'created_at', name: 'created_at'},
            {data: 'comment', name: 'comment'},
            {data: 'user_comment', name: 'user_comment'},
            {data: 'action', name: 'action'},
        ],
    });
    $('.transaction_activity_add_modals').on('shown.bs.modal', function (e) {
        $('.start_dates').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });
        $('form#user_comment_add_form')
            .submit(function (e) {
                e.preventDefault();
            })
            .validate({
                rules: {
                    user_comment: {
                        required: true,
                    },
                },
                submitHandler: function (form) {
                    e.preventDefault();
                    var data = $(form).serialize();

                    $.ajax({
                        method: 'POST',
                        url: $(form).attr('action'),
                        dataType: 'json',
                        data: data,
                        beforeSend: function (xhr) {
                            __disable_submit_button($(form).find('button[type="submit"]'));
                        },
                        success: function (result) {
                            if (result.success == true) {
                                $('div.transaction_activity_add_modals').modal('hide');
                                toastr.success(result.msg);
                                transaction_activity.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                },
            });


    });


    var transaction_id = $('#transaction_id').val();
    master_table = $('#master_table').DataTable({
        processing: true,
        serverSide: true,
        bPaginate: true,
        buttons: [],
        "ajax": {
            "url": "/master_list/"+transaction_id,
            "data": {},
        },
        columnDefs: [ {
            "targets": [9, 10],
            "orderable": false,
            "searchable": false
        } ],
        columns: [
            { data: 'id', name: 'id'  },
            { data: 'contacts_name', name: 'contacts_name'},
            { data: 'shipping_address_line_1', name: 'shipping_address_line_1'},
            { data: 'shipping_zip_code', name: 'shipping_zip_code'},
            { data: 'pax', name: 'pax'},
            { data: 'addon', name: 'addon'},
            { data: 'delivery_note', name: 'delivery_note'},
            { data: 'delivery_date', name: 'delivery_date'},
            { data: 'staff_notes', name: 'staff_notes'},
            { data: 'hp_number', name: 'hp_number'},
            { data: 'cancel_reason', name: 'cancel_reason'},
            { data: 'compensate', name: 'compensate'},
            { data: 'driver_name', name: 'driver_name'},
            { data: 'action', name: 'action'}
        ],

    });
    $('.master_list_compensate_add_modals').on('shown.bs.modal', function (e) {
        var dtes = new Date();
        dtes.setDate(dtes.getDate() + 1);
        $('.delivery_dates').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            minDate: dtes,
            widgetPositioning:{
                horizontal: 'auto',
                vertical: 'bottom'
            }
        });
        $('form#compensate')
            .submit(function (e) {
                e.preventDefault();
            })
            .validate({
                rules: {
                    cancel_reason: {
                        required: true,
                    },
                },
                submitHandler: function (form) {
                    e.preventDefault();
                    var data = $(form).serialize();

                    $.ajax({
                        method: 'POST',
                        url: $(form).attr('action'),
                        dataType: 'json',
                        data: data,
                        beforeSend: function (xhr) {
                            __disable_submit_button($(form).find('button[type="submit"]'));
                        },
                        success: function (result) {
                            if (result.success == true) {
                                $('div.master_list_compensate_add_modals').modal('hide');
                                toastr.success(result.msg);
                                master_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                },
            });


    });
    //delete trasaction activity
    $(document).on('click', '.delete-activity', function (e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).attr('href');
                var is_suspended = $(this).hasClass('is_suspended');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function (result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            transaction_activity.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
    $('#shipping_documents').fileinput({
        showUpload: false,
        showPreview: false,
        browseLabel: LANG.file_browse_label,
        removeLabel: LANG.remove,
    });

    $('#is_export').on('change', function () {
        if ($(this).is(':checked')) {
            $('div.export_div').show();
        } else {
            $('div.export_div').hide();
        }
    });

    $('#status').change(function () {
        if ($(this).val() == 'final') {
            $('#payment_rows_div').removeClass('hide');
        } else {
            $('#payment_rows_div').addClass('hide');
        }
    });
    $('.paid_on').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });
});
$('.start_dates').datetimepicker({
    format: moment_date_format + ' ' + moment_time_format,
    ignoreReadonly: true,
});
