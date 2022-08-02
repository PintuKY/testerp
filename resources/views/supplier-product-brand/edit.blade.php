<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('SupplierProductBrandController@update', [$brand->id]), 'method' => 'PUT', 'id' => 'brand_edit_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">Edit Brand</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name',  'NAME :*') !!}
          {!! Form::text('name', $brand->name, ['class' => 'form-control', 'required', 'placeholder' => 'Name']); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description',  'Description :') !!}
          {!! Form::text('description', $brand->description, ['class' => 'form-control','placeholder' => 'Description']); !!}
      </div>

        @if($is_repair_installed)
          <div class="form-group">
             <label>
                {!!Form::checkbox('use_for_repair', 1, $brand->use_for_repair, ['class' => 'input-icheck']) !!}
                {{ __( 'repair::lang.use_for_repair' )}}
            </label>
            @show_tooltip(__('repair::lang.use_for_repair_help_text'))
          </div>
        @endif

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->