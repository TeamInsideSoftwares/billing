<div class="row g-3 align-items-stretch">
    <div class="col-12 col-lg-4">
        <div class="bg-light p-4 rounded-3 border h-100">
            <div class="mb-3">
                <h5 class="fw-semibold text-black mb-0">Select Client</h5>
            </div>
            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1" for="clientid">Client *</label>
                    <select id="clientid" class="form-select" required>
                        <option value="">Choose client</option>
                        @php $groupedClients = $clients->groupBy(fn ($c) => $c->type === 'trial' ? 'trial' : 'regular') @endphp
                        @foreach (['regular', 'trial'] as $group)
                            @if ($groupedClients->has($group))
                            <optgroup label="{{ $group === 'regular' ? 'Regular Clients' : 'Trial Clients' }}">
                                @foreach ($groupedClients[$group] as $client)
                                    <option value="{{ $client->clientid }}" {{ $clientId == $client->clientid ? 'selected' : '' }}>
                                        {{ $client->business_name ?? $client->contact_name }}
                                    </option>
                                @endforeach
                            </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-outline-primary btn-primary text-white fw-medium" id="toStep2">
                    Next <i class="fas fa-arrow-right btn-icon ms-1"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="bg-light p-4 rounded-3 border h-100">
            <div class="mb-3">
                <h5 class="fw-semibold text-black mb-0">Progress</h5>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge text-bg-primary">1</span>
                <span class="badge text-bg-light border text-dark">2</span>
                <span class="badge text-bg-light border text-dark">3</span>
                <span class="badge text-bg-light border text-dark">4</span>
            </div>
            <p class="small text-muted mt-3 mb-0">Choose the client first, then continue to quotation items.</p>
        </div>
    </div>
</div>
