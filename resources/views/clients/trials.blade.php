@extends('layouts.app')

@section('header_actions')
    <div class="flex items-center gap-4">
        <a href="{{ route('clients.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-slate-700 font-semibold text-sm bg-white border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition-all cursor-pointer no-underline">View All Clients</a>
    </div>
@endsection

@section('content')
    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-1.5 mb-3">
        <form action="{{ route('clients.trials') }}" method="GET" class="grid grid-cols-[repeat(4,minmax(0,1fr))_auto] gap-1 items-end">
            <div class="flex flex-col min-w-0">
                <label class="block mb-0.5 text-xs font-semibold text-slate-500 uppercase tracking-wide" for="trials_search_filter">Search</label>
                <input type="text" name="search" id="trials_search_filter" class="w-full bg-white border border-slate-300 rounded px-3 h-10 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" value="{{ $searchTerm ?? '' }}" placeholder="Business name or contact person">
            </div>

            <div class="flex items-center gap-1.5 flex-wrap">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white font-semibold text-sm bg-blue-600 hover:bg-blue-700 shadow-md hover:shadow-lg transition-all no-underline cursor-pointer">Apply</button>
                <a href="{{ route('clients.trials') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-slate-700 font-semibold text-sm bg-white border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition-all cursor-pointer no-underline">Reset</a>
            </div>
        </form>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-3 px-4">Client</th>
                    <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-3 px-4">Contact</th>
                    <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-3 px-4">Status</th>
                    <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($clients as $client)
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-sm shrink-0">
                                {{ strtoupper(substr($client['name'], 0, 2)) }}
                            </div>
                            <div>
                                <strong class="text-sm text-slate-800">{{ $client['name'] }}</strong>
                                <div class="text-xs text-slate-500">{{ $client['email'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        @if($client['contact'])
                            <div class="text-sm text-slate-600">{{ $client['contact'] }}</div>
                        @endif
                        @if($client['phone'])
                            <div class="text-xs text-slate-500"><i class="fas fa-phone"></i>{{ $client['phone'] }}</div>
                        @endif
                    </td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-[0.7rem] font-bold uppercase tracking-wider leading-none whitespace-nowrap transition-all
                            {{ strtolower($client['status']) === 'active' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ strtolower($client['status']) === 'inactive' ? 'bg-red-100 text-red-800' : '' }}
                            {{ strtolower($client['status']) === 'review' ? 'bg-amber-100 text-amber-800' : '' }}
                            {{ strtolower($client['status']) === 'trial' ? 'bg-amber-100 text-amber-800' : '' }}
                        ">{{ ucfirst(strtolower($client['status'])) }}</span>
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex flex-wrap gap-1">
                            <a href="{{ route('clients.dashboard', $client['record_id']) }}" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100">View Orders</a>
                            <a href="{{ route('quotations.create', ['c' => $client['record_id']]) }}" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-amber-50 text-amber-700 hover:bg-amber-100">Create Quotation</a>
                            <form method="POST" action="{{ route('clients.convert-to-regular', $client['record_id']) }}" class="inline-flex m-0" onsubmit="return confirm('Convert {{ $client['name'] }} to regular client?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-green-50 text-green-700 hover:bg-green-100">Convert to Regular</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="p-12 text-center text-slate-400">
                        <i class="fas fa-user-clock text-4xl mb-3 block mx-auto opacity-30"></i>
                        <p class="text-sm font-medium m-0 text-slate-400">No trial clients found</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
