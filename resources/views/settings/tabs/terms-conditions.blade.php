        <div id="terms-conditions"
            class="tab-pane fade {{ $activeSettingsTab === 'terms-conditions' ? 'show active' : '' }}" role="tabpanel">


             <div class="row g-2 align-items-stretch">
                <div class="col-12 col-md-12"> 
                    <div class="meta-info ps-2">
                        <strong class="fw-bold fs-5 lh-sm">Terms & Conditions</strong>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="bg-light p-2 rounded-3 h-100">
                        <div class="mb-2">
                            <h6 class="fw-semibold text-primary small lh-sm mb-0">Add Terms & Conditions</h6>
                        </div>
                          <form method="POST" action="{{ route('terms-conditions.store') }}" class="mainForm">
                            @csrf
                            @if ($editingTerm)
                            <input type="hidden" name="tc_id" value="{{ $editingTerm->tc_id }}">
                            @endif

                            <div class="row g-2">
                                {{-- Left: Type + checkbox + submit --}}
                                <div class="col-12 col-lg-12 d-flex flex-column gap-2">
                                    <div>
                                        <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                            for="settings_term_type">Type<span class="text-danger">*</span></label>
                                        <select id="settings_term_type" name="type" required class="form-select">
                                            <option value="billing" {{ old('type', $editingTerm->type ?? '') == 'billing' ?
                                                'selected' : '' }}>Billing</option>
                                            <option value="quotation" {{ old('type', $editingTerm->type ?? '') ==
                                                'quotation' ? 'selected' : '' }}>Quotation</option>
                                            <option value="proforma" {{ old('type', $editingTerm->type ?? '') == 'proforma'
                                                ? 'selected' : '' }}>Proforma</option>
                                        </select>
                                    </div>
                                    
                                </div>

                                {{-- Right: Textarea --}}
                                <div class="col-12 col-lg-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                        for="settings_tc_content">Terms and Condition<span
                                            class="text-danger">*</span></label>
                                    <textarea id="settings_tc_content" name="content" rows="6"
                                        placeholder="Enter terms and condition"
                                        class="form-control w-100">{{ old('content', $editingTerm->content ?? '') }}</textarea>
                                </div>
                                <div class="col-12 col-md-12">
                                    <div class="mb-0 bg-white border rounded-1 px-2 py-1 ms-1">
                                        <div class="form-check mb-0 form-check-large">
                                            <input type="hidden" name="is_default" value="0">
                                            <input type="checkbox" name="is_default" value="1" class="form-check-input"
                                                id="settings_tc_default" {{ old('is_default', (int)
                                                ($editingTerm->is_default ?? 0)) ? 'checked' : '' }}>
                                            <label class="form-check-label small lh-sm fw-normal text-dark"
                                                for="settings_tc_default">
                                                Set as default
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-12">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                                        <div> 
                                        @if ($editingTerm)
                                        <a href="{{ route('settings.index', ['t' => request('t', $editingTerm->type ?? 'billing')]) }}#terms-conditions"
                                            class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm">
                                            <i class="fas fa-sync-alt btn-icon me-1"></i> Clear
                                        </a>
                                        @endif
                                        </div>  
                                        <div>
                                        @if(auth()->user()->hasPermission('settings.edit'))
                                        <button type="submit"
                                            class="btn btn-outline-primary btn-primary text-white fw-medium btn-sm">
                                            {{ $editingTerm ? 'Update Terms & Conditions' : 'Add Terms & Conditions' }} <i class="fas fa-arrow-right btn-icon ms-1"></i>
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
                            <h6 class="fw-semibold text-primary small lh-sm mb-0">Terms & Conditions List</h6>
                        </div>
                        <ul class="nav nav-underline d-inline-flex mb-3 settings-tab-group border-bottom rounded-3 gap-0" id="tcTypeTabs" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link btn btn-md px-3 rounded-0 tc-type-tab rounded-0 text-primary bg-primary-subtle border-primary fw-bold active" data-bs-toggle="tab"
                                    data-bs-target="#billing-tc" role="tab" aria-controls="billing-tc" aria-selected="true">
                                    <i class="far fa-credit-card me-1"></i> Billing
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link btn btn-md px-3 rounded-0 tc-type-tab rounded-0 text-primary bg-transparent border-transparent" data-bs-toggle="tab"
                                    data-bs-target="#quotation-tc" role="tab" aria-controls="quotation-tc"
                                    aria-selected="false">
                                    <i class="far fa-file-alt me-1"></i> Quotation
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link btn btn-md px-3 rounded-0 tc-type-tab rounded-0 text-primary bg-transparent border-transparent" data-bs-toggle="tab"
                                    data-bs-target="#proforma-tc" role="tab" aria-controls="proforma-tc" aria-selected="false">
                                    <i class="far fa-file me-1"></i> Proforma
                                </button>
                            </li>
                        </ul>

                <div class="tab-content tc-grid">
                    {{-- Billing Terms List --}}
                    <div class="tab-pane fade show active tc-type-pane" id="billing-tc" data-tc-type="billing"
                        role="tabpanel">
                        <div class="mb-2"> 
                            <h6 class="fw-bold fs-5 lh-sm mb-0">Billing</h6>
                        </div>
                        <div class="card border-0 shadow-sm overflow-hidden mb-0"> 
                            <div class="table-responsive">
                                <table class="table table-striped mainTable border align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="10%">Seq</th>
                                            <th>Particular</th>
                                            <th width="10%" class="text-center"></th>
                                            <th width="20%" class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($billingTerms as $index => $term)
                                        <tr>
                                            <td>
                                                <form method="POST"
                                                    action="{{ route('terms-conditions.update-sequence', $term) }}"
                                                    class="settings-sequence-form">
                                                    @csrf @method('PATCH')
                                                    <select name="sequence" onchange="this.form.submit()"
                                                        class="form-select form-select-sm" style="width: 70px;" {{ !auth()->user()->hasPermission('settings.edit') ? 'disabled' : '' }}>
                                                        @for ($i = 1; $i <= $billingTerms->count(); $i++)
                                                            <option value="{{ $i }}" {{ $index + 1 == $i ? 'selected' : '' }}>
                                                                {{ $i }}</option>
                                                            @endfor
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="text-wrap align-middle">{!! str_replace('<p>', '<p class="mb-0">', $term->content) !!}
                                            </td>
                                            <td class="text-center">
                                                @if ($term->is_default)
                                                <span
                                                    class="badge bg-white text-success border rounded-pill border-success-subtle px-2 py-1">Default</span>
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                    @if(auth()->user()->hasPermission('settings.edit'))
                                                    <button type="button"
                                                        class="js-term-status-badge {{ $term->is_active ? 'bg02 color02' : 'bg-secondary text-white' }}"
                                                        data-toggle-url="{{ route('terms-conditions.toggle', $term) }}"
                                                        data-is-active="{{ $term->is_active ? '1' : '0' }}"
                                                        title="Click to {{ $term->is_active ? 'Deactivate' : 'Activate' }}">
                                                        {{ $term->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                    <a href="{{ route('settings.index', ['e' => base64_encode($term->tc_id), 't' => 'billing']) }}#terms-conditions"
                                                        class="bg03 color03" title="Edit">Edit</a>
                                                    <form method="POST"
                                                        action="{{ route('terms-conditions.destroy', $term) }}"
                                                        class="d-inline" onsubmit="return confirm('Delete this term?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="bg04 color04"
                                                            title="Delete">Delete</button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No billing T&C added
                                                yet.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Quotation Terms List --}}
                    <div class="tab-pane fade tc-type-pane" id="quotation-tc" data-tc-type="quotation" role="tabpanel">
                        <div class="mb-2">
                            <h6 class="fw-bold fs-5 lh-sm mb-0">Quotation</h6>
                        </div>
                        <div class="card border-0 shadow-sm overflow-hidden mb-0">
                            <div class="table-responsive">
                                <table class="table table-striped mainTable border align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="10%">Seq</th>
                                            <th>Particular</th>
                                            <th width="10%" class="text-center"></th>
                                            <th width="20%" class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($quotationTerms as $index => $term)
                                        <tr>
                                            <td>
                                                <form method="POST"
                                                    action="{{ route('terms-conditions.update-sequence', $term) }}"
                                                    class="settings-sequence-form">
                                                    @csrf @method('PATCH')
                                                    <select name="sequence" onchange="this.form.submit()"
                                                        class="form-select form-select-sm" style="width: 70px;" {{ !auth()->user()->hasPermission('settings.edit') ? 'disabled' : '' }}>
                                                        @for ($i = 1; $i <= $quotationTerms->count(); $i++)
                                                            <option value="{{ $i }}" {{ $index + 1 == $i ? 'selected' : '' }}>
                                                                {{ $i }}</option>
                                                            @endfor
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="text-wrap align-middle">{!! str_replace('<p>', '<p class="mb-0">', $term->content) !!}
                                            </td>
                                            <td class="text-center">
                                                @if ($term->is_default)
                                                <span
                                                    class="badge bg-white text-success border rounded-pill border-success-subtle px-2 py-1">Default</span>
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                    @if(auth()->user()->hasPermission('settings.edit'))
                                                    <button type="button"
                                                        class="js-term-status-badge {{ $term->is_active ? 'bg02 color02' : 'bg-secondary text-white' }}"
                                                        data-toggle-url="{{ route('terms-conditions.toggle', $term) }}"
                                                        data-is-active="{{ $term->is_active ? '1' : '0' }}"
                                                        title="Click to {{ $term->is_active ? 'Deactivate' : 'Activate' }}">
                                                        {{ $term->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                    <a href="{{ route('settings.index', ['e' => base64_encode($term->tc_id), 't' => 'quotation']) }}#terms-conditions"
                                                        class="bg03 color03" title="Edit">Edit</a>
                                                    <form method="POST"
                                                        action="{{ route('terms-conditions.destroy', $term) }}"
                                                        class="d-inline" onsubmit="return confirm('Delete this term?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="bg04 color04"
                                                            title="Delete">Delete</button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No quotation T&C added
                                                yet.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Proforma Terms List --}}
                    <div class="tab-pane fade tc-type-pane" id="proforma-tc" data-tc-type="proforma" role="tabpanel">
                        <div class="mb-2">
                            <h6 class="fw-bold fs-5 lh-sm mb-0">Proforma</h6>
                        </div>
                        <div class="card border-0 shadow-sm overflow-hidden mb-0">
                            <div class="table-responsive">
                                <table class="table table-striped mainTable border align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="10%">Seq</th>
                                            <th>Particular</th>
                                            <th width="10%" class="text-center"></th>
                                            <th width="20%" class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($proformaTerms as $index => $term)
                                        <tr>
                                            <td>
                                                <form method="POST"
                                                    action="{{ route('terms-conditions.update-sequence', $term) }}"
                                                    class="settings-sequence-form">
                                                    @csrf @method('PATCH')
                                                    <select name="sequence" onchange="this.form.submit()"
                                                        class="form-select form-select-sm" style="width: 70px;" {{ !auth()->user()->hasPermission('settings.edit') ? 'disabled' : '' }}>
                                                        @for ($i = 1; $i <= $proformaTerms->count(); $i++)
                                                            <option value="{{ $i }}" {{ $index + 1 == $i ? 'selected' : '' }}>
                                                                {{ $i }}</option>
                                                            @endfor
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="text-wrap align-middle">{!! str_replace('<p>', '<p class="mb-0">', $term->content) !!}
                                            </td>
                                            <td class="text-center">
                                                @if ($term->is_default)
                                                <span
                                                    class="badge bg-white text-success border rounded-pill border-success-subtle px-2 py-1">Default</span>
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                    @if(auth()->user()->hasPermission('settings.edit'))
                                                    <button type="button"
                                                        class="js-term-status-badge {{ $term->is_active ? 'bg02 color02' : 'bg-secondary text-white' }}"
                                                        data-toggle-url="{{ route('terms-conditions.toggle', $term) }}"
                                                        data-is-active="{{ $term->is_active ? '1' : '0' }}"
                                                        title="Click to {{ $term->is_active ? 'Deactivate' : 'Activate' }}">
                                                        {{ $term->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                    <a href="{{ route('settings.index', ['e' => base64_encode($term->tc_id), 't' => 'proforma']) }}#terms-conditions"
                                                        class="bg03 color03" title="Edit">Edit</a>
                                                    <form method="POST"
                                                        action="{{ route('terms-conditions.destroy', $term) }}"
                                                        class="d-inline" onsubmit="return confirm('Delete this term?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="bg04 color04"
                                                            title="Delete">Delete</button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No proforma T&C added
                                                yet.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>

