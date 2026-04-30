@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div></div>
    </section>

    <div class="panel-card" style="padding: 1.5rem;">
        <div style="margin-bottom: 1.5rem;">
            <input 
                type="text" 
                id="client-search" 
                placeholder="Search clients..." 
                style="width: 100%; max-width: 500px; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; background: #f8fafc;"
                onfocus="this.style.background='white'; this.style.borderColor='#3b82f6';"
                onblur="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';"
            >
        </div>

        <div id="clients-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
            @forelse($clients as $client)
                <a href="{{ route('orders.index', ['client_id' => $client->clientid]) }}" 
                   class="client-card"
                   style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: white; border: 1px solid #e2e8f0; border-radius: 10px; text-decoration: none; color: inherit; transition: all 0.2s;"
                   onmouseover="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.15)'; this.style.transform='translateY(-2px)';"
                   onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'; this.style.transform='translateY(0)';"
                   data-client-name="{{ strtolower($client->business_name ?? $client->contact_name) }}">
                    <div style="width: 48px; height: 48px; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; flex-shrink: 0;">
                        {{ strtoupper(substr($client->business_name ?? $client->contact_name, 0, 2)) }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <strong style="display: block; font-size: 0.95rem; margin-bottom: 0.25rem; color: #0f172a;">
                            {{ $client->business_name ?? $client->contact_name }}
                        </strong>
                        @if($client->email)
                            <span style="display: block; font-size: 0.8rem; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $client->email }}
                            </span>
                        @endif
                        @if($client->phone)
                            <span style="display: block; font-size: 0.8rem; color: #64748b;">
                                {{ $client->phone }}
                            </span>
                        @endif
                    </div>
                    <div style="color: #94a3b8; flex-shrink: 0;">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            @empty
                <div style="grid-column: 1 / -1; padding: 3rem; text-align: center; color: #94a3b8;">
                    <i class="fas fa-users" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
                    <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">No clients found</p>
                    <p class="small-text">Add clients first to create orders.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@push('styles')
<style>
    .client-card {
        transition: all 0.2s ease;
    }
    .client-card.hidden {
        display: none;
    }
</style>
@endpush

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
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        });

        // Auto-focus search input
        searchInput.focus();
    });
</script>
@endpush
