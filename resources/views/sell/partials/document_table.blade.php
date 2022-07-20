<table class="table table-condensed">
    <tr>
        <td>
            @if(isFileImage($medias))
                <img src="{{ asset('storage/documents/'.$medias) }}" height="60" width="60">
                <br>
            @endif
            {{$medias}}
        </td>
        <td>
            <a href="{{asset('storage/documents/'.$medias)}}" download="{{$medias}}"
               class="btn btn-success btn-xs no-print"><i class="fas fa-download"></i> @lang('lang_v1.download')</a>
        </td>
    </tr>
</table>
