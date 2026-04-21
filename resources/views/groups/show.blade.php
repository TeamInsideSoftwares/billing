@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('groups.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left" style="margin-right: 0.4rem;"></i>Back to Groups
    </a>
    <a href="{{ route('groups.edit', $group) }}" class="primary-button small">
        <i class="fas fa-edit" style="margin-right: 0.35rem;"></i>Edit
    </a>
@endsection

@section('content')
<section class="panel-card">
    <dl style="display: grid; grid-template-columns: 180px 1fr; gap: 0.75rem;">
        <dt style="color: var(--text-muted);">Group Name</dt>
        <dd><strong>{{ $group->group_name }}</strong></dd>

        <dt style="color: var(--text-muted);">Email</dt>
        <dd>{{ $group->email ?: 'N/A' }}</dd>

        <dt style="color: var(--text-muted);">GSTIN</dt>
        <dd>{{ $group->gstin ?: 'N/A' }}</dd>

        <dt style="color: var(--text-muted);">Address Line 1</dt>
        <dd>{{ $group->address_line_1 ?: 'N/A' }}</dd>

        <dt style="color: var(--text-muted);">Address Line 2</dt>
        <dd>{{ $group->address_line_2 ?: 'N/A' }}</dd>

        <dt style="color: var(--text-muted);">City</dt>
        <dd>{{ $group->city ?: 'N/A' }}</dd>

        <dt style="color: var(--text-muted);">State</dt>
        <dd>{{ $group->state ?: 'N/A' }}</dd>

        <dt style="color: var(--text-muted);">Postal Code</dt>
        <dd>{{ $group->postal_code ?: 'N/A' }}</dd>

        <dt style="color: var(--text-muted);">Country</dt>
        <dd>{{ $group->country ?: 'India' }}</dd>
    </dl>
</section>
@endsection
