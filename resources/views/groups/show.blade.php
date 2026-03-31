@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">{{ $group->group_name }}</p>
        <h3>Group details</h3>
    </div>
    <div>
        <a href="{{ route('groups.edit', $group) }}" class="primary-button">Edit</a>
        <a href="{{ route('groups.index') }}" class="text-link">&larr; Back to groups</a>
    </div>
</section>

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
