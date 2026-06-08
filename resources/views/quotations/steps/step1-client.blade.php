<div class="position-relative">
    <div class="row">
        <div class="col-12 col-md-4 mx-auto">
            <div class="bg-white p-3 rounded-3 shadow-sm">
                <div class="bg-light p-4 rounded-3 border">

                    {{-- Header --}}
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                        <div>
                            <h5 class="fw-semibold text-black mb-1">
                                Select Client
                            </h5>
                            <p class="small text-muted mb-0">
                                Choose the client first, then continue to quotation items.
                            </p>
                        </div>
                    </div>

                    {{-- Steps --}}
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
                        <span class="badge text-bg-primary">1</span>
                        <span class="badge text-bg-light border text-dark">2</span>
                        <span class="badge text-bg-light border text-dark">3</span>
                        <span class="badge text-bg-light border text-dark">4</span>
                    </div>

                    {{-- Client Select --}}
                    <form action="#" method="" class="mainForm">
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                    for="clientid">
                                    Client *
                                </label>

                                <select id="clientid" class="form-select" required>
                                    <option value="">Choose client</option>

                                    @php
                                        $groupedClients = $clients->groupBy(
                                            fn ($c) => $c->type === 'trial'
                                                ? 'trial'
                                                : 'regular'
                                        );
                                    @endphp

                                    @foreach (['regular', 'trial'] as $group)
                                        @if ($groupedClients->has($group))
                                            <optgroup label="{{ $group === 'regular' ? 'Regular Clients' : 'Trial Clients' }}">
                                                @foreach ($groupedClients[$group] as $client)
                                                    <option value="{{ $client->clientid }}"
                                                        {{ $clientId == $client->clientid ? 'selected' : '' }}>
                                                        {{ $client->business_name ?? $client->contact_name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    {{-- Button --}}
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button"
                            class="btn btn-outline-primary btn-primary text-white fw-medium"
                            id="toStep2">
                            Next
                            <i class="fas fa-arrow-right btn-icon ms-1"></i>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
