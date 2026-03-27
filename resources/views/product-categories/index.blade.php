@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('product-categories.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search product categories by name..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            @if (isset($searchTerm) && $searchTerm)
                <p class="eyebrow">{{ $resultCount }} categories matching "{{ $searchTerm }}"</p>
                <span class="search-badge">Filtered</span>
            @else
                <p class="eyebrow">{{ count($productCategories) }} product categories</p>
            @endif
            <h3>Product Categories</h3>
        </div>
        <a href="{{ route('product-categories.create') }}" class="primary-button">Add Product Category</a>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($productCategories as $category)
                <tr>
                    <td>
                        <strong>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $category['name']) : $category['name'] !!}</strong>
                    </td>
                    <td>
                        <span>{!! $category['description'] !!}</span>
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($category['status']) }}">{{ $category['status'] }}</span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('product-categories.show', $category['record_id']) }}" class="text-link">View</a>
                        <a href="{{ route('product-categories.edit', $category['record_id']) }}" class="text-link">Edit</a>
                        <form method="POST" action="{{ route('product-categories.destroy', $category['record_id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $category['name'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-link danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endsection

