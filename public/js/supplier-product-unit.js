var units_table = $('#supplier_product_unit_table').DataTable({
    processing: true,
    serverSide: true,
    ajax: '/supplier-product-units',
    columnDefs: [
        {
            targets: 2,
            orderable: false,
            searchable: false,
        },
    ],
    columns: [
        {data: 'name', name: 'name'},
        {data: 'short_name', name: 'short_name'},
        {data: 'action', name: 'action'},
    ],
});

$(document).on('submit', 'form#supplier_product_unit_add_form', function (e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();

    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function (xhr) {
            __disable_submit_button(form.find('button[type="submit"]'));
        },
        success: function (result) {
            if (result.success == true) {
                $('div.add_unit_modal').modal('hide');
                toastr.success(result.msg);
                units_table.ajax.reload();
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

$(document).on('click', 'button.edit_supplier_product_unit_button', function () {
    $('div.edit_unit_modal').load($(this).data('href'), function () {
        $(this).modal('show');

        $('form#edit_supplier_product_unit_form').submit(function (e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();
            console.log($(this).attr('action'));
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                beforeSend: function (xhr) {
                    __disable_submit_button(form.find('button[type="submit"]'));
                },
                success: function (result) {
                    if (result.success == true) {
                        $('div.edit_unit_modal').modal('hide');
                        toastr.success(result.msg);
                        units_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    });
});

$(document).on('click', 'button.delete_supplier_product_unit_button', function () {
    swal({
        title: LANG.sure,
        text: LANG.confirm_delete_unit,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then(willDelete => {
        if (willDelete) {
            var href = $(this).data('href');
            var data = $(this).serialize();

            $.ajax({
                method: 'DELETE',
                url: href,
                dataType: 'json',
                data: data,
                success: function (result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        units_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});