        <!-- PERSONAL TAB -->
        <div id="personal" class="tab-pane fade {{ $activeSettingsTab === 'personal' ? 'show active' : '' }}"
            role="tabpanel">
            <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data" class="mainForm">
                    @csrf
                    @method('PUT')
                <div class="row g-2 align-items-stretch">
                    <div class="col-12 col-md-12"> 
                        <div class="meta-info ps-2">
                            <strong class="fw-bold fs-5 lh-sm">Business Information</strong>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">                    
                        <div class="bg-light p-2 rounded-3 h-100">
                            <div class="mb-2">
                                <h5 class="fw-semibold text-primary small lh-sm mb-0">Client Information</h5>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Business Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $account->name ?? '') }}"
                                        required class="form-control">
                                </div>

                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Legal Entity
                                        Name</label>
                                    <input type="text" name="legal_name"
                                        value="{{ old('legal_name', $account->legal_name ?? '') }}"
                                        class="form-control">
                                </div>

                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Website</label>
                                    <input type="text" name="website"
                                        value="{{ old('website', $account->website ?? '') }}" class="form-control">
                                </div>

                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="email" value="{{ old('email', $account->email ?? '') }}"
                                        required class="form-control"
                                        placeholder="name@company.com, accounts@company.com">
                                    <div class="form-text text-muted small mt-1">Use comma to add multiple emails</div>
                                </div>

                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Phone</label>
                                    <input type="text" name="phone" value="{{ old('phone', $account->phone ?? '') }}"
                                        class="form-control" placeholder="+91..., +1...">
                                    <div class="form-text text-muted small mt-1">Use comma to add multiple phone numbers
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Currency</label>
                                    <select name="currency_code" class="form-select">
                                        @foreach ($currencies as $currency)
                                        <option value="{{ $currency->iso }}" {{ old('currency_code', $account->currency_code ??
                                            'INR') == $currency->iso ? 'selected' : '' }}>
                                            {{ $currency->iso }} - {{ $currency->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Timezone</label>
                                    <input type="text" name="timezone"
                                        value="{{ old('timezone', $account->timezone ?? 'Asia/Kolkata') }}"
                                        class="form-control">
                                </div>
                                
                                            <!-- Logo Upload -->
                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Company Logo</label>
                                    @php
                                    $hasLogo = !empty($account->logo_path);
                                    @endphp
                                    <div class="logo-drag-drop-zone border border-dashed rounded-3 text-center bg-white position-relative py-2"
                                        style="cursor:pointer;" id="logo-drop-zone">
                                        <input type="file" id="logo-upload" name="logo" accept="image/*"
                                            class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                            onchange="previewLogo(this)">

                                        <div class="drop-zone-prompt {{ $hasLogo ? 'd-none' : 'd-flex' }} align-items-center justify-content-center"
                                            id="drop-zone-prompt">
                                            <i class="far fa-file text-secondary mb-2 fs-4"></i>
                                            <span class="small text-muted fw-medium ms-2">Drag and drop or <span
                                                    class="text-primary fw-semibold">browse files</span></span>
                                        </div>

                                        <div class="drop-zone-preview {{ $hasLogo ? '' : 'd-none' }} align-items-center justify-content-between w-100"
                                            id="drop-zone-preview">
                                            <img id="logo-preview"
                                                src="{{ $hasLogo ? (str_starts_with($account->logo_path, 'http') ? $account->logo_path : asset($account->logo_path)) : '#' }}"
                                                alt="Logo Preview" class="img-fluid rounded mb-0 shadow-sm" width="50px">
                                            <button type="button" id="remove-logo-btn"
                                                class="btn btn-sm btn-danger rounded-circle p-0 bg-transparent text-dark border-0"
                                                title="Remove Image">
                                                <i class="fas fa-upload fs-6 lh-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted small d-block mt-1">Square recommended. 5MB max.</small>
                                </div>

                            </div>
                        </div>
                    </div>            
                    <div class="col-12 col-lg-4">                    
                        <div class="bg-light p-2 rounded-3 h-100">
                            <div class="mb-2">
                                <h5 class="fw-semibold text-primary small lh-sm mb-0">Business Address</h5>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                                    <select name="country" class="country-select form-select"
                                        data-selected="{{ old('country', $account->country ?? '') }}">
                                        <option value="">Select Country</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">State<span
                                            class="text-danger">*</span></label>
                                    <select name="state" required class="state-select form-select"
                                        data-selected="{{ old('state', $account->state ?? '') }}">
                                        <option value="">Select State</option>
                                    </select>
                                    @error('state')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                                    <select name="city" class="city-select form-select"
                                        data-selected="{{ old('city', $account->city ?? '') }}">
                                        <option value="">Select City</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Postal Code</label>
                                    <input type="text" name="postal_code"
                                        value="{{ old('postal_code', $account->postal_code ?? '') }}" class="form-control">
                                </div>

                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Address</label>
                                    <textarea name="address_line_1" rows="2" class="form-control">{{ old('address_line_1', $account->address_line_1 ?? '') }}</textarea>
                                </div>
                                <div class="col-12 col-md-12">
                                    <div class="mb-0 mt-3">
                                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Financial Year</h5>
                                    </div>
                                </div>
                                <div class="col-12 col-md-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">FY Start (Day &
                                        Month)</label>
                                    <div class="d-flex gap-2">
                                        @php
                                        $currentFy = old('fy_startdate', $account->fy_startdate ?? '04-01');
                                        $parts = explode('-', $currentFy);
                                        $curMonth = $parts[0] ?? '04';
                                        $curDay = $parts[1] ?? '01';
                                        @endphp
                                        <select name="fy_day" class="fy-day-select form-select w-25">
                                            @for ($i = 1; $i <= 31; $i++) <option value="{{ sprintf('%02d', $i) }}" {{
                                                $curDay==sprintf('%02d', $i) ? 'selected' : '' }}>{{ $i }}
                                                </option>
                                                @endfor
                                        </select>
                                        <select name="fy_month" class="fy-month-select form-select w-75">
                                            @foreach (['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                                            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' =>
                                            'September', '10' => 'October', '11' => 'November', '12' => 'December'] as $mVal =>
                                            $mName)
                                            <option value="{{ $mVal }}" {{ $curMonth==$mVal ? 'selected' : '' }}>
                                                {{ $mName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>            
                    <div class="col-12 col-lg-4">                    
                        <div class="bg-light p-2 rounded-3 h-100">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div>
                                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Advanced Settings</h5>
                                    </div>
                                </div>

                                <!-- Tax Settings Toggle -->
                                <div class="col-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-2">Tax Settings</label>
                                    <div class="d-flex justify-content-between align-items-center bg-white rounded-3 border px-3 py-2">
                                        <label for="allow_multi_taxation" class="form-label small lh-sm fw-semibold text-dark mb-0"
                                            style="cursor: pointer;">
                                            Allow Multi-Taxation
                                            <span class="d-block text-dark fw-normal mt-0.5">Use different tax rates</span>
                                        </label>
                                        <div class="form-check form-switch fs-5 lh-sm mb-0">
                                            <input type="checkbox" name="allow_multi_taxation" value="1" id="allow_multi_taxation"
                                                {{ old('allow_multi_taxation', $account->allow_multi_taxation ?? false) ? 'checked' : '' }}
                                                class="form-check-input border-primary" role="switch" style="cursor: pointer;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Fixed Tax Rate Section -->
                                <div class="col-12 {{ $account->allow_multi_taxation ? 'is-hidden' : '' }}" id="fixed-tax-section">
                                    <div class="d-flex justify-content-between align-items-center bg-white rounded-3 border px-3 py-2">
                                        <span class="fw-semibold text-dark">Fixed Tax Rate</span>
                                        <div class="d-flex align-items-center gap-2">
                                            @if (!$account->allow_multi_taxation)
                                            <span class="badge bg-warning text-dark border border-warning px-2 py-1">
                                                {{ $account->fixed_tax_type ?? 'GST' }}
                                                {{ number_format($account->fixed_tax_rate ?? 0, 2) }}%
                                            </span>
                                            <button type="button" id="open-fixed-tax-modal"
                                                class="btn btn-sm btn-outline-primary bg-white text-primary h-75">
                                                {{ ($account->fixed_tax_rate ?? 0) > 0 ? 'Edit Tax' : 'Add Tax' }} <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- User Settings Toggle -->
                                <div class="col-12">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-2 mt-2">User Settings</label>
                                    <div class="d-flex justify-content-between align-items-center bg-white rounded-3 border px-3 py-2">
                                        <label for="have_users" class="form-label  fw-semibold text-dark mb-0"
                                            style="cursor: pointer;">
                                            Does your Products/Services are with the No. of Users?
                                        </label>
                                        <div class="form-check form-switch fs-5 lh-sm mb-0">
                                            <input type="checkbox" name="have_users" value="1" id="have_users"
                                                {{ old('have_users', $account->have_users ?? false) ? 'checked' : '' }}
                                                class="form-check-input border-primary" role="switch" style="cursor: pointer;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-12">
                        <div class="text-end mt-1">
                            @if(auth()->user()->hasPermission('settings.edit'))
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Update Settings <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                </div> 
            </form>
        </div>

