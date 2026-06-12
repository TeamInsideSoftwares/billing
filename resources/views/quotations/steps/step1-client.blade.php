<div class="position-relative d-flex align-items-center justify-content-center"
    style="min-height: calc(100vh - 240px);">
    <div class="row w-100">
        <div class="col-12 col-md-3 mx-auto">
            <div class="bg-white p-4 rounded-3">

                {{-- Header --}}
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="d-flex align-items-center justify-content-center bg-white rounded-circle border"
                        style="width: 40px; height: 40px;">
                        <i class="fas fa-user text-primary"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="fw-semibold text-dark">Select Client</div>
                        <div class="text-muted small">Choose client first</div>
                    </div>
                </div>

                {{-- Client Select --}}
                <form action="#" method="" class="mainForm">
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1" for="clientid">
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
                        Next
                        <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
