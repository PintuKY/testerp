<!-- <strong>{{ $supplier->name }}</strong><br><br> -->
<h3 class="profile-username">
    <i class="fas fa-user-tie"></i>
    {{ $supplier->name }}
    {{-- <small>
        @if($contact->type == 'both')
            {{__('role.customer')}} & {{__('role.supplier')}}
        @elseif(($contact->type != 'lead'))
            {{__('role.'.$contact->type)}}
        @endif
    </small> --}}
</h3><br>
<strong><i class="fa fa-map-marker margin-r-5"></i> @lang('business.address')</strong>
<p class="text-muted">
    {!! $supplier->supplier_address !!}
</p>
@if($supplier->supplier_business_name)
    <strong><i class="fa fa-briefcase margin-r-5"></i> 
    @lang('business.business_name')</strong>
    <p class="text-muted">
        {{ $supplier->supplier_business_name }}
    </p>
@endif

<strong><i class="fa fa-mobile margin-r-5"></i> @lang('supplier.mobile')</strong>
<p class="text-muted">
    {{ $supplier->mobile }}
</p>
@if($supplier->landline)
    <strong><i class="fa fa-phone margin-r-5"></i> @lang('supplier.landline')</strong>
    <p class="text-muted">
        {{ $supplier->landline }}
    </p>
@endif
@if($supplier->alternate_number)
    <strong><i class="fa fa-phone margin-r-5"></i> @lang('supplier.alternate_contact_number')</strong>
    <p class="text-muted">
        {{ $supplier->alternate_number }}
    </p>
@endif
@if($supplier->dob)
    <strong><i class="fa fa-calendar margin-r-5"></i> @lang('lang_v1.dob')</strong>
    <p class="text-muted">
        {{ @format_date($supplier->dob) }}
    </p>
@endif