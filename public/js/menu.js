$(document).ready(function () {
    if ($('#edit_menu').length) {
        $('form#edit_menu').validate({
            rules: {
                category_id: {required: true},
                business_location_id: {required: true},
                recipe_id: {required: true},
                name: {required: true},
            },
        });
    }

    if ($('#product_add_form').length) {
        $('form#product_add_form').validate({
            rules: {
                category_id: {required: true},
                business_location_id: {required: true},
                recipe_id: {required: true},
                name: {required: true},
            },
        });
    }
    $(document).on('click', '.submit_product_form', function (e) {
        e.preventDefault();
        var submit_type = $(this).attr('value');
        $('#submit_type').val(submit_type);
        if ($('form#product_add_form').valid()) {

            $('form#product_add_form').submit();
        }else{
            toastr.error('Please enter value.');
        }
    });
    $(document).on('click', '.submit_menu_form', function (e) {
        e.preventDefault();
        var submit_type = $(this).attr('value');
        $('#submit_type').val(submit_type);
        if ($('form#edit_menu').valid()) {

            $('form#edit_menu').submit();
        }else{
            toastr.error('Please enter value.');
        }
    });

});
