@if(!empty($sell->transaction_activity))
<table class="table table-condensed">
    <tr>
        <th>@lang('lang_v1.date')</th>
        <th>@lang('messages.action')</th>
        <th>@lang('lang_v1.comment')</th>
        <th>@lang('lang_v1.user_comment')</th>
    </tr>
    @forelse($sell->transaction_activity as $activity)
        <tr>
            <td>{{@format_datetime($activity->created_at)}}</td>
            <td>
                Added
            </td>
            <td>
                {{($activity->comment) ? $activity->comment : 'NA'}}
                {{--{{$activity->causer->user_full_name ?? ''}}
                @if(!empty($activity->getExtraProperty('from_api')))
                    <br>
                    <span class="label bg-gray">{{$activity->getExtraProperty('from_api')}}</span>
                @endif

                @if(!empty($activity->getExtraProperty('is_automatic')))
                    <span class="label bg-gray">@lang('lang_v1.automatic')</span>
                @endif--}}
            </td>
            <td>
                {{($activity->user_comment) ? $activity->user_comment : 'NA'}}
                {{--@if(!empty($activity_type))
                    @if($activity_type == 'sell')
                        @include('sale_pos.partials.activity_row')
                    @elseif($activity_type == 'purchase')
                        @include('sale_pos.partials.activity_row')
                    @endif

                @else
                    @php
                        $update_note = $activity->getExtraProperty('update_note');
                    @endphp
                    @if(!empty($update_note))
                        @if(!is_array($update_note))
                            {{$update_note}}
                        @endif
                    @endif
                @endif

                @if(!empty($activity->getExtraProperty('email')))
                    <b>@lang('business.email'): </b> {{$activity->getExtraProperty('email')}}
                @endif

                @if(!empty($activity->getExtraProperty('mobile')))
                    <b>@lang('business.mobile'): </b> {{$activity->getExtraProperty('mobile')}}
                @endif--}}
            </td>
        </tr>
    @empty
        <tr>
          <td colspan="4" class="text-center">
            @lang('purchase.no_records_found')
          </td>
        </tr>
    @endforelse
</table>
@endif