        <!-- CONFIG -->
        <div id="config" class="tab-pane fade {{ $activeSettingsTab === 'config' ? 'show active' : '' }}"
            role="tabpanel">
            <div class="row g-2 align-items-stretch">
                <div class="col-12 col-md-12"> 
                    <div class="meta-info ps-2">
                        <strong class="fw-bold fs-5 lh-sm">Configuration Keys</strong>
                    </div>
                </div> 
                <div class="col-12 col-md-4"> 
                    <div class="bg-light p-2 rounded-3 h-100">
                        <div class="mb-2">
                            <h6 class="fw-semibold text-primary small lh-sm mb-0">{{ $editingSetting ? 'Edit Configuration Key' : 'Add Configuration Key' }}</h6>
                        </div>
                          <form method="POST"
                        action="{{ $editingSetting ? route('settings.update', $editingSetting->settingid) : route('settings.store') }}"
                        class="mainForm">
                        @csrf
                        @if ($editingSetting)
                        @method('PUT')
                        @endif

                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Key Name <span
                                        class="text-danger">*</span></label>
                                <select id="config-key-select" name="key" required class="form-select">
                                    <option value="">-- Select Key --</option>
                                    @php
                                    $currentKey = old('key', $editingSetting->setting_key ?? '');
                                    @endphp
                                    @foreach ($suggestedKeys as $group => $keys)
                                    <optgroup label="{{ $group }}">
                                        @foreach ($keys as $key => $label)
                                        <option value="{{ $key }}" {{ $currentKey==$key ? 'selected' : '' }}>{{ $key }}
                                            ({{ $label }})</option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Value <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="value"
                                    value="{{ old('value', $editingSetting->setting_value ?? '') }}"
                                    placeholder="Enter value" required class="form-control">
                            </div>
                            <div class="col-12 col-md-3 text-end">
                                @if(auth()->user()->hasPermission('settings.edit'))
                                <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                    {{ $editingSetting ? 'Update Key' : 'Add Key' }} <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>
                                @endif
                               
                                @if ($editingSetting)
                                 <a href="{{ route('settings.index') }}#config"
                                    class="btn btn-outline-secondary">Cancel <i class="fas fa-arrow-right btn-icon ms-1"></i></a>
                                @endif
                            </div> 
                        </div>
                    </form>
                    </div>                                               
                </div> 
                <div class="col-12 col-md-4">
                    <div class="bg-light p-2 rounded-3 h-100">
                        <div class="mb-2">
                            <h6 class="fw-semibold text-primary small lh-sm mb-0">Configuration Key </h6>
                        </div>
                        <div class="card border-0 shadow-sm overflow-hidden">
                            <div class="table-responsive">
                                <table class="table table-striped mainTable border align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Key</th>
                                            <th>Value</th>
                                            <th class="text-end pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($settings as $index => $setting)
                                        <tr>
                                            <td><code class="text-danger fw-semibold">{{ $setting['key'] }}</code></td>
                                            <td><span class="text-dark">{{ $setting['value'] }}</span></td>
                                            <td class="text-end pe-3">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                    <a href="{{ route('settings.index', ['e' => base64_encode($setting['record_id'])]) }}#config"
                                                        class="bg03 color03" title="Edit">
                                                        Edit
                                                    </a>
                                                    <form method="POST"
                                                        action="{{ route('settings.destroy', $setting['record_id']) }}"
                                                        class="d-inline" onsubmit="return confirm('Delete this setting?')">
                                                        @csrf @method('DELETE')
                                                        @if(auth()->user()->hasPermission('settings.edit'))
                                                        <button type="submit" class="bg04 color04" title="Delete">
                                                            Delete
                                                        </button>
                                                        @endif
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">No settings found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>  
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- MESSAGE TEMPLATES -->
 
