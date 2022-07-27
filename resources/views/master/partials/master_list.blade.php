<div class="table-responsive">
    <table class="table table-bordered table-striped ajax_view" id="master_table">
        <thead>
            <tr>
                @foreach ($masterListCols as $value)
                    <th>@lang('master.'.$value['data'])</th>
                @endforeach
            </tr>
        </thead>
        <tfoot>
        @foreach ($masterListCols as $value)
            <th class="{{$value['data']}}">@lang('master.'.$value['data'])</th>
        @endforeach
        </tfoot>
        {{--<tfoot>
        <tr class="bg-gray font-17 footer-total text-center">
            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
            <td colspan="1"><strong>Lunch: {{ $lunch }}<br>
                Dinner: {{ $dinner }}</strong></td>
            <td colspan="5"><strong>@lang('sale.total'):</strong></td>
            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
        </tr>
        </tfoot>--}}
    </table>
</div>
