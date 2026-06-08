@extends('layouts.app')

@section('content')
    <div class="position-relative bg-white p-3 rounded-3 shadow-sm">
        <div class="mb-3">
            <input 
                type="text" 
                id="client-search" 
                placeholder="Search clients..." 
                class="form-control"
            >
        </div>

        <div id="clients-grid" class="clients-grid">
            <a href="{{ route('orders.index', ['c' => 'all']) }}" 
               class="client-card"
               data-client-name="all clients view all">
                <div class="client-card__avatar client-card__avatar--all">
                    <i class="fas fa-users"></i>
                </div>
                <div class="client-card__body">
                    <strong class="client-card__title">
                        All Clients
                    </strong>
                    <span class="client-card__meta">
                        View orders for all clients
                    </span>
                </div>
                <div class="client-card__chevron">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            @if(collect($clients ?? [])->isNotEmpty())
                @foreach($clients as $client)
                    <a href="{{ route('orders.index', ['c' => $client->clientid]) }}" 
                       class="client-card"
                       data-client-name="{{ strtolower($client->business_name ?? $client->contact_name) }}">
                        <div class="client-card__avatar">
                            {{ strtoupper(substr($client->business_name ?? $client->contact_name, 0, 2)) }}
                        </div>
                        <div class="client-card__body">
                            <strong class="client-card__title">
                                {{ $client->business_name ?? $client->contact_name }}
                            </strong>
                            @if($client->primary_email ?? $client->email)
                                <span class="client-card__meta is-ellipsis">
                                    {{ $client->primary_email ?? $client->email }}
                                </span>
                            @endif
                            @if($client->phone)
                                <span class="client-card__meta">
                                    {{ $client->phone }}
                                </span>
                            @endif
                        </div>
                        <div class="client-card__chevron">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                @endforeach
            @else
                <div class="clients-empty">
                    <i class="fas fa-users clients-empty__icon"></i>
                    <p class="clients-empty__title">No clients found</p>
                    <p class="small-text">Add clients first to create orders.</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('client-search');
        const clientsGrid = document.getElementById('clients-grid');
        const clientCards = clientsGrid.querySelectorAll('.client-card');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();

            clientCards.forEach(card => {
                const clientName = card.getAttribute('data-client-name');
                if (searchTerm === '' || clientName.includes(searchTerm)) {
                    card.classList.remove('is-hidden');
                } else {
                    card.classList.add('is-hidden');
                }
            });
        });

        // Auto-focus search input
        searchInput.focus();
    });
</script>
@endpush
