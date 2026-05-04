@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div></div>
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
            @forelse ($quotations as $quotation)
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
                        <a href="{{ route('quotations.show', $quotation['record_id']) }}" class="icon-action-btn view" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <form method="POST" class="inline-delete" action="{{ route('quotations.destroy', $quotation['record_id']) }}" onsubmit="return confirm('Delete {{ $quotation['number'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-action-btn delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="no-records-cell">
                        <i class="fas fa-file-contract empty-state-icon"></i>
                        <p class="no-empty-state-text">No quotations found</p>
                        <p class="small-text">Create your first quotation to get started.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
