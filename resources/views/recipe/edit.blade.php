@extends('layouts.app')

@section('title', __('sale.pos_sale'))

@section('content')
    <section class="content no-print">

        {!! Form::open(['url' => action('RecipeController@update',  $menu->id ), 'method' => 'put', 'id' => 'edit_menu_form' ]) !!}

        <div class="row mb-12">
            <div class="col-md-12 col-sm-12">
                @component('components.widget', ['class' => 'box-solid'])
                    <div class="col-sm-10 col-sm-offset-1">

                        <div class="form-group">
                            {!! Form::label('name', __( 'ingredient.name' ) . ':*') !!}
                            {!! Form::text('name', $menu->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'ingredient.name' ) ]); !!}
                        </div>
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat" data-toggle="modal"
                                            data-target="#configure_search_modal"
                                            title="{{__('lang_v1.configure_product_search')}}"><i
                                            class="fas fa-search-plus"></i></button>
                                </div>
                                {!! Form::text('search_ingredient', null, ['class' => 'form-control mousetrap', 'id' => 'search_ingredient', 'placeholder' => __('lang_v1.search_ingredient_placeholder'),
                                'autofocus' => true,
                                ]); !!}
                                <span class="input-group-btn">

							</span>
                            </div>
                        </div>
                    </div>

                    <div class="row col-sm-12 ing_product_div" style="min-height: 0">


                        <dsiv class="table-responsive">
                            @foreach($menu->recipe_items as $ingredient)
                                <input type="hidden" value="{{$ingredient->ingredient_id}}" name="recipe[{{$ingredient->ingredient_id}}][ingredient_id]" id="ingredient_id">

                                <table class="table table-condensed table-bordered table-striped table-responsive
product_table ingredient_table_{{$ingredient->ingredient_id}}"
                                       id="ingredient_table">
                                    <thead>
                                    <tr>
                                        <th class="text-center">
                                            @lang('ingredient.ingredient_name')
                                        </th>
                                        <th class="text-center">
                                            @lang('ingredient.ingredient_description')
                                        </th>
                                        <th class="text-center">
                                            @lang('ingredient.measure')
                                        </th>
                                        <th class="text-center">
                                            @lang('ingredient.quantity')
                                        </th>
                                        <th class="text-center pos_remove_table">
                                            <a onclick="ing_table_remove({{$ingredient->ingredient_id}})">
                                                <i class="fa fa-times text-danger ing_remove_row cursor-pointer"
                                                   aria-hidden="true"></i>
                                            </a>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr class="ing_row">
                                        <td>
                                            {{ $ingredient->ingredient->name }}
                                        </td>

                                        <td>
                                            {{ $ingredient->ingredient->description }}
                                        </td>
                                        <td>
                                            <div class="form-group">
                                                {!! Form::label('measure_type', __( 'ingredient.measure' )) !!}
                                                {!! Form::select('recipe[' . $ingredient->ingredient_id . '][measure_type]', ingredientMeasure(), $ingredient->measure_type, ['placeholder' => 'Select Please', 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group">
                                                {!! Form::label('quantity', __( 'ingredient.quantity' ) . ':*') !!}
                                                {!! Form::text('recipe[' . $ingredient->ingredient_id . '][quantity]', $ingredient->quantity, ['class' => 'form-control', 'required', 'placeholder' => __( 'ingredient.quantity' ) ]); !!}
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            @endforeach
                        </dsiv>
                        <div class="table-responsive ing">

                        </div>

                    </div>
                @endcomponent

            </div>
        </div>
        <div class="row">

            <div class="col-sm-12 text-center">
                <button type="button" id="submit-menu" class="btn btn-primary btn-big">@lang('messages.save')</button>

            </div>
        </div>
        {!! Form::close() !!}
    </section>

    <!-- This will be printed -->


    <!-- /.content -->
    <div class="modal fade register_details_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade close_register_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>
    <!-- quick product modal -->
    <div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

    <div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    @include('recipe.configure_search_modal')

@stop

@section('javascript')
    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    @include('sale_pos.partials.keyboard_shortcuts')

    <!-- Call restaurant module if defined -->

@endsection
