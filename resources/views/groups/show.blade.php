@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('groups.index') }}" class="secondary-button">
        Back to Groups
    </a>
    <a href="{{ route('groups.edit', $group) }}" class="primary-button small">
        Edit
    </a>
@endsection

@section('content')
<section class="panel-card">
    <dl class="group-detail-grid">
        <dt class="text-muted">Group Name</dt>
        <dd><strong>{{ $group->group_name }}</strong></dd>

        <dt class="text-muted">Email</dt>
        <dd>{{ $group->email ?: 'N/A' }}</dd>

        <dt class="text-muted">GSTIN</dt>
        <dd>{{ $group->gstin ?: 'N/A' }}</dd>

        <dt class="text-muted">Registered Address</dt>
        <dd>{{ $group->registered_address ?: 'N/A' }}</dd>

        <dt class="text-muted">City</dt>
        <dd>{{ $group->city ?: 'N/A' }}</dd>

        <dt class="text-muted">State</dt>
        <dd>{{ $group->state ?: 'N/A' }}</dd>

        <dt class="text-muted">Postal Code</dt>
        <dd>{{ $group->postal_code ?: 'N/A' }}</dd>

        <dt class="text-muted">Country</dt>
        <dd>{{ $group->country ?: 'India' }}</dd>

        <dt class="text-muted">Business Address</dt>
        <dd>{{ $group->business_address ?: 'N/A' }}</dd>

        <dt class="text-muted">Business City</dt>
        <dd>{{ $group->business_city ?: 'N/A' }}</dd>

        <dt class="text-muted">Business State</dt>
        <dd>{{ $group->business_state ?: 'N/A' }}</dd>

        <dt class="text-muted">Business Postal Code</dt>
        <dd>{{ $group->business_postal_code ?: 'N/A' }}</dd>

        <dt class="text-muted">Business Country</dt>
        <dd>{{ $group->business_country ?: 'India' }}</dd>
    </dl>
</section>
@endsection
