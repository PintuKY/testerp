@if(empty($only) || in_array('driver_list_filter_name', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('driver_list_filter_name',  __('driver.driver_name') . ':') !!}

        {!! Form::select('driver_list_filter_name', $driver_name_list, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
    </div>
</div>
@endif

@if(empty($only) || in_array('driver_list_filter_date_range', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('driver_list_filter_date_range', __('report.date_range') . ':') !!}
        {!! Form::text('driver_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
    </div>
</div>
@endif
