@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('payments.create') }}" class="primary-button">Record Payment</a>
@endsection

@section('content')
    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment</th>
                    <th>Received</th>
                    <th>TDS</th>
                    <th>Total Settled</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>
                        <strong>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>' . $searchTerm . '</mark>', $payment['number']) : $payment['number'] !!}</strong>
                        <span>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $payment['client']) : $payment['client'] !!}</span>
                        <span>{{ $payment['date'] }} via {{ $payment['method'] }}@if($payment['invoice']) · {{ $payment['invoice'] }}@endif</span>
                    </td>
                    <td>
                        <strong>{{ $payment['currency'] }} {{ number_format($payment['received_amount'], 0) }}</strong>
                    </td>
                    <td>
                        <strong>{{ $payment['currency'] }} {{ number_format($payment['tds_amount'], 0) }}</strong>
                    </td>
                    <td>
                        <strong>{{ $payment['currency'] }} {{ number_format($payment['total_settled'], 0) }}</strong>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('payments.show', $payment['record_id']) }}" class="icon-action-btn view" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('payments.edit', $payment['record_id']) }}" class="icon-action-btn edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('payments.destroy', $payment['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $payment['number'] }}?')">
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
                    <td colspan="5" class="no-records-cell">
                        <i class="fas fa-money-bill-wave empty-state-icon"></i>
                        <p class="no-empty-state-text">No payments recorded</p>
                        <p class="small-text">Record your first payment to track your collections.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
