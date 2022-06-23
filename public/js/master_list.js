
//Driver table
$(document).ready( function(){
    master_table = $('#master_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']],
        "ajax": {
            "url": "/master",
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
    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #created_by',  function() {
        master_table.ajax.reload();
    });
});
$('form#master_list_edit_form').validate({
    rules: {
        cancel_reason: {
            required: true,
        },
    },
});
