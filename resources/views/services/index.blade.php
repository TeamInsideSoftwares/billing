@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('services.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search services by name..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            <p class="eyebrow">{{ count($services) }} Services</p>
            <h3>Billable services</h3>
        </div>
        <a href="{{ route('services.create') }}" class="primary-button">Add Service</a>
    </section>

    <section class="panel-card">
        <div class="table-list">
            @foreach ($services as $service)
                <div class="table-row">
                    <div>
                        <strong>{{ $service['name'] }}</strong>
                        <span>{{ $service['type'] }}</span>
                    </div>
                    <div>
                        <strong>{{ $service['price'] }}</strong>
                        <span>Tax {{ $service['tax'] }}</span>
                    </div>
                    <div>
                        <span class="status-pill {{ strtolower($service['status']) }}">{{ $service['status'] }}</span>
                    </div>
                    <div class="table-actions">
                        <a href="{{ route('services.show', $service['id']) }}" class="text-link">View</a>
                        <a href="{{ route('services.edit', $service['id']) }}" class="text-link">Edit</a>
                        <form method="POST" action="{{ route('services.destroy', $service['id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $service['name'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-link danger">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection

