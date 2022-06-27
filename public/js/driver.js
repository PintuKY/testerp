
//Driver table
$(document).ready( function(){
    sessionStorage.removeItem('filter_name');
    sessionStorage.removeItem('filter_start_date');
    sessionStorage.removeItem('filter_end_date');
    var driver_table = $('#driver_table').DataTable({
        processing: true,
        serverSide: true,
        "ajax": {
            "url": "/driver",
            "data": function ( d ) {
                if($('#driver_list_filter_date_range').val()) {
                    var start = $('#driver_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#driver_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                d.driver_name = $('#driver_list_filter_name').val();
                d = __datatable_ajax_callback(d);
            }
        },
        columnDefs: [ {
            "targets": [4],
            "orderable": false,
            "searchable": false
        } ],
        "columns":[
            {"data":"name"},
            {"data":"email"},
            {"data":"phone"},
            {"data":"address_line_1"},
            {"data":"address_line_2"},
            {"data":"city"},
            {"data":"state"},
            {"data":"country"},
            {"data":"driver_type"},
            {"data":"action"}
        ]
    });

    $('#driver_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#driver_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            driver_table.ajax.reload();
        }
    );

    $(document).on('click', 'button.delete_driver_button', function(){
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
                    success: function(result){
                        if(result.success == true){
                            toastr.success(result.msg);
                            driver_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            }
            });
    });
    $(document).on('change', '#driver_list_filter_name',  function() {
        driver_table.ajax.reload();
    });

    $(document).on('click', '.edit_all',  function() {
        if($('#driver_list_filter_date_range').val()) {
            var start = $('#driver_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
            var end = $('#driver_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
            $('#filter_start_date').val(start);
            $('#filter_end_date').val(end);
        }
        var filter_name = $('#driver_list_filter_name').val()
        $('#filter_name').val(filter_name);
        console.log(start);
        console.log(end);
        console.log(filter_name);



    });
});

$('form#driver_add_form').validate({
    rules: {
        name: {
            required: true,
        },
        email: {
            email: true,
            remote: {
                url: "/driver/check-email",
                type: "post",
                data: {
                    email: function() {
                    return $( "#email" ).val();
                    }
                }
            }
        },
        phone: {
            required: true,
            remote: {
                url: "/driver/check-mobile",
                type: "post",
                data: {
                    phone: function() {
                    return $( "#phone" ).val();
                    }
                }
            }

        },
        address_line_1: {
        required: true,
        },
        city: {
        required: true,
        },
        state: {
        required: true,
        },
        country: {
        required: true,
        },
        status: {
        required: true,
        },
    },
    messages: {
        email: {
            remote: LANG.driver_email_already_registered,
        },
        phone: {
            remote: LANG.driver_mobile_already_registered,
        }
    }
});

$('form#driver_edit_form').validate({
    rules: {
        name: {
            required: true,
        },
        email: {
            email: true,
            remote: {
                url: "/driver/check-email",
                type: "post",
                data: {
                    email: function() {return $( "#email" ).val()},
                    driver_id: function() {
                        if ($('#driver_hidden_id').length) {
                            return $('#driver_hidden_id').val();
                        } else {
                            return '';
                        }
                    },
                },
            }
        },
        phone: {
            required: true,
            remote: {
                url: "/driver/check-mobile",
                type: "post",
                data: {
                    phone: function() {return $( "#phone" ).val()},
                    driver_id: function() {
                        if ($('#driver_hidden_id').length) {
                            return $('#driver_hidden_id').val();
                        } else {
                            return '';
                        }
                    },
                },
            }
        },
        address_line_1: {
        required: true,
        },
        city: {
        required: true,
        },
        state: {
        required: true,
        },
        country: {
        required: true,
        },
        status: {
        required: true,
        },
    },
    messages: {
        email: {
            remote: LANG.driver_email_already_registered,
        },
        phone: {
            remote: LANG.driver_mobile_already_registered,
        }
    }
});
