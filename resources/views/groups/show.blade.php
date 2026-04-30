@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('groups.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Groups
    </a>
    <a href="{{ route('groups.edit', $group) }}" class="primary-button small">
        <i class="fas fa-edit icon-spaced-sm"></i>Edit
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

        <dt class="text-muted">Address Line 1</dt>
        <dd>{{ $group->address_line_1 ?: 'N/A' }}</dd>

        <dt class="text-muted">Address Line 2</dt>
        <dd>{{ $group->address_line_2 ?: 'N/A' }}</dd>

        <dt class="text-muted">City</dt>
        <dd>{{ $group->city ?: 'N/A' }}</dd>

        <dt class="text-muted">State</dt>
        <dd>{{ $group->state ?: 'N/A' }}</dd>

        <dt class="text-muted">Postal Code</dt>
        <dd>{{ $group->postal_code ?: 'N/A' }}</dd>

        <dt class="text-muted">Country</dt>
        <dd>{{ $group->country ?: 'India' }}</dd>
    </dl>
</section>
@endsection
