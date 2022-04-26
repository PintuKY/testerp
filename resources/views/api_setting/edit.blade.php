<div class="modal-dialog" role="document">
    <div class="modal-content">

      {!! Form::open(['url' => action('ApiController@update', [$apisetting->id]), 'method' => 'PUT', 'id' => 'api_edit_form', 'class' => 'form-horizontal']) !!}

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">@lang('api-setting.edit_api_setting')</h4>
      </div>

    <div class="modal-body">
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('consumer_key', __('api-setting.consumer_key') . ':*') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-id-badge"></i>
                    </span>
                    {!! Form::text('consumer_key', $apisetting->consumer_key, ['class' => 'form-control','placeholder' => __('api-setting.consumer_key')]); !!}
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
                    {!! Form::text('consumer_secret', $apisetting->consumer_secret, ['class' => 'form-control','placeholder' => __('api-setting.consumer_secret')]); !!}
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
                    {!! Form::url('url', $apisetting->url, ['class' => 'form-control','placeholder' => __('api-setting.url')]); !!}
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
                    {!! Form::select('business_locations_id',$businesslocation,$apisetting->business_locations_id,['class' => 'form-control','placeholder' => __('messages.please_select')]); !!}
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                <label for="status_filter">@lang('sale.status'):</label>
                {!! Form::select('status', ['active' => __('business.is_active'), 'inactive' => __('lang_v1.inactive')], $apisetting->status, ['class' => 'form-control', 'id' => 'status_filter','placeholder' => __('lang_v1.none')]); !!}
            </div>
        </div>
    </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
      </div>

      {!! Form::close() !!}

      <script>
        $('form#api_edit_form').validate({
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
      business_id: {
          required: true,
      },

  }
});
    </script>

    </div><!-- /.modal-content -->
  </div>
