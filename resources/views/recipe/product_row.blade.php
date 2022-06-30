
<input type="hidden" value="{{$ingredient->id}}" name="recipe[{{$ingredient->id}}][ingredient_id]" id="ingredient_id">
<table class="table table-condensed table-bordered table-striped table-responsive
product_table ingredient_table_{{$ingredient->id}}"
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
            <a onclick="ing_table_remove({{$ingredient->id}})">
                <i class="fa fa-times text-danger ing_remove_row cursor-pointer" aria-hidden="true"></i>
            </a>
        </th>
    </tr>
    </thead>
    <tbody>
    <tr class="ing_row">
        <td>
            {{ $ingredient->name }}
        </td>

        <td>
            {{ $ingredient->description }}
        </td>
        <td>
            <div class="form-group">
                {!! Form::label('measure_type', __( 'ingredient.measure' )) !!}
                {!! Form::select('recipe[' . $ingredient->id . '][measure_type]', ingredientMeasure(), null, ['placeholder' => 'Select Please', 'class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
        </td>
        <td>
            <div class="form-group">
                {!! Form::label('quantity', __( 'ingredient.quantity' ) . ':*') !!}
                {!! Form::text('recipe[' . $ingredient->id . '][quantity]', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'ingredient.quantity' ) ]); !!}
            </div>
        </td>
    </tr>
    </tbody>
</table>
