        <!-- BILLING DETAILS TAB -->
        <div id="billing-details"
            class="tab-pane fade {{ $activeSettingsTab === 'billing-details' ? 'show active' : '' }}" role="tabpanel">



            {{-- DEBUG: Check if editingBillingDetail exists --}}
            @php
            echo '<!-- DEBUG: editingBillingDetail = ' .
                    (isset($editingBillingDetail) ? 'SET' : 'NOT SET') .
                    ' -->';
            @endphp

            <form method="POST" action="{{ route('account.billing.update') }}" enctype="multipart/form-data"
                class="mainForm">
                @csrf
                @if (isset($editingBillingDetail))
                <input type="hidden" name="account_bdid" value="{{ $editingBillingDetail->account_bdid }}">
                @endif
                <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                <div class="row g-2 align-items-stretch">
                    <div class="col-12 col-md-12"> 
                        <div class="meta-info ps-2">
                            <strong class="fw-bold fs-5 lh-sm">Billing Details</strong>
                        </div>
                    </div>
                    <!-- Billing Information Card -->
                    <div class="col-12 col-lg-4">                    
                        <div class="bg-light p-2 rounded-3 h-100">
                            <div class="mb-2">
                                <h5 class="fw-semibold text-primary small lh-sm mb-0">Billing Profile</h5>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Business Billing Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="billing_name" class="form-control"
                                        value="{{ old('billing_name', $editingBillingDetail->billing_name ?? ($account->name ?? '')) }}"
                                        required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Billing From Email</label>
                                    <input type="text" name="billing_from_email" class="form-control"
                                        value="{{ old('billing_from_email', $editingBillingDetail->billing_from_email ?? '') }}"
                                        placeholder="billing@company.com, finance@company.com">
                                    <div class="form-text text-muted small mt-1">Use comma to add multiple emails</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Billing From Name <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="text" name="billing_from_name" class="form-control"
                                        value="{{ old('billing_from_name', $editingBillingDetail->billing_from_name ?? '') }}"
                                        placeholder="e.g. SkoolReady Billing Team">
                                    <div class="form-text text-muted small mt-1">Shown as the sender name in outgoing emails.</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Authorize Signatory</label>
                                    <input type="text" name="authorize_signatory" class="form-control"
                                        value="{{ old('authorize_signatory', $editingBillingDetail->authorize_signatory ?? '') }}">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Designation</label>
                                    <input type="text" name="designation" class="form-control"
                                        value="{{ old('designation', $editingBillingDetail->designation ?? '') }}">
                                </div>
                            </div>
                            <div class="mb-2">
                                <h5 class="fw-semibold text-primary small lh-sm mb-0">Tax &amp; Verification</h5>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">GSTIN</label>
                                    <input type="text" name="gstin" class="form-control"
                                        value="{{ old('gstin', $editingBillingDetail->gstin ?? '') }}" maxlength="15"
                                        minlength="15" pattern="[A-Z0-9]{15}" title="GSTIN must be exactly 15 characters"
                                        oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
                                        onblur="if(this.value && this.value.length!==15){this.setCustomValidity('GSTIN must be exactly 15 characters');this.reportValidity();}else{this.setCustomValidity('');}">
                                    <div class="form-text text-muted small mt-1">Exactly 15 characters required</div>
                                </div>

                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">TIN</label>
                                    <input type="text" name="tin" class="form-control"
                                        value="{{ old('tin', $editingBillingDetail->tin ?? '') }}">
                                </div>

                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Signature Upload</label>
                                    @php
                                    $hasSignature = !empty($editingBillingDetail) && !empty($editingBillingDetail->signature_upload);
                                    @endphp
                                    <div class="logo-drag-drop-zone border border-dashed rounded-3 text-center bg-white position-relative py-2"
                                        style="cursor:pointer;" id="sig-drop-zone">
                                        <input type="file" id="billing-signature-upload" name="signature_upload"
                                            accept="image/*" class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                            onchange="previewSignature(this)">

                                        <div class="drop-zone-prompt {{ $hasSignature ? 'd-none' : 'd-flex' }} align-items-center justify-content-center"
                                            id="sig-drop-zone-prompt">
                                            <i class="far fa-file text-secondary mb-2 fs-4"></i>
                                            <span class="small text-muted fw-medium ms-2">Drag and drop or <span
                                                    class="text-primary fw-semibold">browse files</span></span>
                                        </div>

                                        <div class="drop-zone-preview {{ $hasSignature ? '' : 'd-none' }} align-items-center justify-content-between w-100"
                                            id="sig-drop-zone-preview">
                                            <img id="signature-preview-img"
                                                src="{{ $hasSignature ? $editingBillingDetail->signature_upload : '#' }}"
                                                alt="Signature Preview" class="img-fluid rounded mb-0 shadow-sm" width="50px">
                                            <button type="button" id="remove-signature-btn"
                                                class="btn btn-sm btn-danger rounded-circle p-0 bg-transparent text-dark border-0"
                                                title="Remove Image">
                                                <i class="fas fa-upload fs-6 lh-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-text text-muted small mt-1">Max file size: 5MB. Supported formats: JPG, PNG, GIF, SVG</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Address Card -->
                    <div class="col-12 col-lg-4">                    
                        <div class="bg-light p-2 rounded-3 h-100">
                            <div class="mb-2">
                                <h5 class="fw-semibold text-primary small lh-sm mb-0">Billing Address</h5>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                                    <select name="billing_country" class="country-select form-select"
                                        data-selected="{{ old('billing_country', $editingBillingDetail->country ?? 'India') }}">
                                        <option value="">Select Country</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">State <span
                                            class="text-danger">*</span></label>
                                    <select name="billing_state" required class="state-select form-select"
                                        data-selected="{{ old('billing_state', $editingBillingDetail->state ?? '') }}">
                                        <option value="">Select State</option>
                                    </select>
                                    @error('billing_state')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                                    <select name="billing_city" class="city-select form-select"
                                        data-selected="{{ old('billing_city', $editingBillingDetail->city ?? '') }}">
                                        <option value="">Select City</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Postal Code</label>
                                    <input type="text" name="billing_postal_code" class="form-control"
                                        value="{{ old('billing_postal_code', $editingBillingDetail->postal_code ?? '') }}">
                                </div>

                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Address</label>
                                    <textarea name="address" rows="2"
                                        class="form-control">{{ old('address', $editingBillingDetail->address ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 col-md-8">
                        <div class="text-end mt-2">
                            @if (isset($editingBillingDetail) && request('edit_bd'))
                            <a href="{{ route('settings.index') }}#billing-details"
                                class="btn btn-outline-primary bg-white text-primary fw-medium me-2">
                                <i class="fas fa-times btn-icon me-1"></i> Cancel
                            </a>
                            @endif
                            @if(auth()->user()->hasPermission('settings.edit'))
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Save Billing Detail <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                            @endif
                        </div>
                    </div>

                </div>
            </form>
        </div>

