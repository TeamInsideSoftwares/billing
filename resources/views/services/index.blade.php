@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('services.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search services by name..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            @if (isset($searchTerm) && $searchTerm)
                <p class="eyebrow">{{ $resultCount }} services matching "{{ $searchTerm }}"</p>
                <span class="search-badge">Filtered</span>
            @else
                <p class="eyebrow">{{ count($services) }} services</p>
            @endif
            <h3>Billable services</h3>

        </div>
        <a href="{{ route('services.create') }}" class="primary-button">Add Service</a>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($services as $service)
                <tr>
                    <td>
                        <strong>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $service['name']) : $service['name'] !!}</strong>
                        <span>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $service['type']) : $service['type'] !!}</span>
                    </td>
                    <td>
                        <strong>{{ $service['price'] }}</strong>
                        <span>Tax {{ $service['tax'] }}</span>
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($service['status']) }}">{{ $service['status'] }}</span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('services.show', $service['record_id']) }}" class="text-link">View</a>
                        <a href="{{ route('services.edit', $service['record_id']) }}" class="text-link">Edit</a>
                        <form method="POST" action="{{ route('services.destroy', $service['record_id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $service['name'] }}?')">
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

