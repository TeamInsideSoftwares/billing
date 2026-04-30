@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div></div>
        <a href="{{ route('payments.create') }}" class="primary-button">Record Payment</a>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Payment</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($payments as $payment)
                <tr>
                    <td>
                        <strong>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>' . $searchTerm . '</mark>', $payment['number']) : $payment['number'] !!}</strong>
                        <span>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $payment['client']) : $payment['client'] !!}</span>
                    </td>
                    <td>
                        <strong>{{ $payment['amount'] }}</strong>
                        <span>{{ $payment['date'] }} via {{ $payment['method'] }}</span>
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($payment['status']) }}">{{ $payment['status'] }}</span>
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
            @endforeach
            </tbody>
        </table>
    </section>
@endsection
