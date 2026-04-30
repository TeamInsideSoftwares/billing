@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('product-categories.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search product categories by name..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
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
                        <a href="{{ route('product-categories.show', $category['record_id']) }}" class="icon-action-btn view" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('product-categories.edit', $category['record_id']) }}" class="icon-action-btn edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('product-categories.destroy', $category['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $category['name'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-action-btn delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endsection

