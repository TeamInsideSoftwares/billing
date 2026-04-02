@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">All Estimates</h3>
            @if(request('search'))
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                    Search results for "{{ request('search') }}"
                </p>
            @endif
        </div>
        <a href="{{ route('estimates.create') }}" class="primary-button">New Estimate</a>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Estimate</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($estimates as $estimate)
                <tr>
                    <td>
                        <strong>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $estimate['number']) : $estimate['number'] !!}</strong>
                        <span>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $estimate['client']) : $estimate['client'] !!}</span>
                    </td>
</xai:function_call name="edit_file">

<xai:function_call name="edit_file">
<parameter name="path">resources/views/invoices/index.blade.php
                    <td>
                        <strong>{{ $estimate['amount'] }}</strong>
                        <span>Expires {{ $estimate['expiry'] }}</span>
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($estimate['status']) }}">{{ $estimate['status'] }}</span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('estimates.show', $estimate['record_id']) }}" class="text-link">View</a>
 action="{{ route('estimates.destroy', $estimate['record_id']) }}"
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
