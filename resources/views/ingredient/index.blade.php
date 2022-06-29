@extends('layouts.app')
@section('title', __('ingredient.ingredient'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'ingredient.ingredient' )
        </h1>
        <!-- <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
        </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        @if (session('notification') || !empty($notification))
            <div class="row">
                <div class="col-sm-12">
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        @if(!empty($notification['msg']))
                            {{$notification['msg']}}
                        @elseif(session('notification.msg'))
                            {{ session('notification.msg') }}
                        @endif
                    </div>
                </div>
            </div>
        @endif
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.import_export_selling_price_group_prices')])
            <div class="row">
                <div class="col-sm-6">
                    <a href="{{action('SellingPriceGroupController@export')}}"
                       class="btn btn-primary">@lang('lang_v1.export_selling_price_group_prices')</a>
                </div>
                <div class="col-sm-6">
                    {!! Form::open(['url' => action('SellingPriceGroupController@import'), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                    <div class="form-group">
                        {!! Form::label('name', __( 'product.file_to_import' ) . ':') !!}
                        {!! Form::file('product_group_prices', ['required' => 'required']); !!}
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">@lang('messages.submit')</button>
                    </div>
                    {!! Form::close() !!}
                </div>
                <div class="col-sm-12">
                    <h4>@lang('lang_v1.instructions'):</h4>
                    <p>
                        &bull; @lang('lang_v1.price_group_import_istruction')
                    </p>
                    <p>
                        &bull; @lang('lang_v1.price_group_import_istruction1')
                    </p>

                </div>
            </div>
        @endcomponent
        @component('components.widget', ['class' => 'box-primary', 'title' => __( 'ingredient.all_ingredient' )])
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal"
                            data-href="{{action('IngredientController@create')}}"
                            data-container=".view_modal">
                        <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="selling_price_group_table">
                    <thead>
                    <tr>
                        <th>@lang( 'ingredient.parent_ingredient' )</th>
                        <th>@lang( 'ingredient.name' )</th>
                        <th>@lang( 'ingredient.description' )</th>
                        <th>@lang( 'messages.action' )</th>
                    </tr>
                    </thead>
                </table>
            </div>
        @endcomponent

        <div class="modal fade brands_modal" tabindex="-1" role="dialog"
             aria-labelledby="gridSystemModalLabel">
        </div>

    </section>
    <!-- /.content -->
@stop
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function () {

            //selling_price_group_table
            var selling_price_group_table = $('#selling_price_group_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/ingredients',
                columnDefs: [{
                    "targets": 3,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [
                    {data: 'ingredient_name',name: 'ingredient_parent_id'},
                    {data: 'name'},
                    {data: 'description'},
                    {data: 'action'},
                ],
            });

            $(document).on('submit', 'form#selling_price_group_form', function (e) {
                e.preventDefault();
                var data = $(this).serialize();

                $.ajax({
                    method: "POST",
                    url: $(this).attr("action"),
                    dataType: "json",
                    data: data,
                    success: function (result) {
                        if (result.success == true) {
                            $('div.view_modal').modal('hide');
                            toastr.success(result.msg);
                            selling_price_group_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $(document).on('click', 'button.delete_spg_button', function () {
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        var data = $(this).serialize();

                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            data: data,
                            success: function (result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    selling_price_group_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $(document).on('click', 'button.activate_deactivate_spg', function () {
                var href = $(this).data('href');
                $.ajax({
                    url: href,
                    dataType: "json",
                    success: function (result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            selling_price_group_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

        });
    </script>
@endsection
