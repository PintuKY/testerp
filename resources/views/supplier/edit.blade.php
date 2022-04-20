<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">

  @php

    if(isset($update_action)) {
        $url = $update_action;
        $customer_groups = [];
        $opening_balance = 0;
        $lead_users = $supplier->leadUsers->pluck('id');
    } else {
      $url = action('SupplierController@update', [$supplier->id]);
      $sources = [];
      $life_stages = [];
      $users = [];
      $lead_users = [];
    }
  @endphp

    {!! Form::open(['url' => $url, 'method' => 'PUT', 'id' => 'supplier_edit_form']) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('supplier.edit_supplier')</h4>
    </div>

    <div class="modal-body">

      <div class="row">

        {{-- <div class="col-md-4">
          <div class="form-group">
              {!! Form::label('type', __('contact.contact_type') . ':*' ) !!}
              <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-user"></i>
                  </span>
                  {!! Form::select('type', $types, $supplier->type, ['class' => 'form-control', 'id' => 'contact_type','placeholder' => __('messages.please_select'), 'required']); !!}
              </div>
          </div>
        </div> --}}
        <div class="col-md-4 mt-15">
            <label class="radio-inline">
                <input type="radio" name="contact_type_radio" id="inlineRadio1" value="individual">
                @lang('lang_v1.individual')
            </label>
            <label class="radio-inline">
                <input type="radio" name="contact_type_radio" id="inlineRadio2" value="business">
                @lang('business.business')
            </label>
        </div>
        <div class="col-md-4">
          <div class="form-group">
              {!! Form::label('supplier_id', __('lang_v1.supplier_id') . ':') !!}
              <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-id-badge"></i>
                  </span>
                  <input type="hidden" id="hidden_id" value="{{$supplier->id}}">
                  {!! Form::text('supplier_id', $supplier->supplier_id, ['class' => 'form-control','placeholder' => __('lang_v1.supplier_id')]); !!}
              </div>
              <p class="help-block">
                @lang('lang_v1.leave_empty_to_autogenerate')
            </p>
          </div>
        </div>
        {{-- <div class="col-md-4 customer_fields">
          <div class="form-group">
              {!! Form::label('customer_group_id', __('lang_v1.customer_group') . ':') !!}
              <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-users"></i>
                  </span>
                  {!! Form::select('customer_group_id', $customer_groups, $supplier->customer_group_id, ['class' => 'form-control']); !!}
              </div>
          </div>
        </div> --}}
        <div class="clearfix customer_fields"></div>
        <div class="col-md-4 business">
          <div class="form-group">
              {!! Form::label('supplier_business_name', __('business.business_name') . ':') !!}
              <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-briefcase"></i>
                  </span>
                  {!! Form::text('supplier_business_name', 
                  $supplier->supplier_business_name, ['class' => 'form-control', 'placeholder' => __('business.business_name')]); !!}
              </div>
          </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-3 individual">
                <div class="form-group">
                    {!! Form::label('prefix', __( 'business.prefix' ) . ':') !!}
                    {!! Form::text('prefix', $supplier->prefix, ['class' => 'form-control', 'placeholder' => __( 'business.prefix_placeholder' ) ]); !!}
                </div>
            </div>
            <div class="col-md-3 individual">
                <div class="form-group">
                    {!! Form::label('first_name', __( 'business.first_name' ) . ':*') !!}
                    {!! Form::text('first_name', $supplier->first_name, ['class' => 'form-control', 'required', 'placeholder' => __( 'business.first_name' ) ]); !!}
                </div>
            </div>
            <div class="col-md-3 individual">
                <div class="form-group">
                    {!! Form::label('middle_name', __( 'lang_v1.middle_name' ) . ':') !!}
                    {!! Form::text('middle_name', $supplier->middle_name, ['class' => 'form-control', 'placeholder' => __( 'lang_v1.middle_name' ) ]); !!}
                </div>
            </div>
            <div class="col-md-3 individual">
                <div class="form-group">
                    {!! Form::label('last_name', __( 'business.last_name' ) . ':') !!}
                    {!! Form::text('last_name', $supplier->last_name, ['class' => 'form-control', 'placeholder' => __( 'business.last_name' ) ]); !!}
                </div>
            </div>
            <div class="clearfix"></div>

      <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('mobile', __('supplier.mobile') . ':*') !!}
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-mobile"></i>
                </span>
                {!! Form::text('mobile', $supplier->mobile, ['class' => 'form-control', 'required', 'placeholder' => __('supplier.mobile')]); !!}
            </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('alternate_number', __('supplier.alternate_contact_number') . ':') !!}
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-phone"></i>
                </span>
                {!! Form::text('alternate_number', $supplier->alternate_number, ['class' => 'form-control', 'placeholder' => __('supplier.alternate_contact_number')]); !!}
            </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('landline', __('supplier.landline') . ':') !!}
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-phone"></i>
                </span>
                {!! Form::text('landline', $supplier->landline, ['class' => 'form-control', 'placeholder' => __('supplier.landline')]); !!}
            </div>
        </div>
      </div>
      <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('email', __('business.email') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-envelope"></i>
                    </span>
                    {!! Form::email('email', $supplier->email, ['class' => 'form-control','placeholder' => __('business.email')]); !!}
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group individual">
                {!! Form::label('dob', __('lang_v1.dob') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </span>
                    
                    {!! Form::text('dob', !empty($supplier->dob) ? @format_date($supplier->dob) : null, ['class' => 'form-control dob-date-picker','placeholder' => __('lang_v1.dob'), 'readonly']); !!}
                </div>
            </div>
        </div>
        
        <!-- lead additional field -->
        <div class="col-md-4 lead_additional_div">
          <div class="form-group">
              {!! Form::label('crm_source', __('lang_v1.source') . ':' ) !!}
              <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fas fa fa-search"></i>
                  </span>
                  {!! Form::select('crm_source', $sources, $supplier->crm_source , ['class' => 'form-control', 'id' => 'crm_source','placeholder' => __('messages.please_select')]); !!}
              </div>
          </div>
        </div>
        <div class="col-md-4 lead_additional_div">
          <div class="form-group">
              {!! Form::label('crm_life_stage', __('lang_v1.life_stage') . ':' ) !!}
              <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fas fa fa-life-ring"></i>
                  </span>
                  {!! Form::select('crm_life_stage', $life_stages, $supplier->crm_life_stage , ['class' => 'form-control', 'id' => 'crm_life_stage','placeholder' => __('messages.please_select')]); !!}
              </div>
          </div>
        </div>
        <div class="col-md-6 lead_additional_div">
          <div class="form-group">
              {!! Form::label('user_id', __('lang_v1.assigned_to') . ':*' ) !!}
              <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-user"></i>
                  </span>
                  {!! Form::select('user_id[]', $users, $lead_users , ['class' => 'form-control select2', 'id' => 'user_id', 'multiple', 'required', 'style' => 'width: 100%;']); !!}
              </div>
          </div>
        </div>

        <div class="col-md-12">
            <button type="button" class="btn btn-primary center-block more_btn" data-target="#more_div">@lang('lang_v1.more_info') <i class="fa fa-chevron-down"></i></button>
        </div>
        
        <div id="more_div" class="hide">

            <div class="col-md-12"><hr/></div>
        
        <div class="col-md-4">
          <div class="form-group">
              {!! Form::label('tax_number', __('supplier.tax_no') . ':') !!}
              <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-info"></i>
                  </span>
                  {!! Form::text('tax_number', $supplier->tax_number, ['class' => 'form-control', 'placeholder' => __('supplier.tax_no')]); !!}
              </div>
          </div>
        </div>

        
        <div class="col-md-4 opening_balance">
          <div class="form-group">
              {!! Form::label('opening_balance', __('lang_v1.opening_balance') . ':') !!}
              <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fas fa-money-bill-alt"></i>
                  </span>
                  {!! Form::text('opening_balance', $opening_balance, ['class' => 'form-control input_number']); !!}
              </div>
          </div>
        </div>

        <div class="col-md-4 pay_term">
          <div class="form-group">
            <div class="multi-input">
              {!! Form::label('pay_term_number', __('supplier.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
              <br/>
              {!! Form::number('pay_term_number', $supplier->pay_term_number, ['class' => 'form-control width-40 pull-left', 'placeholder' => __('supplier.pay_term')]); !!}

              {!! Form::select('pay_term_type', ['months' => __('lang_v1.months'), 'days' => __('lang_v1.days')], $supplier->pay_term_type, ['class' => 'form-control width-60 pull-left','placeholder' => __('messages.please_select')]); !!}
            </div>
          </div>
        </div>
        <div class="clearfix"></div>
        
        <div class="col-md-4 customer_fields">
          <div class="form-group">
              {!! Form::label('credit_limit', __('lang_v1.credit_limit') . ':') !!}
              <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fas fa-money-bill-alt"></i>
                  </span>
                  {!! Form::text('credit_limit', $supplier->credit_limit != null ? @num_format($supplier->credit_limit) : null, ['class' => 'form-control input_number']); !!}
              </div>
              <p class="help-block">@lang('lang_v1.credit_limit_help')</p>
          </div>
        </div>
          
      <div class="col-md-12">
        <hr/>
      </div>
      
      <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('address_line_1', __('lang_v1.address_line_1') . ':') !!}
            {!! Form::text('address_line_1', $supplier->address_line_1, ['class' => 'form-control', 'placeholder' => __('lang_v1.address_line_1'), 'rows' => 3]); !!}
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('address_line_2', __('lang_v1.address_line_2') . ':') !!}
            {!! Form::text('address_line_2', $supplier->address_line_2, ['class' => 'form-control', 
                'placeholder' => __('lang_v1.address_line_2'), 'rows' => 3]); !!}
        </div>
      </div>
      <div class="clearfix"></div>
      <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('city', __('business.city') . ':') !!}
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-map-marker"></i>
                </span>
                {!! Form::text('city', $supplier->city, ['class' => 'form-control', 'placeholder' => __('business.city')]); !!}
            </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('state', __('business.state') . ':') !!}
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-map-marker"></i>
                </span>
                {!! Form::text('state', $supplier->state, ['class' => 'form-control', 'placeholder' => __('business.state')]); !!}
            </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('country', __('business.country') . ':') !!}
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-globe"></i>
                </span>
                {!! Form::text('country', $supplier->country, ['class' => 'form-control', 'placeholder' => __('business.country')]); !!}
            </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('zip_code', __('business.zip_code') . ':') !!}
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-map-marker"></i>
                </span>
                {!! Form::text('zip_code', $supplier->zip_code, ['class' => 'form-control', 
                'placeholder' => __('business.zip_code_placeholder')]); !!}
            </div>
        </div>
      </div>
      <div class="clearfix"></div>
</div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->