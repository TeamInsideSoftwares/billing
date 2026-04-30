@extends('layouts.app')

@section('content')
<div class="panel-card">
    <div class="section-bar">
        <h2>{{ $title }}</h2>
        <div class="button-group">
            <a href="{{ route('product-categories.edit', $productCategory->ps_catid) }}" class="primary-button small">Edit</a>
            <form method="POST" action="{{ route('product-categories.destroy', $productCategory->ps_catid) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete this product category?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="danger-button small">Delete</button>
            </form>
            <a href="{{ route('product-categories.index') }}" class="secondary-button small">Back to List</a>
        </div>
    </div>

    <div class="record-details">
        <div class="detail-row">
            <span class="detail-label">ID</span>
            <span class="detail-value">{{ $productCategory->ps_catid }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Name</span>
            <span class="detail-value">{{ $productCategory->name }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="status-pill {{ strtolower($productCategory->status) }}">{{ ucfirst($productCategory->status) }}</span>
        </div>
        @if($productCategory->description)
        <div class="detail-row">
            <span class="detail-label">Description</span>
            <span class="detail-value">{{ $productCategory->description }}</span>
        </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">Created</span>
            <span class="detail-value">{{ $productCategory->created_at->format('d M Y H:i') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Updated</span>
            <span class="detail-value">{{ $productCategory->updated_at->format('d M Y H:i') }}</span>
        </div>
    </div>
</div>
@endsection

