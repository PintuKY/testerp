<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        @php

            if(isset($update_action)) {
                $url = $update_action;
                $customer_groups = [];
                $opening_balance = 0;
                $lead_users = $contact->leadUsers->pluck('id');
            } else {
              $url = action('ContactController@update', [$contact->id]);
              $sources = [];
              $life_stages = [];
              $users = [];
              $lead_users = [];
            }
        @endphp

        {!! Form::open(['url' => $url, 'method' => 'PUT', 'id' => 'contact_edit_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('contact.edit_contact')</h4>
        </div>

        <div class="modal-body">

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('contact_id', __('lang_v1.contact_id') . ':') !!}
                        <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-id-badge"></i>
                  </span>
                            <input type="hidden" id="hidden_id" value="{{$contact->id}}">
                            {!! Form::text('contact_id', $contact->contact_id, ['class' => 'form-control','placeholder' => __('lang_v1.contact_id')]); !!}
                        </div>
                        <p class="help-block">
                            @lang('lang_v1.leave_empty_to_autogenerate')
                        </p>
                    </div>
                </div>
                <div class="col-md-4 customer_fields">
                    <div class="form-group">
                        {!! Form::label('customer_group_id', __('lang_v1.customer_group') . ':') !!}
                        <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-users"></i>
                  </span>
                            {!! Form::select('customer_group_id', $customer_groups, $contact->customer_group_id, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4 business">
                    <div class="form-group">
                        {!! Form::label('supplier_business_name', __('business.business_name') . ':') !!}
                        <div class="input-group">
                  <span class="input-group-addon">
                      <i class="fa fa-briefcase"></i>
                  </span>
                            {!! Form::text('supplier_business_name',
                            $contact->supplier_business_name, ['class' => 'form-control', 'placeholder' => __('business.business_name')]); !!}
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>

                <div class="col-md-3 individual">
                    <div class="form-group">
                        {!! Form::label('first_name', __( 'business.first_name' ) . ':*') !!}
                        {!! Form::text('first_name', $contact->first_name, ['class' => 'form-control', 'required', 'placeholder' => __( 'business.first_name' ) ]); !!}
                    </div>
                </div>
                <div class="col-md-3 individual">
                    <div class="form-group">
                        {!! Form::label('last_name', __( 'business.last_name' ) . ':*') !!}
                        {!! Form::text('last_name', $contact->last_name, ['class' => 'form-control','required' ,'placeholder' => __( 'business.last_name' ) ]); !!}
                    </div>
                </div>
                <div class="clearfix"></div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('mobile', __('contact.mobile') . ':*') !!}
                        <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-mobile"></i>
                </span>
                            {!! Form::text('mobile', $contact->mobile, ['class' => 'form-control', 'required', 'placeholder' => __('contact.mobile')]); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('email', __('business.email') . ':*') !!}
                        <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-envelope"></i>
                    </span>
                            {!! Form::email('email', $contact->email, ['class' => 'form-control','required','placeholder' => __('business.email')]); !!}
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

                            {!! Form::text('dob', !empty($contact->dob) ? @format_date($contact->dob) : null, ['class' => 'form-control dob-date-picker','placeholder' => __('lang_v1.dob'), 'readonly']); !!}
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
                            {!! Form::select('crm_source', $sources, $contact->crm_source , ['class' => 'form-control', 'id' => 'crm_source','placeholder' => __('messages.please_select')]); !!}
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
                            {!! Form::select('crm_life_stage', $life_stages, $contact->crm_life_stage , ['class' => 'form-control', 'id' => 'crm_life_stage','placeholder' => __('messages.please_select')]); !!}
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
                <div>

                    <div class="col-md-12">
                        <hr/>
                    </div>

                    <div class="clearfix"></div>
                    <h4 class="modal-title ml-15">@lang('contact.billing_adress')</h4>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address_line_1', __('lang_v1.address_line_1') . ':*') !!}
                            {!! Form::text('address_line_1', $contact->address_line_1, ['class' => 'form-control','required' ,'placeholder' => __('lang_v1.address_line_1'), 'rows' => 3]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address_line_2', __('lang_v1.address_line_2') . ':') !!}
                            {!! Form::text('address_line_2', $contact->address_line_2, ['class' => 'form-control',
                                'placeholder' => __('lang_v1.address_line_2'), 'rows' => 3]); !!}
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('city', __('business.city') . ':*') !!}
                            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-map-marker"></i>
                </span>
                                {!! Form::select('city', city(), $contact->city, ['class' => 'form-control select2','required'/*,'placeholder' => __('messages.please_select')*/]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('state', __('business.state') . ':*') !!}
                            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-map-marker"></i>
                </span>
                                {!! Form::select('city', state(), $contact->state, ['class' => 'form-control select2','required'/*,'placeholder' => __('messages.please_select')*/]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('country', __('business.country') . ':*') !!}
                            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-globe"></i>
                </span>
                                {!! Form::select('country', country(), $contact->country, ['class' => 'form-control select2','required'/*,'placeholder' => __('messages.please_select')*/]); !!}
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
                                {!! Form::text('zip_code', $contact->zip_code, ['class' => 'form-control','required',
                                'placeholder' => __('business.zip_code_placeholder'),'pattern' => "[0-9]{6}",'title'=>'Please enter 6 digit zipcode.']); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            {!! Form::label('billing_email', __('contact.billing_email') . ':*') !!}
                            <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-envelope"></i>
                            </span>
                                {!! Form::email('billing_email', $contact->billing_email, ['class' => 'form-control','required','placeholder' => __('business.billing_email')]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            {!! Form::label('billing_phone', __('contact.billing_phone') . ':*') !!}
                            <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-mobile"></i>
                            </span>
                                {!! Form::text('billing_phone', $contact->billing_phone, ['class' => 'form-control', 'required', 'placeholder' => __('contact.billing_phone')]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-12">
                        <hr/>
                    </div>

                    <!-- <div class="col-md-8 col-md-offset-2 shipping_addr_div mb-10" >
          <strong>{{__('lang_v1.shipping_address')}}</strong><br>
          {!! Form::text('shipping_address', $contact->shipping_address, ['class' => 'form-control',
                'placeholder' => __('lang_v1.search_address'), 'id' => 'shipping_address']); !!}
                    <div class="mb-10" id="map"></div>
                  </div>
{!! Form::hidden('position', $contact->position, ['id' => 'position']); !!}
                    @php
                        $shipping_custom_label_1 = !empty($custom_labels['shipping']['custom_field_1']) ? $custom_labels['shipping']['custom_field_1'] : '';

                        $shipping_custom_label_2 = !empty($custom_labels['shipping']['custom_field_2']) ? $custom_labels['shipping']['custom_field_2'] : '';

                        $shipping_custom_label_3 = !empty($custom_labels['shipping']['custom_field_3']) ? $custom_labels['shipping']['custom_field_3'] : '';

                        $shipping_custom_label_4 = !empty($custom_labels['shipping']['custom_field_4']) ? $custom_labels['shipping']['custom_field_4'] : '';

                        $shipping_custom_label_5 = !empty($custom_labels['shipping']['custom_field_5']) ? $custom_labels['shipping']['custom_field_5'] : '';
                    @endphp

                    @if(!empty($custom_labels['shipping']['is_custom_field_1_contact_default']) && !empty($shipping_custom_label_1))
                        @php
                            $label_1 = $shipping_custom_label_1 . ':';
                        @endphp

                            <div class="col-md-4">
                                <div class="form-group">
{!! Form::label('shipping_custom_field_1', $label_1 ) !!}
                        {!! Form::text('shipping_custom_field_details[shipping_custom_field_1]', !empty($contact->shipping_custom_field_details['shipping_custom_field_1']) ? $contact->shipping_custom_field_details['shipping_custom_field_1'] : null, ['class' => 'form-control','placeholder' => $shipping_custom_label_1]); !!}
                        </div>
                    </div>


                    @endif
                    @if(!empty($custom_labels['shipping']['is_custom_field_2_contact_default']) && !empty($shipping_custom_label_2))
                        @php
                            $label_2 = $shipping_custom_label_2 . ':';
                        @endphp

                            <div class="col-md-4">
                                <div class="form-group">
{!! Form::label('shipping_custom_field_2', $label_2 ) !!}
                        {!! Form::text('shipping_custom_field_details[shipping_custom_field_2]', !empty($contact->shipping_custom_field_details['shipping_custom_field_2']) ? $contact->shipping_custom_field_details['shipping_custom_field_2'] : null, ['class' => 'form-control','placeholder' => $shipping_custom_label_2]); !!}
                        </div>
                    </div>


                    @endif
                    @if(!empty($custom_labels['shipping']['is_custom_field_3_contact_default']) && !empty($shipping_custom_label_3))
                        @php
                            $label_3 = $shipping_custom_label_3 . ':';
                        @endphp

                            <div class="col-md-4">
                                <div class="form-group">
{!! Form::label('shipping_custom_field_3', $label_3 ) !!}
                        {!! Form::text('shipping_custom_field_details[shipping_custom_field_3]', !empty($contact->shipping_custom_field_details['shipping_custom_field_3']) ? $contact->shipping_custom_field_details['shipping_custom_field_3'] : null, ['class' => 'form-control','placeholder' => $shipping_custom_label_3]); !!}
                        </div>
                    </div>


                    @endif
                    @if(!empty($custom_labels['shipping']['is_custom_field_4_contact_default']) && !empty($shipping_custom_label_4))
                        @php
                            $label_4 = $shipping_custom_label_4 . ':';
                        @endphp

                            <div class="col-md-4">
                                <div class="form-group">
{!! Form::label('shipping_custom_field_4', $label_4 ) !!}
                        {!! Form::text('shipping_custom_field_details[shipping_custom_field_4]', !empty($contact->shipping_custom_field_details['shipping_custom_field_4']) ? $contact->shipping_custom_field_details['shipping_custom_field_4'] : null, ['class' => 'form-control','placeholder' => $shipping_custom_label_4]); !!}
                        </div>
                    </div>


                    @endif
                    @if(!empty($custom_labels['shipping']['is_custom_field_5_contact_default']) && !empty($shipping_custom_label_5))
                        @php
                            $label_5 = $shipping_custom_label_5 . ':';
                        @endphp

                            <div class="col-md-4">
                                <div class="form-group">
{!! Form::label('shipping_custom_field_5', $label_5 ) !!}
                        {!! Form::text('shipping_custom_field_details[shipping_custom_field_5]', !empty($contact->shipping_custom_field_details['shipping_custom_field_5']) ? $contact->shipping_custom_field_details['shipping_custom_field_5'] : null, ['class' => 'form-control','placeholder' => $shipping_custom_label_5]); !!}
                        </div>
                    </div>


                    @endif
                    @php
                        $common_settings = session()->get('business.common_settings');
                    @endphp
                    @if(!empty($common_settings['is_enabled_export']))
                        <div class="col-md-12 mb-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_export" class="form-check-input" id="is_customer_export" @if(!empty($contact->is_export))
                            checked

                        @endif>
                    <label class="form-check-label" for="is_customer_export">@lang('lang_v1.is_export')</label>
                </div>
            </div>
            @php
                            $i = 1;
                        @endphp
                        @for($i; $i <= 6 ; $i++)
                            <div class="col-md-4 export_div" style="display: none;">
                                <div class="form-group">
{!! Form::label('export_custom_field_'.$i, __('lang_v1.export_custom_field'.$i).':' ) !!}
                            {!! Form::text('export_custom_field_'.$i, !empty($contact['export_custom_field_'.$i]) ? $contact['export_custom_field_'.$i] : null, ['class' => 'form-control','placeholder' => __('lang_v1.export_custom_field'.$i)]); !!}
                            </div>
                        </div>


                        @endfor
                    @endif
                    </div> -->
                    <div class="clearfix"></div>
                    <h4 class="modal-title mb-10 ml-15">@lang('contact.shipping_adress')</h4>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address_line_1', __('lang_v1.address_line_1') . ':*') !!}
                            {!! Form::text('shipping_address_1', $contact->shipping_address_1, ['class' => 'form-control','required' ,'placeholder' => __('lang_v1.address_line_1'), 'rows' => 3]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address_line_2', __('lang_v1.address_line_2') . ':') !!}
                            {!! Form::text('shipping_address_2', $contact->shipping_address_2, ['class' => 'form-control', 'placeholder' => __('lang_v1.address_line_2'), 'rows' => 3]); !!}
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('city', __('business.city') . ':*') !!}
                            <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-map-marker"></i>
                    </span>
                                {!! Form::select('shipping_city', city(), $contact->shipping_city, ['class' => 'form-control select2','required'/*,'placeholder' => __('messages.please_select')*/]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('state', __('business.state') . ':*') !!}
                            <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-map-marker"></i>
                    </span>
                                {!! Form::select('shipping_state', state(), $contact->shipping_state, ['class' => 'form-control select2','required'/*,'placeholder' => __('messages.please_select')*/]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('country', __('business.country') . ':*') !!}
                            <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-globe"></i>
                    </span>
                                {!! Form::select('shipping_country', country(), $contact->shipping_country, ['class' => 'form-control select2','required'/*,'placeholder' => __('messages.please_select')*/]); !!}
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
                                {!! Form::text('shipping_zipcode', $contact->shipping_zipcode, ['class' => 'form-control','required',
                                'placeholder' => __('business.zip_code_placeholder'),'pattern' => "[0-9]{6}",'title'=>'Please enter 6 digit zipcode.']); !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
