@extends('layouts.app')
@section('title', __('menus.menu'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'menus.menu' )
        </h1>
        <!-- <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
        </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        @component('components.filters', ['title' => __('report.filters')])
            @include('menu.partials.menu_list_filters')
        @endcomponent
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

        @component('components.widget', ['class' => 'box-primary', 'title' => __( 'menus.all_menu' )])
                @slot('tool')

                    <div class="box-tools">
                        <a class="btn btn-block btn-primary" href="{{action('MenuController@create')}}">
                            <i class="fa fa-plus"></i> @lang('messages.add')</a>
                    </div>
                @endslot
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="menu_table">
                    <thead>
                    <tr>
                        <th>@lang( 'menus.name' )</th>
                        <th>@lang( 'business.business_locations' )</th>
                        <th>@lang( 'product.category' )</th>
                        <th>@lang( 'menus.recipe' )</th>
                        <th>@lang( 'messages.action' )</th>
                    </tr>
                    </thead>
                </table>
            </div>
        @endcomponent


    </section>
    <!-- /.content -->
@stop
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function () {
            //menu_table
            var menu_table = $('#menu_table').DataTable({
                processing: true,
                serverSide: true,
                "ajax": {
                    "url": "/menu",
                    "data": function ( d ) {
                        d.menu_list_filter_name = $('#menu_list_filter_name').val();
                        d.menu_list_location = $('#menu_list_location').val();
                        d.menu_list_category = $('#menu_list_category').val();
                        d.menu_list_recipe = $('#menu_list_recipe').val();
                        d = __datatable_ajax_callback(d);
                    }
                },
                columnDefs: [{
                    "targets": 1,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [
                    {data: 'name', name: 'name'},
                    {data: 'business_location_id', name: 'business_location_id'},
                    {data: 'category_id', name: 'category_id'},
                    {data: 'recipe_id', name: 'recipe_id'},
                    {data: 'action', name: 'action'},
                ]
            });


            $(document).on('change', '#menu_list_filter_name, #menu_list_location, #menu_list_category, #menu_list_recipe',  function() {
                menu_table.ajax.reload();
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
                            menu_table.ajax.reload();
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
                                    menu_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
