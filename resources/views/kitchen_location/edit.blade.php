{!! Form::open(['url' => action('KitchenLocationController@update',[$kitchens->id]), 'method' => 'PUT','id'=>'kitchen_location_edit_form']) !!}
<div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang( 'kitchen.update_kitchen_location' )</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                        {!! Form::label('name', __( 'invoice.name' ) . ':') !!}
                            {!! Form::text('name', $kitchens->name, ['class' => 'form-control','required','placeholder' => __( 'invoice.name' ) ]); !!}
                        </div>
                    </div>
                    
                    <div class="clearfix"></div>

                    <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('landmark', __( 'business.landmark' ) . ':') !!}
                        {!! Form::text('landmark', $kitchens->landmark, ['class' => 'form-control','required', 'placeholder' => __( 'business.landmark' ) ]); !!}
                    </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('email', __( 'business.email' ) . ':') !!}
                            {!! Form::email('email', $kitchens->email, ['class' => 'form-control','required' ,'placeholder' => __( 'business.email')]); !!}
                        </div>
                        </div>

                    <div class="clearfix"></div>
                    <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('city', __( 'business.city' ) . ':') !!}
                        {!! Form::text('city', $kitchens->city, ['class' => 'form-control','required', 'placeholder' => __( 'business.city') ]); !!}
                    </div>
                    </div>
                    <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('state', __( 'business.state' ) . ':') !!}
                        {!! Form::text('state', $kitchens->state, ['class' => 'form-control', 'placeholder' => __( 'business.state'), 'required' ]); !!}
                    </div>
                    </div>

                    <div class="clearfix"></div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('country', __( 'business.country' ) . ':') !!}
                            {!! Form::text('country', $kitchens->country, ['class' => 'form-control', 'placeholder' => __( 'business.country'), 'required' ]); !!}
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('zip_code', __( 'business.zip_code' ) . ':') !!}
                            {!! Form::text('zip_code', $kitchens->zip_code, ['class' => 'form-control', 'placeholder' => __( 'business.zip_code'), 'required' ]); !!}
                        </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('mobile', __( 'business.mobile' ) . ':') !!}
                        {!! Form::text('mobile', $kitchens->mobile, ['class' => 'form-control','required','placeholder' => __( 'business.mobile')]); !!}
                    </div>
                    </div>
                    <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('alternate_number', __( 'business.alternate_number' ) . ':') !!}
                        {!! Form::text('alternate_number', $kitchens->alternate_number, ['class' => 'form-control', 'placeholder' => __( 'business.alternate_number')]); !!}
                    </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
                    </div>
                </div>
            </div>
        </div>
</div>
{!! Form::close() !!}
<script>
    $('form#kitchen_location_edit_form').validate({
        name:{
            required:true,
        },
        landmark:{
            required:true,
        },
        email:{
            required:true,
        },
        city:{
            required:true,
        },
        state:{
            required:true,
        },
        country:{
            required:true,
        },
        zip_code:{
            required:true,
        },
        mobile:{
            required:true,
        },
    })
</script>
