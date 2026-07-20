        <div id="message-templates"
            class="tab-pane fade {{ $activeSettingsTab === 'message-templates' ? 'show active' : '' }}" role="tabpanel">
            <div class="row g-2 align-items-stretch">
                <div class="col-12 col-md-12"> 
                    <div class="meta-info ps-2">
                        <strong class="fw-bold fs-5 lh-sm">Automation Templates</strong>
                    </div>
                </div>
                <div class="col-12 col-md-12"> 
                    <div class="bg-light p-2 rounded-3 h-100">
                        @php
                        $typeIcons = [
                            'pi' => 'far fa-file-lines',
                            'ti' => 'fas fa-file-invoice-dollar',
                            'quotation' => 'fas fa-file-signature',
                            'reminder' => 'far fa-clock',
                            'expiry' => 'far fa-calendar-times',
                            'payment_received' => 'far fa-check-circle',
                        ];
                        @endphp
                        <ul class="nav nav-underline mb-3 settings-tab-group border-bottom" role="tablist">
                            @foreach ($messageTemplateTypes as $typeKey => $typeLabel)
                            <li class="nav-item">
                                <button type="button"
                                    class="nav-link btn btn-md px-3 settings-tab-btn mt-type-tab-btn {{ $loop->first ? 'is-active active rounded-0 text-primary bg-primary-subtle border-primary fw-bold' : 'rounded-0 text-primary bg-transparent border-transparent' }} d-inline-flex align-items-center gap-2"
                                    data-type="{{ $typeKey }}">
                                    <i class="{{ $typeIcons[$typeKey] ?? 'far fa-file' }}"></i>
                                    {{ $typeLabel }}
                                </button>
                            </li>
                            @endforeach
                            <li class="nav-item">
                                <button type="button"
                                    class="nav-link btn btn-md px-3 settings-tab-btn mt-type-tab-btn rounded-0 text-primary bg-transparent border-transparent d-inline-flex align-items-center gap-2"
                                    data-type="consolidated">
                                    <i class="fas fa-layer-group"></i>
                                    Consolidated
                                </button>
                            </li>
                        </ul>
                        <div class="position-relative">
                            @php
                            // Flatten all templates into a single collection for the right-side list
                            $defaultTypeKey = array_key_first($messageTemplateTypes);
                            $templateContextMap = [];
                            $allTemplates = collect();
                            foreach ($messageTemplatesByType as $t) {
                            $allTemplates = $allTemplates->concat($t);
                            }
                            foreach ($allTemplates as $tpl) {
                            $ctxKey = ($tpl->template_type ?? '') . '|' . ($tpl->channel ?? '');
                            if ($ctxKey !== '|') {
                            $templateContextMap[$ctxKey] = [
                            'templateid' => (string) ($tpl->templateid ?? ''),
                            'template_type' => (string) ($tpl->template_type ?? ''),
                            'channel' => (string) ($tpl->channel ?? ''),
                            'name' => (string) ($tpl->name ?? ''),
                            'subject' => (string) ($tpl->subject ?? ''),
                            'body' => (string) ($tpl->body ?? ''),
                            'template_id' => (string) ($tpl->template_id ?? ''),
                            'sender_id' => (string) ($tpl->sender_id ?? ''),
                            ];
                            }
                            }
                            @endphp

                            <div class="row align-items-stretch g-2" id="template-editors-container">
                                <!-- Email Column -->
                                <div class="col-12 col-lg-4" id="email-form-col">
                                    <div id="email-form-container" class="h-100">
                                        <form method="POST" action="{{ route('message-templates.store') }}"
                                        class="mainForm message-template-form d-flex flex-column h-100" data-channel="email"
                                        data-store-action="{{ route('message-templates.store') }}"
                                        data-update-base="{{ url('settings/message-templates') }}" autocomplete="off">
                                        @csrf
                                        <input type="hidden" name="template_type" value="{{ $defaultTypeKey }}">
                                        <input type="hidden" name="templateid" class="template-id-input" value="">
                                        <input type="hidden" name="channel" class="template-channel-input" value="email">

                                        <div class="bg-white p-2 rounded-3 h-100">
                                            <div class="mb-3 border-bottom rounded-3 bg-light p-2 d-flex justify-content-between align-items-center">
                                                <h5 class="fw-semibold text-primary small lh-sm mb-0"><i
                                                        class="fas fa-envelope fs-6 lh-sm me-1"></i> Email Template <span class="text-dark fw-normal">(One template per type)</span></h5>
                                                @if(auth()->user()->hasPermission('settings.edit'))
                                                <button type="submit"
                                                    class="btn btn-primary text-white fw-medium template-submit-btn h-auto">
                                                    Save Email Template <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                                </button>
                                                @endif
                                            </div> 
                                            <div class="d-flex flex-column grow">
                                                <div class="row g-2 mb-2">
                                                    <div class="col-6 form-group">
                                                        <label
                                                            class="form-label small lh-sm fw-semibold text-dark mb-1">Template
                                                            Name<span class="template-name-required-mark text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control template-name-input"
                                                            placeholder="{{ $messageTemplateTypes[$defaultTypeKey] ?? '' }} Email Template"
                                                            required>
                                                    </div>

                                                    <div class="col-6 form-group template-subject-group">
                                                        <label class="form-label small lh-sm fw-semibold text-dark mb-1">Subject
                                                            (optional)</label>
                                                        <input type="text" name="subject"
                                                            class="form-control template-subject-input"
                                                            placeholder="{{ $messageTemplateTypes[$defaultTypeKey] ?? '' }} update for @{{ client_name }}"
                                                            autocomplete="off">
                                                    </div>
                                                </div>

                                                <div class="form-group mb-2 grow d-flex flex-column">
                                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Message
                                                        Body<span class="template-body-required-mark text-danger">*</span></label>
                                                    <textarea name="body" id="templateBodyInput-email" rows="5"
                                                        class="form-control template-body-input grow"
                                                        autocomplete="off"
                                                        placeholder="Hi @{{ client_name }},\nPlease find the details below."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    </div>
                                </div>

                                <!-- WhatsApp Column -->
                                <div class="col-12 col-lg-4" id="whatsapp-form-col">
                                    <form method="POST" action="{{ route('message-templates.store') }}"
                                        class="mainForm message-template-form d-flex flex-column h-100" data-channel="whatsapp"
                                        data-store-action="{{ route('message-templates.store') }}"
                                        data-update-base="{{ url('settings/message-templates') }}" autocomplete="off">
                                        @csrf
                                        <input type="hidden" name="template_type" value="{{ $defaultTypeKey }}">
                                        <input type="hidden" name="templateid" class="template-id-input" value="">
                                        <input type="hidden" name="channel" class="template-channel-input" value="whatsapp">

                                        <div class="bg-white p-2 rounded-3 h-100">
                                            <div class="mb-3 border-bottom rounded-3 bg-light p-2 d-flex justify-content-between align-items-center">
                                                <h5 class="fw-semibold text-success small lh-sm mb-0"><i
                                                        class="fab fa-whatsapp fs-6 lh-sm me-1"></i> WhatsApp Template <span class="text-dark fw-normal">(One template per type)</span></h5>
                                                @if(auth()->user()->hasPermission('settings.edit'))
                                                <button type="submit"
                                                    class="btn btn-primary text-white fw-medium template-submit-btn h-auto">
                                                    Save WhatsApp Template <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                                </button>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-column grow">
                                                <div class="row g-2 mb-2">
                                                    <div class="col-6 form-group">
                                                        <label
                                                            class="form-label small lh-sm fw-semibold text-dark mb-1">Template
                                                            Name (optional)</label>
                                                        <input type="text" name="name" class="form-control template-name-input"
                                                            placeholder="{{ $messageTemplateTypes[$defaultTypeKey] ?? '' }} WhatsApp Template">
                                                    </div>

                                                    <div class="col-6 form-group template-wa-template-id-group">
                                                        <label
                                                            class="form-label small lh-sm fw-semibold text-dark mb-1">WhatsApp
                                                            Template ID <span class="text-danger">*</span></label>
                                                        <input type="text" name="template_id"
                                                            class="form-control template-wa-template-id-input template-external-id-input"
                                                            placeholder="wa_template_42" autocomplete="off" required>
                                                    </div>
                                                </div>

                                                <div class="form-group mb-2 grow d-flex flex-column">
                                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1 d-flex justify-content-between align-items-center w-100">
                                                        <span>Message Body</span>
                                                        <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 m-0" onclick="refreshTemplateFromCampio('whatsapp')">
                                                            <i class="fas fa-sync-alt"></i> Refresh template
                                                        </button>
                                                    </label>
                                                    <textarea name="body" id="templateBodyInput-whatsapp" rows="5"
                                                        class="form-control template-body-input grow"
                                                        autocomplete="off"
                                                        placeholder="Hi @{{ client_name }},\nPlease find the details below."></textarea>
                                                    <small class="small lh-sm text-muted mt-1 mb-0">
                                                        Message text is fixed by the provider template. Only keep/update dynamic
                                                        variables here.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- SMS Column -->
                                <div class="col-12 col-lg-4" id="sms-form-col">
                                    <form method="POST" action="{{ route('message-templates.store') }}"
                                        class="mainForm message-template-form d-flex flex-column h-100" data-channel="sms"
                                        data-store-action="{{ route('message-templates.store') }}"
                                        data-update-base="{{ url('settings/message-templates') }}" autocomplete="off">
                                        @csrf
                                        <input type="hidden" name="template_type" value="{{ $defaultTypeKey }}">
                                        <input type="hidden" name="templateid" class="template-id-input" value="">
                                        <input type="hidden" name="channel" class="template-channel-input" value="sms">

                                        <div class="bg-white p-2 rounded-3 h-100">
                                            <div class="mb-3 border-bottom rounded-3 bg-light p-2 d-flex justify-content-between align-items-center">
                                                <h5 class="fw-semibold small lh-sm mb-0" style="    color: #1179c5;"><i
                                                        class="fas fa-sms fs-6 lh-sm me-1"></i> SMS Template <span class="text-dark fw-normal">(One template per type)</span></h5>
                                                @if(auth()->user()->hasPermission('settings.edit'))
                                                <button type="submit"
                                                    class="btn btn-primary text-white fw-medium template-submit-btn h-auto">
                                                    Save SMS Template <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                                </button>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-column grow">
                                                <div class="form-group mb-2">
                                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Template
                                                        Name (optional)</label>
                                                    <input type="text" name="name" class="form-control template-name-input"
                                                        placeholder="{{ $messageTemplateTypes[$defaultTypeKey] ?? '' }} SMS Template">
                                                </div>

                                                <div class="row g-2 mb-2">
                                                    <div class="col-6 form-group">
                                                        <label class="form-label small lh-sm fw-semibold text-dark mb-1">SMS
                                                            Template ID <span class="text-danger">*</span></label>
                                                        <input type="text" name="template_id"
                                                            class="form-control template-external-id-input"
                                                            placeholder="sms_template_15" autocomplete="off" required>
                                                    </div>

                                                    <div class="col-6 form-group">
                                                        <label class="form-label small lh-sm fw-semibold text-dark mb-1">SMS
                                                            Sender ID (optional)</label>
                                                        <input type="text" name="sender_id"
                                                            class="form-control template-sender-id-input" placeholder=""
                                                            autocomplete="off">
                                                    </div>
                                                </div>

                                                <div class="form-group mb-2 grow d-flex flex-column">
                                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1 d-flex justify-content-between align-items-center w-100">
                                                        <span>Message Body</span>
                                                        <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 m-0" onclick="refreshTemplateFromCampio('sms')">
                                                            <i class="fas fa-sync-alt"></i> Refresh template
                                                        </button>
                                                    </label>
                                                    <textarea name="body" id="templateBodyInput-sms" rows="5"
                                                        class="form-control template-body-input grow"
                                                        autocomplete="off"
                                                        placeholder="Hi @{{ client_name }},\nPlease find the details below."></textarea>
                                                    <small class="small lh-sm text-muted mt-1 mb-0">
                                                        Message text is fixed by the provider template. Only keep/update dynamic
                                                        variables here.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-12 col-md-12">
                                     <!-- Template Variables Helper Box at the bottom of the row -->
                                        <div class="meta-info ps-2">
                                            <strong class="fw-bold fs-5 lh-sm">Available Template Variables</strong>
                                        </div>
                                    <div class="bg-white p-2 rounded-3 mt-2">
                                        <div class="d-flex flex-wrap gap-2 template-variable-badges"></div>
                                        <small class="d-block small lh-sm text-muted mt-2 template-variable-help">
                                            Showing common tags and tags relevant to the selected template type.
                                        </small> 
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row align-items-stretch g-3 d-none" id="consolidated-view-container">
                                <!-- Order Summary Column -->
                                <div class="col-12 col-xl-6">
                                    <div class="bg-white p-2 rounded-3 border d-flex flex-column h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong class="fw-semibold text-primary small lh-sm">
                                                <i class="fas fa-eye fs-6 lh-sm me-1"></i> Consolidated Order Summary
                                            </strong>
                                            <form method="POST" action="{{ route('settings.consolidated-days.update') }}" class="m-0 d-flex gap-2 align-items-center">
                                                @csrf
                                                <label class="form-label small lh-sm fw-semibold text-dark mb-0 text-nowrap">Trigger Days:</label>
                                                <input type="number" name="days" value="{{ $consolidatedReminderDays ?? 10 }}" class="form-control form-control-sm" style="width: 70px" min="1" max="90" required>
                                                <button type="submit" class="btn btn-sm btn-primary fw-medium px-2 py-1" title="Save trigger days configuration">Save</button>
                                            </form>
                                        </div>
                                        <div class="flex-grow-1" style="min-height: 500px;">
                                            @include('settings.partials.consolidated-preview')
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Payment Due Column -->
                                <div class="col-12 col-xl-6">
                                    <div class="bg-white p-2 rounded-3 border d-flex flex-column h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong class="fw-semibold text-primary small lh-sm">
                                                <i class="fas fa-eye fs-6 lh-sm me-1"></i> Consolidated Payments Due
                                            </strong>
                                            <form method="POST" action="{{ route('settings.consolidated-payment-days.update') }}" class="m-0 d-flex gap-2 align-items-center">
                                                @csrf
                                                <label class="form-label small lh-sm fw-semibold text-dark mb-0 text-nowrap">Trigger Days:</label>
                                                <input type="number" name="days" value="{{ $consolidatedPaymentReminderDays ?? 5 }}" class="form-control form-control-sm" style="width: 70px" min="1" max="90" required>
                                                <button type="submit" class="btn btn-sm btn-primary fw-medium px-2 py-1" title="Save trigger days configuration">Save</button>
                                            </form>
                                        </div>
                                        <div class="flex-grow-1" style="min-height: 500px;">
                                            @include('settings.partials.consolidated-payment-preview')
                                        </div>
                                    </div>
                                </div>
                            </div>   
                        </div>
                    </div>                                           
                </div> 
            </div>
        </div>

        <!-- TERMS & CONDITIONS TAB -->
