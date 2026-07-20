        <!-- FINANCIAL YEAR -->
        <div id="financial-year" class="tab-pane fade {{ $activeSettingsTab === 'financial-year' ? 'show active' : '' }}" role="tabpanel">           
            <div class="row g-2 align-items-stretch">
                <div class="col-12 col-md-12"> 
                    <div class="meta-info ps-2">
                        <strong class="fw-bold fs-5 lh-sm">Financial Year (FY)</strong>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="bg-light p-2 rounded-3 h-100">
                        <div class="mb-2">
                            <h6 class="fw-semibold text-primary small lh-sm mb-0">Add FY</h6>
                        </div>
                        <form method="POST" action="{{ route('financial-year.update') }}" class="mainForm">
                            @csrf
                            <div class="row g-1 align-items-end">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Start
                                        Year</label>
                                    <select name="year_start" id="fy_year_start" required class="form-select">
                                        @php $currentYear = date('Y'); @endphp
                                        @for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++) <option
                                            value="{{ $y }}" {{ $y==$currentYear ? 'selected' : '' }}>{{ $y }}
                                            </option>
                                            @endfor
                                    </select>
                                </div>
                                <div class="col-3 col-md-1 pb-2 text-dark fw-bold text-center">-</div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">End
                                        Year</label>
                                    <select name="year_end" id="fy_year_end" required class="form-select">
                                        @for ($y = $currentYear; $y <= $currentYear + 2; $y++) <option
                                            value="{{ $y }}" {{ $y==$currentYear + 1 ? 'selected' : '' }}>{{ $y
                                            }}
                                            </option>
                                            @endfor
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="text-end">
                                        @if(auth()->user()->hasPermission('settings.edit'))
                                        <button type="submit"
                                            class="btn btn-outline-primary btn-primary text-white fw-medium">
                                             Add FY <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </form> 
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="bg-light p-2 rounded-3 h-100">
                        <div class="mb-2">
                            <h6 class="fw-semibold text-primary small lh-sm mb-0">FY List</h6>
                        </div>
                        <div class="card border-0 overflow-hidden">
                            <div class="table-responsive">
                                <table class="table table-striped mainTable border align-middle mb-0">
                                    <thead class="table-light">
                                            <tr>
                                                <th>Financial Year</th>
                                                <th>Status</th>
                                                <th class="text-end pe-3">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($financialYears as $index => $fy)
                                            <tr>
                                                <td><span class="fw-semibold text-dark">{{ $fy->financial_year
                                                        }}</span></td>
                                                <td>
                                                    @if ($fy->default)
                                                    <span
                                                        class="badge bg-white text-success border rounded-pill border-success-subtle px-2 py-1">Default</span>
                                                    @else
                                                    <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-end pe-3">
                                                    @if (!$fy->default)
                                                    <div class="tableActionButton d-inline-flex gap-1">
                                                        <form method="POST"
                                                            action="{{ route('financial-year.default', $fy->fy_id) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            @method('PUT')
                                                            @if(auth()->user()->hasPermission('settings.edit'))
                                                            <button type="submit" class="bg03 color03" title="Set Default">
                                                                Set Default
                                                            </button>
                                                            @endif
                                                        </form>
                                                    </div>
                                                    @endif
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-4 text-muted">No financial
                                                    years yet.</td>
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

