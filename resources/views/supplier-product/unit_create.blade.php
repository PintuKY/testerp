<div class="modal-dialog" role="document">
  <div class="modal-content">

    {{ Form::open(['url' => route('supplierProductUnits.store'), 'method' => 'post', 'id' => 'quick_add_unit_form' ]) }}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'unit.add_unit' )</h4>
    </div>

    <div class="modal-body">
      <div class="row">
        <div class="form-group col-sm-12">
          {{ Form::label('name', __( 'unit.name' ) . ':*') }}
            {{ Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'unit.name' )]); }}
        </div>

        <div class="form-group col-sm-12">
          {{ Form::label('short_name', __( 'unit.short_name' ) . ':*') }}
            {{ Form::text('short_name', null, ['class' => 'form-control', 'placeholder' => __( 'unit.short_name' ), 'required']); }}
        </div>

          <div class="form-group col-sm-12">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                     {{ Form::checkbox('define_base_unit', 1, false,[ 'class' => 'toggler', 'data-toggle_id' => 'base_unit_div' ]); }} @lang( 'lang_v1.add_as_multiple_of_base_unit' )
                  </label> @show_tooltip(__('lang_v1.multi_unit_help'))
                </div>
            </div>
          </div>
          <div class="form-group col-sm-12 hide" id="base_unit_div">
            <table class="table">
              <tr>
                <th style="vertical-align: middle;">1 <span id="unit_name">@lang('product.unit')</span></th>
                <th style="vertical-align: middle;">=</th>
                <td style="vertical-align: middle;">
                  {{ Form::text('base_unit_multiplier', null, ['class' => 'form-control input_number', 'placeholder' => __( 'lang_v1.times_base_unit' )]); }}</td>
                <td style="vertical-align: middle;">
                  {{ Form::select('base_unit_id', $units, null, ['placeholder' => __( 'lang_v1.select_base_unit' ), 'class' => 'form-control']); }}
                </td>
              </tr>
            </table>
          </div>
      </div>

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

{{ Form::close() }}

<script>
$('#quick_add_unit_form')
            .submit(function (e) {
                e.preventDefault();
                console.log(' validation');
            })
            .validate({
                rules: {
                    name: {
                        required: true,
                    },
                    short_name: {
                        required: true,
                    },
                    base_unit_id: {
                        required:'.toggler:checked'
                    },
                    base_unit_multiplier: {
                        required: '.toggler:checked',
                        digits: true
                    },
                        
                },
                submitHandler: function(form) {  
                  submitUnitForm(form);
                }
 });

function submitUnitForm(form){
    var data = $('#quick_add_unit_form').serialize();
    $.ajax({
        method: 'POST',
        url: $('#quick_add_unit_form').attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function(xhr) {
            __disable_submit_button($('#quick_add_unit_form').find('button[type="submit"]'));
        },
        success: function(result) {
            if (result.success == true) {
                var newOption = new Option(result.data.name, result.data.id, true, true);
                // Append it to the select
                $('#unit_id')
                    .append(newOption)
                    .trigger('change');
                $('div.add_unit_modal').modal('hide');
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