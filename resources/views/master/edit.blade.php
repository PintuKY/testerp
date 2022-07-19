@extends('layouts.app')

@section('title', __( 'master.edit_master_list' ))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'master.edit_master_list' )</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">

            <div class="col-sm-4">
                {{--@if(!empty($transaction->contact->supplier_business_name))
                    <b>{{ __('product.product_name') }}:</b> {{ $transaction->contact->supplier_business_name }}<br>
                @endif--}}
                <b>{{ __('product.product_name') }}:</b> {{ $sell_line->product_name }}<br>
                <b>{{ __('sale.customer_name') }}:</b> {{ $transaction->contact->name }}<br>
                <b>Total Days:</b> {{ ($sell_line->number_of_days != '')? $sell_line->number_of_days.' days':'NA' }}<br>
                <b>Start Date:</b> {{ \Carbon\Carbon::parse($sell_line->start_date)->format(' jS F Y') }}<br>
                <b>Product Unit Type:</b> {{ $sell_line->unit_name }}<br>
                <b>Sell Line Days:</b> {{ ($transaction_sell_lines_days_val != '') ? $transaction_sell_lines_days_val : 'NA' }}<br>
            </div>
            <div class="col-sm-4">
                <b>{{ __('business.address') }}:</b><br>
                @if(!empty($transaction->billing_address()))
                    {{$transaction->billing_address()}}
                @else
                    {!! $transaction->contact->contact_address !!}
                    @if($transaction->contact->mobile)
                        <br>
                        {{__('contact.mobile')}}: {{ $transaction->contact->mobile }}
                    @endif
                    @if($transaction->contact->alternate_number)
                        <br>
                        {{__('contact.alternate_contact_number')}}: {{ $transaction->contact->alternate_number }}
                    @endif
                    @if($transaction->contact->landline)
                        <br>
                        {{__('contact.landline')}}: {{ $transaction->contact->landline }}
                    @endif
                @endif
            </div>
            <div class="col-sm-4">
                @if(in_array('tables' ,$enabled_modules))
                    <strong>@lang('restaurant.table'):</strong>
                    {{$transaction->table->name ?? ''}}<br>
                @endif
                @if(in_array('service_staff' ,$enabled_modules))
                    <strong>@lang('restaurant.service_staff'):</strong>
                    {{$transaction->service_staff->user_full_name ?? ''}}<br>
                @endif

                <strong>@lang('sale.shipping'):</strong>
                <span
                    class="label @if(!empty($shipping_status_colors[$transaction->shipping_status])) {{$shipping_status_colors[$transaction->shipping_status]}} @else {{'bg-gray'}} @endif">{{$shipping_statuses[$transaction->shipping_status] ?? '' }}</span><br>
                {!! $transaction->contact->address_line_1 !!}, <br>
                {!! $transaction->contact->address_line_2 !!},<br>
                {!! $transaction->contact->shipping_city !!}
                {!! $transaction->contact->shipping_state !!}
                {!! $transaction->contact->shipping_country !!},<br>
                {!! $transaction->contact->shipping_zipcode !!}

                @if(!empty($transaction->shipping_custom_field_1))
                    <br><strong>{{$custom_labels['shipping']['custom_field_1'] ?? ''}}
                        : </strong> {{$transaction->shipping_custom_field_1}}
                @endif
                @if(!empty($transaction->shipping_custom_field_2))
                    <br><strong>{{$custom_labels['shipping']['custom_field_2'] ?? ''}}
                        : </strong> {{$transaction->shipping_custom_field_2}}
                @endif
                @if(!empty($transaction->shipping_custom_field_3))
                    <br><strong>{{$custom_labels['shipping']['custom_field_3'] ?? ''}}
                        : </strong> {{$transaction->shipping_custom_field_3}}
                @endif
                @if(!empty($transaction->shipping_custom_field_4))
                    <br><strong>{{$custom_labels['shipping']['custom_field_4'] ?? ''}}
                        : </strong> {{$transaction->shipping_custom_field_4}}
                @endif
                @if(!empty($transaction->shipping_custom_field_5))
                    <br><strong>{{$custom_labels['shipping']['custom_field_5'] ?? ''}}
                        : </strong> {{$transaction->shipping_custom_field_5}}
                @endif
                @php
                    $medias = $transaction->media->where('model_media_type', 'shipping_document')->all();
                @endphp
                @if(count($medias))
                    @include('sell.partials.media_table', ['medias' => $medias])
                @endif

                @if(in_array('types_of_service' ,$enabled_modules))
                    @if(!empty($transaction->types_of_service))
                        <strong>@lang('lang_v1.types_of_service'):</strong>
                        {{$transaction->types_of_service->name}}<br>
                    @endif
                    @if(!empty($transaction->types_of_service->enable_custom_fields))
                        <strong>{{ $custom_labels['types_of_service']['custom_field_1'] ?? __('lang_v1.service_custom_field_1' )}}
                            :</strong>
                        {{$transaction->service_custom_field_1}}<br>
                        <strong>{{ $custom_labels['types_of_service']['custom_field_2'] ?? __('lang_v1.service_custom_field_2' )}}
                            :</strong>
                        {{$transaction->service_custom_field_2}}<br>
                        <strong>{{ $custom_labels['types_of_service']['custom_field_3'] ?? __('lang_v1.service_custom_field_3' )}}
                            :</strong>
                        {{$transaction->service_custom_field_3}}<br>
                        <strong>{{ $custom_labels['types_of_service']['custom_field_4'] ?? __('lang_v1.service_custom_field_4' )}}
                            :</strong>
                        {{$transaction->service_custom_field_4}}<br>
                        <strong>{{ $custom_labels['types_of_service']['custom_field_5'] ?? __('lang_v1.custom_field', ['number' => 5])}}
                            :</strong>
                        {{$transaction->service_custom_field_5}}<br>
                        <strong>{{ $custom_labels['types_of_service']['custom_field_6'] ?? __('lang_v1.custom_field', ['number' => 6])}}
                            :</strong>
                        {{$transaction->service_custom_field_6}}
                    @endif
                @endif
            </div>
        </div>
        @foreach($product_ids as $key=>$productId)
            <table class="table bg-gray">
                <tr class="bg-green">
                    <th>#</th>
                    <th>{{ __('sale.product') }}({{$product_names[$key]}})</th>
                    @if( session()->get('business.enable_lot_number') == 1)
                        <th>{{ __('lang_v1.lot_n_expiry') }}</th>
                    @endif
                    @if($transaction->type == 'sales_order')
                        <th>@lang('lang_v1.quantity_remaining')</th>
                    @endif

                    @if(!empty($pos_settings['inline_service_staff']))
                        <th>
                            @lang('restaurant.service_staff')
                        </th>
                    @endif

                    <th>{{ __('sale.subtotal') }}</th>
                </tr>
                @foreach($transaction->sell_lines as $sell_line)
                    @if($sell_line->product->id == $productId)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if( $sell_line->product->type == 'variable')
                                    {{ $sell_line->variations->product_variation->name ?? ''}}
                                    - {{ $sell_line->variations->name ?? ''}}
                                @endif


                                @if(!empty($sell_line->sell_line_note))
                                    <br> {{$sell_line->sell_line_note}}
                                @endif


                                @if(in_array('kitchen', $enabled_modules))
                                    <br><span
                                        class="label @if($sell_line->res_line_order_status == 'cooked' ) bg-red @elseif($sell_line->res_line_order_status == 'served') bg-green @else bg-light-blue @endif">@lang('restaurant.order_statuses.' . $sell_line->res_line_order_status) </span>
                                @endif
                            </td>
                            @if( session()->get('business.enable_lot_number') == 1)
                                <td>{{ $sell_line->lot_details->lot_number ?? '--' }}
                                    @if( session()->get('business.enable_product_expiry') == 1 && !empty($sell_line->lot_details->exp_date))
                                        ({{@format_date($sell_line->lot_details->exp_date)}})
                                    @endif
                                </td>
                            @endif

                            <td>
                <span class="display_currency"
                      data-currency_symbol="true">{{  ($sell_line->transactionSellLinesVariants->isNotEmpty()) ? $sell_line->transactionSellLinesVariants[0]->value : '0'}}</span>
                            </td>
                        </tr>
                    @endif
                    @if(!empty($sell_line->modifiers))
                        @foreach($sell_line->modifiers as $modifier)
                            <tr>
                                <td>&nbsp;</td>
                                <td>
                                    {{ $modifier->product->name }} - {{ $modifier->variations->name ?? ''}}
                                </td>
                                @if( session()->get('business.enable_lot_number') == 1)
                                    <td>&nbsp;</td>
                                @endif
                                <td>{{ $modifier->quantity }}</td>
                                @if(!empty($pos_settings['inline_service_staff']))
                                    <td>
                                        &nbsp;
                                    </td>
                                @endif
                                <td>
                            <span class="display_currency"
                                  data-currency_symbol="true">{{ $modifier->unit_price }}</span>
                                </td>
                                <td>
                                    &nbsp;
                                </td>
                                <td>
                                    <span class="display_currency" data-currency_symbol="true">{{ $modifier->item_tax }}</span>
                                    @if(!empty($taxes[$modifier->tax_id]))
                                        ( {{ $taxes[$modifier->tax_id]}} )
                                    @endif
                                </td>
                                <td>
                        <span class="display_currency"
                              data-currency_symbol="true">{{ $modifier->unit_price_inc_tax }}</span>
                                </td>
                                <td>
                        <span class="display_currency"
                              data-currency_symbol="true">{{  ($sell_line->transactionSellLinesVariants->isNotEmpty()) ? $sell_line->transactionSellLinesVariants[0]->value : '0' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </table>
            <table class="table table-condensed table-bordered table-striped pos_table_{{$productId}}">
                <tr>
                    <td class="price_cal">
                        <div class="pull-right">
                            <b>@lang('sale.item'):</b>
                            <span
                                class="total_quantity_{{$productId}} total_quantity">{{  @num_format($edit_product[$productId]['quantity']) }}</span>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <b>@lang('sale.total'): </b>
                            @php
                                $total_item_value = $edit_product[$productId]['total_item_value'];
                                $total_quantity = $edit_product[$productId]['quantity']
                            @endphp
                            <span
                                class="price_totals_{{$productId}} total_prices">${{round($total_item_value * $total_quantity,2)}}</span>
                        </div>
                    </td>
                </tr>
            </table>

        @endforeach

        {!! Form::open(['url' => action('MasterController@update', [$master_list->id]), 'method' => 'PUT', 'id' => 'master_list_edit_form' ]) !!}
        <div class="row">

            @component('components.widget')
                <div class="col-md-12">
                    <input type="hidden" id="master_list_hidden_id" value="{{ $master_list->id }}">
                    <input type="hidden" id="status" name="status" value="1">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('delivery_date', __('sale.delivery_date') . ':*') !!}
                            <div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
                                {!! Form::text("delivery_date", $master_list->delivery_date, ['class' => 'form-control delivery_date', 'readonly','required']); !!}
                            </div>
                        </div>

                    </div>
                    <div class="col-md-4">
                        <label for="time_slot">{{__('master.cancel_reason')}}:*</label>
                        <div class="form-group">
                            <select class="form-control select2" id="cancel_reason"
                                    name="cancel_reason">
                                <option selected>please select</option>
                                @foreach(reasonCancelOrder() as $key => $types)
                                    <option
                                        value="{{$key}}"
                                        @if($master_list->cancel_reason == $key) selected @endif>{{ $types }}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('contacts_name', __('master.contacts_name') . ':*' ) !!}

                            {!! Form::text('contacts_name', $master_list->contacts_name, ['class' => 'form-control','placeholder' => __('master.contacts_name'),'readonly', 'required']); !!}

                        </div>
                    </div>

                </div>

                <div class="clearfix"></div>

                <div class="col-md-12">
                    <input type="hidden" id="master_list_hidden_id" value="{{ $master_list->id }}">
                    <input type="hidden" id="status" name="status" value="1">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_address_line_1', __('master.shipping_address_line_1') . ':*' ) !!}

                            {!! Form::text('shipping_address_line_1', $master_list->shipping_address_line_1, ['class' => 'form-control','placeholder' => __('master.shipping_address_line_1'),'readonly', 'required']); !!}

                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_address_line_2', __('master.shipping_address_line_2') . ':*' ) !!}

                            {!! Form::text('shipping_address_line_2', $master_list->shipping_address_line_2, ['class' => 'form-control','placeholder' => __('master.shipping_address_line_2'),'readonly', 'required']); !!}

                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_city', __('master.shipping_city') . ':*' ) !!}

                            {!! Form::text('shipping_city', $master_list->shipping_city, ['class' => 'form-control','placeholder' => __('master.shipping_city'),'readonly', 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_state', __('master.shipping_state') . ':*' ) !!}

                            {!! Form::text('shipping_state', $master_list->shipping_state, ['class' => 'form-control','placeholder' => __('master.shipping_state'),'readonly', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_country', __('master.shipping_country') . ':*' ) !!}

                            {!! Form::text('shipping_country', $master_list->shipping_country, ['class' => 'form-control','placeholder' => __('master.shipping_country'),'readonly', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_zip_code', __('master.shipping_zip_code') . ':*' ) !!}

                            {!! Form::text('shipping_zip_code', $master_list->shipping_zip_code, ['class' => 'form-control','placeholder' => __('master.shipping_zip_code'),'readonly', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_phone', __('master.shipping_phone') . ':*' ) !!}

                            {!! Form::text('shipping_phone', $master_list->shipping_phone, ['class' => 'form-control','placeholder' => __('master.shipping_phone'),'readonly', 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('staff_notes', __('master.staff_notes') . ':*' ) !!}

                            {!! Form::text('staff_notes', $master_list->staff_notes, ['class' => 'form-control','placeholder' => __('master.staff_notes'),'readonly', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('additional_notes', __('master.additional_notes') . ':*' ) !!}

                            {!! Form::text('additional_notes', $master_list->additional_notes, ['class' => 'form-control','placeholder' => __('master.additional_notes'),'readonly', 'required']); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('delivery_notes', __('master.delivery_notes') . ':*' ) !!}

                            {!! Form::text('delivery_notes', $master_list->delivery_notes, ['class' => 'form-control','placeholder' => __('master.delivery_notes'),'readonly', 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="col-md-4">
                        <div class="form-group">

                            <label for="compensate">{{__('master.compensate')}}:*</label>
                            <div class="form-group">
                                <select class="form-control select2" id="compensate"
                                        name="compensate"
                                        required disabled>
                                    <option selected>please select</option>
                                    @foreach(compensateTypes() as $key => $compensate)
                                        <option value="{{$key}}"
                                                @if($master_list->is_compensate == $key) selected @endif>{{ $compensate }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="time_slot">Meal Type:*</label>
                            <div class="form-group">
                                <select class="form-control select2" id="time_slot"
                                        name="time_slot"
                                        required disabled>
                                    <option selected>please select</option>
                                    @foreach(mealTypes() as $key => $deliveryDays)
                                        <option value="{{$key}}"
                                                @if($master_list->time_slot == $key) selected @endif>{{ $deliveryDays }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
        </div>
        @endcomponent

        </div>
        <div class="row">
            <div class="col-md-12 text-center">
                <button type="submit" class="btn btn-primary btn-big"
                        id="submit_user_button">@lang( 'messages.update' )</button>
            </div>
        </div>
        {!! Form::close() !!}
    </section>
@stop
@section('javascript')
    <script src="{{ asset('js/driver.js?v=' . $asset_v) }}"></script>
@endsection
