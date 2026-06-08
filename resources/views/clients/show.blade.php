@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="{{ route('clients.index') }}" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-arrow-left btn-icon"></i> Back to Clients
    </a>
    <a href="{{ route('orders.create', ['c' => $client->clientid]) }}" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-plus btn-icon"></i> Add Order
    </a>
    <a href="{{ route('clients.edit', $client) }}" class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-edit btn-icon"></i> Edit Profile
    </a>
    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="d-inline" onsubmit="return confirm('Delete this client?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger bg-white text-danger d-inline-flex align-items-center gap-1 fw-medium">
            <i class="fas fa-trash-alt btn-icon"></i> Delete
        </button>
    </form>
</div>
@endsection

@section('content')
<div class="position-relative bg-white p-4 rounded-3 shadow-sm">
    @include('clients.partials.show-content')
</div>
@endsection
