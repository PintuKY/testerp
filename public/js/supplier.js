//Start: CRUD for supplier
//supplier table
    var columns = [
        { data: 'action', searchable: false, orderable: false },
        { data: 'supplier_id', name: 'supplier_id' },
        { data: 'supplier_business_name', name: 'supplier_business_name' },
        { data: 'name', name: 'name' },
        { data: 'email', name: 'email' },
        { data: 'tax_number', name: 'tax_number' },
        { data: 'pay_term', name: 'pay_term', searchable: false, orderable: false },
        { data: 'opening_balance', name: 'opening_balance', searchable: false },
        { data: 'balance', name: 'balance', searchable: false },
        { data: 'created_at', name: 'supplier.created_at' },
        { data: 'address', name: 'address', orderable: false },
        { data: 'mobile', name: 'mobile' },
        { data: 'due', searchable: false, orderable: false },
        { data: 'return_due', searchable: false, orderable: false },
    ];

    supplier_table = $('#supplier_table').DataTable({
        processing: true,
        serverSide: true,
        scrollY:        "75vh",
        scrollX:        true,
        scrollCollapse: true,
        "ajax": {
            "url": "/supplier",
            "data": function ( d ) {
                // d.type = $('#contact_type').val();
                d = __datatable_ajax_callback(d);

                if ($('#has_sell_due').length > 0 && $('#has_sell_due').is(':checked')) {
                    d.has_sell_due = true;
                }

                if ($('#has_sell_return').length > 0 && $('#has_sell_return').is(':checked')) {
                    d.has_sell_return = true;
                }

                if ($('#has_purchase_due').length > 0 && $('#has_purchase_due').is(':checked')) {
                    d.has_purchase_due = true;
                }

                if ($('#has_purchase_return').length > 0 && $('#has_purchase_return').is(':checked')) {
                    d.has_purchase_return = true;
                }

                if ($('#has_advance_balance').length > 0 && $('#has_advance_balance').is(':checked')) {
                    d.has_advance_balance = true;
                }

                if ($('#has_opening_balance').length > 0 && $('#has_opening_balance').is(':checked')) {
                    d.has_opening_balance = true;
                }

                if ($('#has_no_sell_from').length > 0) {
                    d.has_no_sell_from = $('#has_no_sell_from').val();
                }

                if ($('#cg_filter').length > 0) {
                    d.customer_group_id = $('#cg_filter').val();
                }

                if ($('#status_filter').length > 0) {
                    d.supplier_status = $('#status_filter').val();
                }
            }
        },
        aaSorting: [[1, 'desc']],
        columns: columns,
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#supplier_table'));
        },
        "footerCallback": function ( row, data, start, end, display ) {
            var total_due = 0;
            var total_return_due = 0;
            for (var r in data){
                total_due += $(data[r].due).data('orig-value') ? 
                parseFloat($(data[r].due).data('orig-value')) : 0;

                total_return_due += $(data[r].return_due).data('orig-value') ? 
                parseFloat($(data[r].return_due).data('orig-value')) : 0;
            }
            $('.footer_contact_due').html(__currency_trans_from_en(total_due));
            $('.footer_contact_return_due').html(__currency_trans_from_en(total_return_due));
        }
    });

// On display of add supplier modal
$('.supplier_modal').on('shown.bs.modal', function(e) {
    $('input[type=radio][name="supplier_type_radio"]').on('change', function() {
        if (this.value == 'individual') {
            $('div.individual').show();
            $('div.business').hide();
        } else if (this.value == 'business') {
            $('div.individual').hide();
            $('div.business').show();
        }
    });
    if ($('#is_customer_export').is(':checked')) {
        $('div.export_div').show();
    }
    $('#is_customer_export').on('change', function () {
        if ($(this).is(':checked')) {
            $('div.export_div').show();
        } else {
            $('div.export_div').hide();
        }
    });

    $('.more_btn').click(function(){
        $($(this).data('target')).toggleClass('hide');
    });
    $('div.lead_additional_div').hide();

    if ($('select#contact_type').val() == 'customer') {
        $('div.supplier_fields').hide();
        $('div.customer_fields').show();
    } else if ($('select#contact_type').val() == 'supplier') {
        $('div.supplier_fields').show();
        $('div.customer_fields').hide();
    }  else if ($('select#contact_type').val() == 'lead') {
        $('div.supplier_fields').hide();
        $('div.customer_fields').hide();
        $('div.opening_balance').hide();
        $('div.pay_term').hide();
        $('div.lead_additional_div').show();
        $('div.shipping_addr_div').hide();
    }

    // $('select#contact_type').change(function() {
    //     var t = $(this).val();

    //     if (t == 'supplier') {
    //         $('div.supplier_fields').fadeIn();
    //         $('div.customer_fields').fadeOut();
    //     } else if (t == 'both') {
    //         $('div.supplier_fields').fadeIn();
    //         $('div.customer_fields').fadeIn();
    //     } else if (t == 'customer') {
    //         $('div.customer_fields').fadeIn();
    //         $('div.supplier_fields').fadeOut();
    //     } else if (t == 'lead') {
    //         $('div.customer_fields').fadeOut();
    //         $('div.supplier_fields').fadeOut();
    //         $('div.opening_balance').fadeOut();
    //         $('div.pay_term').fadeOut();
    //         $('div.lead_additional_div').fadeIn();
    //         $('div.shipping_addr_div').hide();
    //     }
    // });

    $(".supplier_modal").find('.select2').each( function(){
        $(this).select2();
    });

    $('form#supplier_add_form, form#supplier_edit_form')
        .submit(function(e) {
            e.preventDefault();
        })
        .validate({
            rules: {
                supplier_id: {
                    remote: {
                        url: '/supplier/check-supplier-id',
                        type: 'post',
                        data: {
                            supplier_id: function() {
                                return $('#supplier_id').val();
                            },
                            hidden_id: function() {
                                if ($('#hidden_id').length) {
                                    return $('#hidden_id').val();
                                } else {
                                    return '';
                                }
                            },
                        },
                    },
                },
            },
            messages: {
                supplier_id: {
                    remote: LANG.supplier_id_already_exists,
                },
            },
            submitHandler: function(form) {
                e.preventDefault();
                $.ajax({
                    method: 'POST',
                    url: base_path + '/supplier/check-mobile',
                    dataType: 'json',
                    data: {
                        supplier_id: function() {
                            return $('#hidden_id').val();
                        },
                        mobile_number: function() {
                            return $('#mobile').val();
                        },
                    },
                    beforeSend: function(xhr) {
                        __disable_submit_button($(form).find('button[type="submit"]'));
                    },
                    success: function(result) {
                        if (result.is_mobile_exists == true) {
                            swal({
                                title: LANG.sure,
                                text: result.msg,
                                icon: 'warning',
                                buttons: true,
                                dangerMode: true
                            }).then(willContinue => {
                                if (willContinue) {
                                    submitSupplierForm(form);
                                } else {
                                    $('#mobile').select();
                                }
                            });
                            
                        } else {
                            submitSupplierForm(form);
                        }
                    },
                });
            },
        });

        $('#supplier_add_form').trigger('supplierFormvalidationAdded');
});

$(document).on('click', '.edit_supplier_button', function(e) {
    e.preventDefault();
    $('div.supplier_modal').load($(this).attr('href'), function() {
        $(this).modal('show');
    });
});

$(document).on('click', '.delete_supplier_button', function(e) {
    e.preventDefault();
    swal({
        title: LANG.sure,
        text: LANG.confirm_delete_supplier,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then(willDelete => {
        if (willDelete) {
            var href = $(this).attr('href');
            var data = $(this).serialize();

            $.ajax({
                method: 'DELETE',
                url: href,
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        supplier_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});

function submitSupplierForm(form) {
    var data = $(form).serialize();
    $.ajax({
        method: 'POST',
        url: $(form).attr('action'),
        dataType: 'json',
        data: data,
        success: function (result) {
            if (result.success == true) {
                $('div.supplier_modal').modal('hide');
                console.log(supplier_table);
                supplier_table.ajax.reload();
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    });
}

$(document).on('ifChanged', '#has_sell_due, #has_sell_return, \
#has_purchase_due, #has_purchase_return, #has_advance_balance, #has_opening_balance', function () {
    supplier_table.ajax.reload();
});

$(document).on('change', '#has_no_sell_from, #cg_filter, #status_filter', function () {
    supplier_table.ajax.reload();
});