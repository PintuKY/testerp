<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('IngredientController@update', [$ing->id]), 'method' => 'put', 'id' => 'selling_price_group_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'ingredient.edit_ingredient' )</h4>
    </div>

    <div class="modal-body">
        <div class="form-group">
            {!! Form::label('ingredient_parent_id', __( 'ingredient.ingredient_type' )) !!}
            {!! Form::select('ingredient_parent_id', $parent_ingredient, $ing->ingredient_parent_id, ['placeholder' => 'Select Please', 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
        </div>

      <div class="form-group">
        {!! Form::label('name', __( 'ingredient.name' ) . ':*') !!}
          {!! Form::text('name', $ing->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.name' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'ingredient.description' ) . ':') !!}
          {!! Form::textarea('description', $ing->description, ['class' => 'form-control','placeholder' => __( 'lang_v1.description' ), 'rows' => 3]); !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
