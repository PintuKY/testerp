@php
    $default_location = null;
    if(count($business_locations) == 1){
      $default_location = array_key_first($business_locations->toArray());
    }
@endphp

@if(empty($only) || in_array('menu_list_filter_name', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('menu_list_filter_name',  __('menus.menu_name') . ':') !!}

            {!! Form::select('menu_list_filter_name', $menu_name_list, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
        </div>
    </div>
@endif

@if(empty($only) || in_array('menu_list_location', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('menu_list_location',  __('business.business_locations') . ':') !!}

            {!! Form::select('menu_list_location', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
        </div>
    </div>
@endif

@if(empty($only) || in_array('menu_list_category', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('menu_list_category',  __('product.category') . ':') !!}

            {!! Form::select('menu_list_category', $categories, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
        </div>
    </div>
@endif

@if(empty($only) || in_array('menu_list_recipe', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('menu_list_recipe',  __('menus.recipe') . ':') !!}

            {!! Form::select('menu_list_recipe', $recipe, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
        </div>
    </div>
@endif
