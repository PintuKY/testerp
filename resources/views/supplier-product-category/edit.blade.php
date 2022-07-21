<div class="modal-dialog" role="document">
    <div class="modal-content">
  
      {{ Form::open(['url' => route('supplier-product-categories.update',[$category->id]), 'method' => 'patch', 'id' => 'update_supplier_product_category_form' ]) }}
  
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">
            Update Category
        </h4>
      </div>
  
      <div class="modal-body">
        <div class="row">
          <div class="form-group col-sm-12">
            {{ Form::label('name', __( 'category.category_name' ) . ':*') }}
              {{ Form::text('name', $category->name, ['class' => 'form-control categoryName', 'required', 'placeholder' => __( 'category.category_name' )]); }}
          </div>
          <div class="form-group col-sm-12">
            {{ Form::label('description', __( 'category.category_name' ) . ':*') }}
              {{ Form::textarea('description', $category->description, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.description' )]); }}
          </div>
        </div>
      </div>
  
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
      </div>
  
      {{ Form::close() }}
  
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->