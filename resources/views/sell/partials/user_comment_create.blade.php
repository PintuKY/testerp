<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('TransactionActivityController@store'), 'method' => 'post', 'id' => 'user_comment_add_form' ]) !!}

        <input type="hidden" name="transaction_id" id="transaction_ids" value="">
        <input type="hidden" name="type"  value="{{TransactionActivityTypes()['UserComment']}}">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'sale.add_user_comment' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('user_comment', __( 'sale.comment' ) . ':*') !!}

                        {!! Form::textarea('user_comment', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'sale.add_comment' ),'rows' => 3 ]); !!}
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
