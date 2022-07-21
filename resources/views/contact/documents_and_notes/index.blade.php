<div class="table-responsive">
    <table class="table table-bordered table-striped" style="width: 100%;" id="documents_and_notes_table">
        <thead>
        <tr>
            <th>@lang('lang_v1.transaction_id')</th>
            <th>@lang('lang_v1.media_type')</th>
            <th>@lang('lang_v1.document')</th>
            <th>@lang('lang_v1.created_at')</th>
            <th>@lang('lang_v1.updated_at')</th>
        </tr>
        </thead>
        <tbody>
        @forelse($document_note as $note)
            @if($note->document)
                <tr>
                    <td>{{$note->id}}</td>
                    <td>{{$note->type}}</td>
                    <td><a class="aaa" target="_blank" href="{{ asset('storage/documents/'.$note->document) }}"> {{$note->document}}</a></td>
                    <td>{{@format_date($note->created_at)}}</td>
                    <td>{{@format_date($note->updated_at)}}</td>
                </tr>
            @endif
            @forelse($note->media as $media)
                <tr>
                    <td>{{$media->model_id}}</td>
                    <td>{{$media->model_media_type}}</td>
                    <td><a target="_blank" class="bbb" href="{{ asset('storage/media/'.$media->file_name) }}">{{$media->file_name}}</a></td>
                    <td>{{@format_date($media->created_at)}}</td>
                    <td>{{@format_date($media->updated_at)}}</td>
                </tr>
            @empty
            @endforelse
            @forelse($note->payment_lines as $payment)
                @if($payment->document)
                    <tr>
                        <td>{{$payment->transaction_id}}</td>
                        <td>{{'Payment'}}</td>
                        <td><a class="ccc" target="_blank" href="{{ asset('storage/documents/'.$payment->document) }}">{{$payment->document}}</a></td>
                        <td>{{@format_date($payment->created_at)}}</td>
                        <td>{{@format_date($payment->updated_at)}}</td>
                    </tr>
                @endif
            @empty
            @endforelse
        @empty
        @endforelse


        </tbody>
    </table>
</div>
<div class="modal fade docus_note_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
