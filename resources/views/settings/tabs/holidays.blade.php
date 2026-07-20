        <!-- HOLIDAYS TAB -->
        <div id="holidays" class="tab-pane fade {{ $activeSettingsTab === 'holidays' ? 'show active' : '' }}" role="tabpanel">
            <div class="row g-2 align-items-stretch">
                <div class="col-12 col-md-6">
                    <div class="meta-info ps-2"> 
                        <strong class="fw-bold fs-5 lh-sm">Holidays &amp; Weekends</strong>
                    </div>
                </div>
                <div class="col-12 col-md-6 text-md-end">
                    <button type="button" class="btn btn-outline-primary btn-sm me-2 fw-medium" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                        <i class="fas fa-plus me-1"></i> Add Holiday
                    </button>
                    <button type="button" class="btn btn-primary btn-sm text-white fw-medium" data-bs-toggle="modal" data-bs-target="#bulkWeekendModal">
                        <i class="fas fa-calendar-alt me-1"></i> Weekend Policy (Bulk Generator)
                    </button>
                </div>
                <div class="col-12 col-md-12">
                    <div class="bg-light p-2 rounded-3 h-100">
                    <!-- Sub Tabs for Holidays and Weekends -->
                    <ul class="nav nav-underline mb-3 settings-tab-group border-bottom rounded-3 gap-0" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn holiday-type-tab text-primary bg-primary-subtle border-primary fw-bold active" data-bs-toggle="tab" data-bs-target="#public-holidays-tab" role="tab" aria-selected="true">
                                Public & Custom Holidays
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn holiday-type-tab text-primary bg-transparent border-transparent" data-bs-toggle="tab" data-bs-target="#system-weekends-tab" role="tab" aria-selected="false">
                                System Weekends
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn holiday-type-tab text-primary bg-transparent border-transparent" data-bs-toggle="tab" data-bs-target="#calendar-view-tab" role="tab" aria-selected="false">
                                Yearly Calendar
                            </button>
                        </li>
                    </ul>

            <div class="tab-content">
                <!-- Public & Custom Holidays Tab -->
                <div id="public-holidays-tab" class="tab-pane fade show active" role="tabpanel">
                    @php
                        $customHolidays = collect($holidays)->where('type', '!==', 'weekend');
                        $expandedCustomHolidays = collect();
                        $currentYear = date('Y');
                        
                        foreach ($customHolidays as $holiday) {
                            $expandedCustomHolidays->push($holiday);
                            
                            if ($holiday->is_recurring && $holiday->holiday_date->format('Y') != $currentYear) {
                                $cloned = clone $holiday;
                                $cloned->holiday_date = \Carbon\Carbon::parse($currentYear . '-' . $holiday->holiday_date->format('m-d'));
                                $cloned->title = $holiday->title . ' (Recurring)';
                                $expandedCustomHolidays->push($cloned);
                            }
                        }
                        
                        $expandedCustomHolidays = $expandedCustomHolidays->sortBy('holiday_date');
                        
                        $groupedCustomHolidays = $expandedCustomHolidays->groupBy(function($holiday) {
                            return $holiday->holiday_date->format('F Y');
                        });
                    @endphp
                    
                    <div class="row g-3">
                        @forelse($groupedCustomHolidays as $monthYear => $monthHolidays)
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                            <div class="accordion" id="customAccordion-{{ $loop->index }}">
                                <div class="accordion-item border-light-subtle rounded-3 overflow-hidden">
                                    <h2 class="accordion-header" id="custom-heading-{{ $loop->index }}">
                                        <button class="accordion-button collapsed fw-semibold bg-white p-3" type="button" data-bs-toggle="collapse" data-bs-target="#custom-collapse-{{ $loop->index }}" aria-expanded="false" aria-controls="custom-collapse-{{ $loop->index }}">
                                            <div class="d-flex flex-column w-100">
                                                <span>{{ $monthYear }}</span>
                                                <span class="text-muted small fw-normal mt-1">{{ $monthHolidays->count() }} holidays</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="custom-collapse-{{ $loop->index }}" class="accordion-collapse collapse" aria-labelledby="custom-heading-{{ $loop->index }}" data-bs-parent="#customAccordion-{{ $loop->index }}">
                                        <div class="accordion-body bg-light p-2">
                                            <ul class="list-group list-group-flush small rounded border border-light-subtle">
                                                @foreach($monthHolidays as $holiday)
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1">
                                                    <div>
                                                        <div class="fw-semibold">{{ $holiday->holiday_date->format('d D') }}</div>
                                                        <div class="text-muted small">{{ $holiday->title }}</div>
                                                    </div>
                                                    <div class="tableActionButton d-inline-flex">
                                                        <form method="POST" action="{{ route('holidays.destroy', $holiday->holidayid) }}" class="d-inline" onsubmit="return confirm('Delete this holiday?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="bg04 color04" title="Delete">Delete</button>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class="text-center py-5 bg-white rounded-3 border border-dashed">
                                <div class="text-muted mb-3">
                                    <i class="far fa-calendar-times" style="font-size: 3rem;"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">No Public Holidays Found</h6>
                                <p class="text-muted small mb-3">Add a custom holiday to get started.</p>
                                <button type="button" class="btn btn-outline-primary btn-sm fw-medium" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                                    <i class="fas fa-plus me-1"></i> Add Holiday
                                </button>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- System Weekends Tab -->
                <div id="system-weekends-tab" class="tab-pane fade" role="tabpanel">
                    @php
                        $weekendHolidays = collect($holidays)->where('type', '===', 'weekend');
                        $groupedWeekendHolidays = $weekendHolidays->groupBy(function($holiday) {
                            return $holiday->holiday_date->format('F Y');
                        });
                    @endphp
                    
                    <div class="row g-3">
                        @forelse($groupedWeekendHolidays as $monthYear => $monthHolidays)
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                            <div class="accordion" id="weekendsAccordion-{{ $loop->index }}">
                                <div class="accordion-item border-light-subtle rounded-3 overflow-hidden">
                                    <h2 class="accordion-header" id="weekend-heading-{{ $loop->index }}">
                                        <button class="accordion-button collapsed fw-semibold bg-white p-3" type="button" data-bs-toggle="collapse" data-bs-target="#weekend-collapse-{{ $loop->index }}" aria-expanded="false" aria-controls="weekend-collapse-{{ $loop->index }}">
                                            <div class="d-flex flex-column w-100">
                                                <span>{{ $monthYear }}</span>
                                                <span class="text-muted small fw-normal mt-1">{{ $monthHolidays->count() }} weekends off</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="weekend-collapse-{{ $loop->index }}" class="accordion-collapse collapse" aria-labelledby="weekend-heading-{{ $loop->index }}" data-bs-parent="#weekendsAccordion-{{ $loop->index }}">
                                        <div class="accordion-body bg-light p-2">
                                            <ul class="list-group list-group-flush small rounded border border-light-subtle">
                                                @foreach($monthHolidays as $holiday)
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1">
                                                    <span>{{ $holiday->holiday_date->format('d D') }}</span>
                                                    <div class="tableActionButton d-inline-flex">
                                                        <form method="POST" action="{{ route('holidays.destroy', $holiday->holidayid) }}" class="d-inline" onsubmit="return confirm('Delete this weekend?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="bg04 color04" title="Delete">Delete</button>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class="text-center py-5 bg-white rounded-3 border border-dashed">
                                <div class="text-muted mb-3">
                                    <i class="far fa-calendar-times" style="font-size: 3rem;"></i>
                                </div>
                                <h6 class="fw-semibold text-dark">No System Weekends Found</h6>
                                <p class="text-muted small mb-3">Use the bulk generator to add weekends for the year.</p>
                                <button type="button" class="btn btn-primary btn-sm text-white fw-medium" data-bs-toggle="modal" data-bs-target="#bulkWeekendModal">
                                    <i class="fas fa-calendar-alt me-1"></i> Weekend Policy
                                </button>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Calendar View Tab -->
                <div id="calendar-view-tab" class="tab-pane fade" role="tabpanel">
                    @php
                        $currentYear = date('Y');
                    @endphp
                    
                    <div class="row g-3">
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $monthDate = \Carbon\Carbon::create($currentYear, $m, 1);
                                $daysInMonth = $monthDate->daysInMonth;
                                $firstDayOfWeek = $monthDate->dayOfWeek; // 0 (Sun) to 6 (Sat)
                            @endphp
                            <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                                <div class="card border border-light-subtle h-100 transition-hover">
                                    <div class="card-header bg-white border-bottom py-2">
                                        <h6 class="fw-bold mb-0 text-dark text-center">{{ $monthDate->format('F Y') }}</h6>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="d-grid" style="grid-template-columns: repeat(7, 1fr); gap: 2px;">
                                            <!-- Days of week header -->
                                            @foreach(['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $dow)
                                                <div class="text-center small text-muted fw-bold" style="font-size: 0.7rem;">{{ $dow }}</div>
                                            @endforeach
                                            
                                            <!-- Empty slots for first week -->
                                            @for($i = 0; $i < $firstDayOfWeek; $i++)
                                                <div></div>
                                            @endfor
                                            
                                            <!-- Days -->
                                            @for($day = 1; $day <= $daysInMonth; $day++)
                                                @php
                                                    $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $m, $day);
                                                    $isHoliday = isset($holidayMap[$dateStr]);
                                                    $holidayData = $isHoliday ? $holidayMap[$dateStr] : null;
                                                    
                                                    $bgClass = 'bg-transparent';
                                                    $textClass = 'text-dark';
                                                    $tooltipTitle = '';
                                                    
                                                    if ($isHoliday) {
                                                        if ($holidayData['type'] === 'weekend') {
                                                            $bgClass = 'bg-secondary-subtle border border-secondary-subtle';
                                                            $textClass = 'text-secondary fw-bold';
                                                            
                                                            $currentDate = \Carbon\Carbon::create($currentYear, $m, $day);
                                                            if ($currentDate->isSunday()) {
                                                                $tooltipTitle = 'Sunday';
                                                            } elseif ($currentDate->isSaturday()) {
                                                                $occ = (int)ceil($day / 7);
                                                                if ($occ === 1) $tooltipTitle = 'First Saturday off';
                                                                elseif ($occ === 2) $tooltipTitle = 'Second Saturday off';
                                                                elseif ($occ === 3) $tooltipTitle = 'Third Saturday off';
                                                                elseif ($occ === 4) $tooltipTitle = 'Fourth Saturday off';
                                                                elseif ($occ === 5) $tooltipTitle = 'Fifth Saturday off';
                                                                else $tooltipTitle = 'Saturday off';
                                                            } else {
                                                                $tooltipTitle = 'Weekend Off';
                                                            }
                                                        } else {
                                                            $bgClass = 'bg-primary text-white border border-primary shadow-sm';
                                                            $textClass = 'text-white fw-bold';
                                                            $tooltipTitle = $holidayData['title'] . (!empty($holidayData['is_recurring']) ? ' (Recurring)' : '');
                                                        }
                                                    }
                                                @endphp
                                                <div class="text-center rounded-1 {{ $bgClass }} {{ $textClass }} d-flex align-items-center justify-content-center" 
                                                     style="height: 24px; font-size: 0.75rem; cursor: {{ $isHoliday ? 'help' : 'default' }};"
                                                     @if($isHoliday) data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ $tooltipTitle }}" @endif>
                                                    {{ $day }}
                                                </div>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

            </div>
        </div>

