<div class="modal-dialog" role="document">
    <div class="modal-content">
  
      {{ Form::open(['url' => route('supplierProductCategories.store'), 'method' => 'post', 'id' => 'quick_add_caregory_form' ]) }}
  
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">
            @lang('category.add_category')
        </h4>
      </div>
  
      <div class="modal-body">
        <div class="row">
          <div class="form-group col-sm-12">
            {{ Form::label('name', __( 'category.category_name' ) . ':*') }}
              {{ Form::text('name', null, ['class' => 'form-control categoryName', 'required', 'placeholder' => __( 'category.category_name' )]); }}
          </div>
          <div class="form-group col-sm-12">
            {{ Form::label('description', __( 'category.category_name' ) . ':*') }}
              {{ Form::textarea('description', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.description' )]); }}
          </div>
        </div>
      </div>
  
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
      </div>
  
      {{ Form::close() }}
  <script>
    var categories = {!!json_encode($categories);!!} 
    $.validator.addMethod("categoryName", 
    function(value, element) {
      var matches = new Array();
      $.each(categories, function( index, data ) {
        if(data == value) {
          matches.push(data);
        }
      })
      return matches.length == 0
    }, "Category already exist");

  $('#quick_add_caregory_form')
    .submit(function (e) {
        e.preventDefault();
        console.log(' validation');
    })
    .validate({
        rules: {
            name: {
                required: true,

            },
            description: {
                required: true,
            },   
        },
        submitHandler: function(form) {  
          submitCategoryForm(form);
        }
   });

   function submitCategoryForm(form){
    var data = $('#quick_add_caregory_form').serialize();
    $.ajax({
        method: 'POST',
        url: $('#quick_add_caregory_form').attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function(xhr) {
            __disable_submit_button($('#quick_add_caregory_form').find('button[type="submit"]'));
        },
        success: function(result) {
            if (result.success == true) {
                var newOption = new Option(result.data.name, result.data.id, true, true);
                // Append it to the select
                $('#category_id')
                    .append(newOption)
                    .trigger('change');
                $('div.add_category_modal').modal('hide');
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    })
}
  
  </script>
  
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->