<div class="table-responsive">
    <table class="table table-bordered table-striped ajax_view" id="master_table">
        <thead>
            <tr>
                @foreach ($masterListCols as $value)
                    <th>@lang('master.'.$value['data'])</th>
                @endforeach
            </tr>
        </thead>
    </table>
</div>
