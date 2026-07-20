        <!-- TAXES TAB -->
        <div id="taxes" class="tab-pane fade {{ $activeSettingsTab === 'taxes' ? 'show active' : '' }}" role="tabpanel">
            <div class="row g-2 align-items-stretch">
                <div class="col-12 col-md-12"> 
                    <div class="meta-info ps-2">
                        <strong class="fw-bold fs-5 lh-sm">Tax Management</strong>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="bg-light p-2 rounded-3 h-100" id="tax-form-card">
                        <div class="mb-2">
                            <h6 id="tax-form-title" class="fw-semibold text-primary small lh-sm mb-0">Add New Tax</h6>
                        </div>
                        <form method="POST" id="tax-form" action="{{ route('taxes.store') }}" class="mainForm">
                            @csrf
                            <div class="row g-2">
                                <div class="col-12 col-lg-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Rate (%)<span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="rate" id="tax-rate-input" value="{{ old('rate') }}"
                                        placeholder="e.g., 18" step="0.01" min="0" max="100" required class="form-control">
                                </div>
                                <div class="col-12 col-lg-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Type<span
                                            class="text-danger">*</span></label>
                                    <select name="type" id="tax-type-select" required class="form-select">
                                        @foreach (['GST' => 'GST', 'VAT' => 'VAT'] as $val => $label)
                                        <option value="{{ $val }}" {{ old('type')==$val ? 'selected' : '' }}>
                                            {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-12">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                                        <div>
                                            <button type="button" id="tax-form-cancel"
                                                class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm d-none"
                                                onclick="cancelEditTax()"><i class="fas fa-times btn-icon me-1"></i> Cancel</button>
                                        </div>
                                        <div>
                                            @if(auth()->user()->hasPermission('settings.edit'))
                                            <button type="submit" id="tax-form-btn"
                                                class="btn btn-outline-primary btn-primary text-white fw-medium btn-sm">
                                                Add Tax <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-md-8">
                    <div class="bg-light p-2 rounded-3 h-100">
                        <div class="mb-2"> 
                            <h6 class="fw-semibold text-primary small lh-sm mb-0">Taxes List</h6>
                        </div>

                        {{-- Taxes Grouped by Type --}}
                        @php
                        $taxTypes = ['GST', 'VAT', 'Sales Tax', 'Service Tax', 'Other'];
                        $groupedTaxes = $taxes->groupBy('type');
                        @endphp
                        <div class="tax-list-grid">
                            @foreach ($taxTypes as $taxType)
                            @php
                            $group = $groupedTaxes->get($taxType, collect());
                            @endphp
                            @if ($group->count() > 0)
                            <div class="field-gap mb-4">
                                <div class="mb-2">
                                    <h6 class="fw-semibold text-dark mb-0">
                                        <span
                                            class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">{{
                                            $taxType }}</span>
                                        — <span class="text-muted small">{{ $group->count() }}
                                            tax{{ $group->count() > 1 ? 'es' : '' }}</span>
                                    </h6>
                                </div>
                                <div class="card border-0 shadow-sm overflow-hidden mb-3">
                                    <div class="table-responsive">
                                        <table class="table mainTable border align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 80px;">#</th>
                                                    <th>Rate</th>
                                                    <th style="width: 150px;">Status</th>
                                                    <th style="width: 150px;" class="text-end pe-3">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($group as $index => $tax)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $tax->rate }}%</td>
                                                    <td>
                                                        <div class="tableActionButton d-inline-flex">
                                                            <button type="button"
                                                                class="js-term-status-badge {{ $tax->is_active ? 'bg02 color02' : 'bg-secondary text-white' }}"
                                                                data-toggle-url="{{ route('taxes.toggle', $tax) }}"
                                                                data-is-active="{{ $tax->is_active ? '1' : '0' }}"
                                                                title="Click to {{ $tax->is_active ? 'Deactivate' : 'Activate' }}">
                                                                {{ $tax->is_active ? 'Active' : 'Inactive' }}
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="text-end pe-3">
                                                        <div class="tableActionButton d-inline-flex gap-1">
                                                            @if(auth()->user()->hasPermission('settings.edit'))
                                                            <a href="javascript:void(0)" class="bg03 color03"
                                                                data-id="{{ $tax->taxid }}" data-rate="{{ $tax->rate }}"
                                                                data-type="{{ $tax->type }}" data-name="{{ $tax->tax_name }}"
                                                                onclick="startEditTax(this)">Edit</a>
                                                            @endif
                                                            <form method="POST" action="{{ route('taxes.destroy', $tax) }}"
                                                                class="d-inline" onsubmit="return confirm('Delete this tax?')">
                                                                @csrf @method('DELETE')
                                                                @if(auth()->user()->hasPermission('settings.edit'))
                                                                <button type="submit" class="bg04 color04">Delete</button>
                                                                @endif
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @if ($taxes->isEmpty())
                        <p class="text-center py-5 text-muted">No taxes configured yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Fixed Tax Rate Modal --}}
        <div class="modal fade" id="fixedTaxRateModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-white border-bottom">
                        <h5 class="modal-title fw-semibold" id="fixedTaxRateModalLabel">Add Tax</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('account.fixed-tax.update') }}" id="fixed-tax-form"
                        class="mainForm">
                        @csrf
                        <div class="modal-body bg-light p-4">
                            <div class="mb-3">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                    for="fixed_tax_rate">Rate (%)<span class="text-danger">*</span></label>
                                <input type="number" id="fixed_tax_rate" name="fixed_tax_rate" placeholder="18"
                                    step="0.01" min="0" max="100"
                                    value="{{ old('fixed_tax_rate', $account->fixed_tax_rate ?? 0) }}" required
                                    class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                    for="fixed_tax_type">Type<span class="text-danger">*</span></label>
                                <select id="fixed_tax_type" name="fixed_tax_type" required class="form-select">
                                    @foreach (['GST' => 'GST', 'VAT' => 'VAT'] as $v => $l)
                                    <option value="{{ $v }}" {{ old('fixed_tax_type', $account->fixed_tax_type ?? 'GST')
                                        ==
                                        $v ? 'selected' : '' }}>
                                        {{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <button type="button" class="btn btn-outline-primary bg-white text-primary fw-medium"
                                    data-bs-dismiss="modal">
                                    <i class="fas fa-times btn-icon me-1"></i> Cancel
                                </button>
                                @if(auth()->user()->hasPermission('settings.edit'))
                                <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                    Save <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

