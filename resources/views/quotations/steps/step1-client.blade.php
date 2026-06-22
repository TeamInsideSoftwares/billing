<div class="position-relative d-flex align-items-center justify-content-center"
    style="min-height: calc(100vh - 160px);">
    <div class="row w-100">
        <div class="col-12 col-md-3 mx-auto">
            <div class="bg-white p-4 rounded-3 mx-auto mb-5">

                {{-- Header --}}
                <div class="d-flex align-items-center gap-3 mb-3 pb-1">
                    <div class="min-w-0">
                        <h5 class="fw-semibold text-black mb-0">Select Client</h5>
                        <p class="text-dark mb-0">Choose client first</p>
                    </div>
                </div> 

                {{-- Client Select --}}
                <form action="#" method="" class="mainForm">
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1" for="clientid">
                                Client<span class="text-danger">*</span>
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
                                    <option value="{{ $client->clientid }}" {{ $clientId==$client->clientid ? 'selected'
                                        : '' }}>
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
                    <button type="button" class="btn btn-outline-primary btn-primary text-white fw-medium" id="toStep2">
                        Create Quotation
                        <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
