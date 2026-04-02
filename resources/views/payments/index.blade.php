@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">All Payments</h3>
            @if(request('search'))
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                    Search results for "{{ request('search') }}"
                </p>
            @endif
        </div>
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
                        <a href="{{ route('payments.show', $payment['record_id']) }}" class="text-link">View</a>
                        <a href="{{ route('payments.edit', $payment['record_id']) }}" class="text-link">Edit</a>
                        <form method="POST" action="{{ route('payments.destroy', $payment['record_id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $payment['number'] }}?')">
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
