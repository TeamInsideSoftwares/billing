@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">All Orders</h3>
            @if(request('search'))
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                    Found {{ $resultCount }} result(s) for "{{ request('search') }}"
                </p>
            @endif
        </div>
        <div>
            <a href="{{ route('orders.create') }}" class="primary-button">Create Order</a>
        </div>
    </section>

    <div class="services-accordion-container">
    @forelse ($groupedOrders as $clientName => $clientOrders)
        @php
            $clientEmailForGroup = collect($clientOrders)->pluck('client_email')->filter()->first();
        @endphp
        <details class="category-accordion" open>
            <summary class="accordion-header">
                <span style="display: inline-flex; flex-direction: column; gap: 0.1rem;">
                    <span class="category-title">{{ $clientName }}</span>
                    @if($clientEmailForGroup)
                        <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">{{ $clientEmailForGroup }}</span>
                    @endif
                </span>
                <span class="service-count">{{ count($clientOrders) }} order(s)</span>
                <span class="accordion-icon"></span>
            </summary>
            <div class="accordion-content">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 34%;">Order</th>
                            <th style="width: 14%;">Order Date</th>
                            <th style="width: 12%;">Delivery</th>
                            <th style="width: 12%;">Amount</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 18%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($clientOrders as $order)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <div>
                                        @if($order['order_title'])
                                            <strong style="font-size: 0.9rem;">{{ $order['order_title'] }}</strong>
                                            <div style="font-size: 0.75rem; color: #64748b;">{{ $order['number'] }}</div>
                                        @else
                                            <strong style="font-size: 0.9rem;">{{ $order['number'] }}</strong>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.85rem;">{{ $order['order_date'] }}</div>
                            </td>
                            <td>
                                <div style="font-size: 0.85rem;">{{ $order['delivery_date'] }}</div>
                            </td>
                            <td>
                                <strong style="font-size: 0.9rem;">{{ $order['amount'] }}</strong>
                            </td>
                            <td>
                                <span class="status-pill {{ strtolower($order['status']) }}">{{ $order['status'] }}</span>
                            </td>
                            <td class="table-actions" style="vertical-align: middle; white-space: nowrap; width: 1%;">
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <a href="{{ route('orders.show', $order['record_id']) }}" class="icon-action-btn view" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('orders.edit', $order['record_id']) }}" class="icon-action-btn edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('orders.destroy', $order['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $order['number'] }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-action-btn delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @empty
        <div style="padding: 3rem; text-align: center; color: #94a3b8;">
            <i class="fas fa-receipt" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
            <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">No orders found</p>
            <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Get started by creating your first order.</p>
        </div>
    @endforelse
    </div>
@endsection
