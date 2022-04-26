<div class="modal-dialog" role="document">
    <div class="modal-content">
      {!! Form::open(['url' => action('ApiController@store'), 'method' => 'post', 'id' => 'api_add_form', 'class' => 'form-horizontal' ]) !!}

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">@lang('api-setting.add_api_setting')</h4>
      </div>

      <div class="modal-body">
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('consumer_key', __('api-setting.consumer_key') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-id-badge"></i>
                    </span>
                    {!! Form::text('consumer_key', null, ['class' => 'form-control', 'placeholder' => __('api-setting.consumer_key')]); !!}
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('consumer_secret', __('api-setting.consumer_secret') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-id-badge"></i>
                    </span>
                    {!! Form::text('consumer_secret', null, ['class' => 'form-control','placeholder' => __('api-setting.consumer_secret')]); !!}
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('url', __('api-setting.url') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-id-badge"></i>
                    </span>
                    {!! Form::url('url', null, ['class' => 'form-control','placeholder' => __('api-setting.url')]); !!}
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('business_locations_id', __('api-setting.business_name') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-id-badge"></i>
                    </span>
                    {!! Form::select('business_locations_id',$businesslocation,null,['class' => 'form-control','placeholder' => __('messages.please_select')]); !!}
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="status_filter">@lang('sale.status'):</label>
                {!! Form::select('status', ['active' => __('business.is_active'), 'inactive' => __('lang_v1.inactive')], null, ['class' => 'form-control', 'id' => 'status_filter']); !!}
            </div>
        </div>
    </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
      </div>

      {!! Form::close() !!}

      <script>
          $('form#api_add_form').validate({
    rules: {
        consumer_key: {
            required: true,
        },
        consumer_secret: {
            required: true,
        },
        url: {
            required: true,
        },
        business_locations_id:{
            required: true,
        }

    }
});
      </script>
    </div><!-- /.modal-content -->
  </div>
