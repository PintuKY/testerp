
//Driver table
$(document).ready( function(){
    var driver_table = $('#driver_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/driver',
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
            {"data":"action"}
        ]
    });
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
