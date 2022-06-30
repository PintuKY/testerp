@extends('layouts.app')

@section('title', __('menus.edit_menu'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('menus.edit_menu')</h1>
        <!-- <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
        </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        @php
            $form_class = '';
        @endphp
        {!! Form::open(['url' => action('MenuController@update',[$menu->id]), 'method' => 'PUT',
            'id' => 'edit_menu','class' => 'edit_form']) !!}
        @component('components.widget', ['class' => 'box-primary'])
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('name', __('menus.menu_name') . ':*') !!}
                        {!! Form::text('name', $menu->name, ['class' => 'form-control', 'required',
                        'placeholder' => __('menus.menu_name')]); !!}
                    </div>
                </div>

                <div class="clearfix"></div>


                @php
                    $default_location = null;
                    if(count($business_locations) == 1){
                      $default_location = array_key_first($business_locations->toArray());
                    }
                @endphp
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('business_location_id', __('business.business_locations') . ':') !!} @show_tooltip(__('lang_v1.product_location_help'))
                        {!! Form::select('business_location_id', $business_locations, $menu->business_location_id, ['class' => 'form-control select2', 'id' => 'product_locations']); !!}
                    </div>
                </div>

                <div class="col-sm-4 @if(!session('business.enable_category')) hide @endif">
                    <div class="form-group">
                        {!! Form::label('category_id', __('product.category') . ':*') !!}
                        {!! Form::select('category_id', $categories, $menu->category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('recipe_id', __('menus.recipe') . ':*') !!}
                        {!! Form::select('recipe_id', $recipe, $menu->recipe_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
        @endcomponent


        <div class="row">
            <div class="col-sm-12">
                <input type="hidden" name="submit_type" id="submit_type">
                <div class="text-center">
                    <div class="btn-group">
                        <button type="submit" value="submit" class="btn btn-primary submit_menu_form">@lang('messages.save')</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        {!! Form::close() !!}

    </section>
@endsection

@section('javascript')
    <script src="{{ asset('js/menu.js?v=' . $asset_v) }}"></script>
@endsection
