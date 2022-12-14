<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('VariationTemplateController@store'), 'method' => 'post', 'id' => 'variation_add_form', 'class' => 'form-horizontal' ]) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('lang_v1.add_variation')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('name',__('lang_v1.variation_name') . ':*', ['class' => 'col-sm-3 control-label']) !!}

                <div class="col-sm-9">
                    {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.variation_name')]); !!}
                </div>
            </div>

            <div class="form-group">
                <label for="name" class="col-sm-3 control-label">Variation Type:*</label>

                <div class="col-sm-9">
                    <select class="form-control select2" style="width: 100%;" id="type" name="type">
                        <option selected>Please Select Type</option>
                        @foreach(variationTypes() as $key => $variationTypes)
                            <option value="{{$key}}">{{$variationTypes}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group row variations_values_componets">
                <div class="col-sm-5">
                    <label class=" control-label">@lang('lang_v1.add_variation_values'):*</label>
                    {!! Form::text('variation_values[]', null, ['class' => 'form-control', 'required']); !!}
                </div>
                <div class="col-sm-5">
                    <label class="control-label">@lang('price'):*</label>
                    {!! Form::text('variation_values_price[]', null, ['class' => 'form-control input_number input-sm dpp valid','required']); !!}
                </div>
                <div class="col-sm-2">
                    <button type="button" class="btn btn-primary" id="add_variation_values">+</button>
                </div>
            </div>
            <div id="variation_values"></div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
