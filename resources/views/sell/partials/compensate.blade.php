<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('MasterController@store'), 'method' => 'post', 'id' => 'compensate' ]) !!}

        <input type="hidden" name="transaction_id" id="transaction_ids" value="">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'master.add_compensate' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="time_slot">Meal Type:*</label>
                        <div class="form-group">
                            <select class="form-control select2" id="time_slot"
                                    name="time_slot"
                                    required>
                                <option selected>please select</option>
                                @foreach(compensateMealTypes() as $key => $deliveryDays)
                                    <option value="{{$key}}">{{ $deliveryDays }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div
                    class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('delivery_date', __('sale.delivery_date') . ':*') !!}
                        <div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
                            {!! Form::text("delivery_date", $default_datetime, ['class' => 'form-control delivery_dates', 'required']); !!}
                        </div>
                    </div>
                </div>



            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
