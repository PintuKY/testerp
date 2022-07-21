var category_table = $('#supplier_product_category_table').DataTable({
    processing: true,
    serverSide: true,
    ajax: '/supplier-product-categories',
    columnDefs: [
        {
            targets: 2,
            orderable: false,
            searchable: false,
        },
    ],
    columns: [
        {data: 'name', name: 'name'},
        {data: 'description', name: 'description'},
        {data: 'action', name: 'action'},
    ],
});

$(document).on('submit', 'form#add_supplier_category_form', function (e) {
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
                $('div.supplier_product_category_modal').modal('hide');
                toastr.success(result.msg);
                category_table.ajax.reload();
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

$(document).on('click', 'button.edit_supplier_product_category_button', function () {
    $('div.update_supplier_product_category_modal').load($(this).data('href'), function () {
        $(this).modal('show');

        $('form#update_supplier_product_category_form').submit(function (e) {
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
                        $('div.update_supplier_product_category_modal').modal('hide');
                        toastr.success(result.msg);
                        category_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    });
});

$(document).on('click', 'button.delete_supplier_product_category_button', function () {
    console.log(123+'delete')
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
                        category_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});