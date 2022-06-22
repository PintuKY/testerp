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
