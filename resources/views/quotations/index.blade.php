@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">All Quotations</h3>
            @if(request('search'))
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                    Search results for "{{ request('search') }}"
                </p>
            @endif
        </div>
        <a href="{{ route('quotations.create') }}" class="primary-button">New Quotation</a>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Quotation</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($quotations as $quotation)
                <tr>
                    <td>
                        <strong>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $quotation['number']) : $quotation['number'] !!}</strong>
                        <span>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $quotation['client']) : $quotation['client'] !!}</span>
                    </td>
                    <td>
                        <strong>{{ $quotation['amount'] }}</strong>
                        <span>Expires {{ $quotation['expiry'] }}</span>
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($quotation['status']) }}">{{ $quotation['status'] }}</span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('quotations.show', $quotation['record_id']) }}" class="text-link">View</a>
                        <form method="POST" class="inline-delete"
                              action="{{ route('quotations.destroy', $quotation['record_id']) }}"
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
