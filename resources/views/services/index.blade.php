@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">All Items</h3>
        @if(request('search'))
            <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                Found {{ $resultCount }} result(s) for "{{ request('search') }}"
            </p>
        @endif
    </div>
    <div>
        <a href="{{ route('services.create') }}" class="primary-button">Add Item</a>
        <button class="secondary-button" data-bs-toggle="modal" data-bs-target="#productCategoriesModal"><i class="fas fa-folder" style="margin-right: 5px;"></i>Manage Categories</button>
    </div>
</section>

<div class="modal fade" id="productCategoriesModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered" style="max-width: 650px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;"><i class="fas fa-folder" style="margin-right: 0.5rem; color: #64748b;"></i>Manage Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <!-- Add/Edit Category Form -->
                <form id="catForm" method="POST" action="{{ route('product-categories.store') }}" style="background: #f8fafc; padding: 0.75rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    @csrf
                    <div id="methodField"></div>
                    <h6 id="formTitle" class="eyebrow" style="margin-bottom: 0.75rem;">Add New Category</h6>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Name *</label>
                        <input type="text" name="name" id="catName" value="{{ old('name') }}" required maxlength="150" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Status</label>
                        <select name="status" id="catStatus" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Description</label>
                        <textarea name="description" id="catDescription" rows="1" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">{{ old('description') }}</textarea>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <button type="submit" id="catSubmitBtn" class="primary-button small">Save Category</button>
                        <button type="button" id="catCancelBtn" class="text-link small" style="display:none;" onclick="resetCatForm()">Cancel</button>
                    </div>
                </form>

                <!-- Categories List -->
                <div style="margin-top: 1rem; max-height: 220px; overflow-y: auto;">
                    <h6 style="margin: 0 0 0.5rem 0; font-size: 0.8rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">{{ count($productCategories) }} Categories</h6>
                    @forelse($productCategories as $pc)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 0.25rem; background: #f8fafc;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                    <div style="width: 24px; height: 24px; border-radius: 5px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; flex-shrink: 0;"><i class="fas fa-folder"></i></div>
                                    <div>
                                        <strong style="font-size: 0.82rem;">{{ $pc['name'] }}</strong>
                                        @if($pc['description'])
                                            <div style="font-size: 0.72rem; color: #64748b;">{{ $pc['description'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.2rem; align-items: center;">
                                <span style="font-size: 0.7rem; background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 8px; color: #64748b;">Seq: {{ $pc['sequence'] }}</span>
                                <button type="button" class="icon-action-btn edit" onclick="editCategory('{{ $pc['record_id'] }}', '{{ addslashes($pc['name']) }}', '{{ addslashes($pc['description'] ?? '') }}', '{{ strtolower($pc['status']) }}')" title="Edit" style="padding: 0.25rem 0.4rem; font-size: 0.72rem;"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="{{ route('product-categories.destroy', $pc['record_id']) }}" class="inline" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action-btn delete" title="Delete" style="padding: 0.25rem 0.4rem; font-size: 0.72rem; border: none; cursor: pointer;"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 1.25rem; color: #94a3b8;">
                            <i class="fas fa-folder-open" style="font-size: 1.25rem; margin-bottom: 0.4rem; opacity: 0.3;"></i>
                            <p style="margin: 0; font-size: 0.82rem;">No categories yet. Create one above!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="services-accordion-container">
    @php
        $groupedServices = collect($services)->groupBy('category_name');
    @endphp

    @foreach ($groupedServices as $categoryName => $servicesInCategory)
        <details class="category-accordion" open>
            <summary class="accordion-header">
                <span class="category-title">{{ $categoryName }}</span>
                <span class="service-count">{{ count($servicesInCategory) }} items</span>
                <span class="accordion-icon"></span>
            </summary>
            <div class="accordion-content">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Item</th>
                            <th style="width: 8%;">Type</th>
                            <th style="width: 18%;">Costings</th>
                            <th style="width: 14%;">Add-ons</th>
                            <th style="width: 8%;">Status</th>
                            <th style="width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="services-sortable-body">
                    @foreach ($servicesInCategory as $index => $service)
                        <tr draggable="true" data-service-id="{{ $service['record_id'] }}" style="cursor: move; vertical-align: middle;">
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                                        <i class="fas fa-{{ $service['type'] === 'product' ? 'box' : 'cog' }}"></i>
                                    </div>
                                    <div>
                                        <span style="display: inline-block; margin-right: 0.45rem; padding: 0.1rem 0.35rem; border-radius: 0.25rem; font-size: 0.7rem; background: #eef2f7; color: #475569;" data-seq-badge>{{ $service['sequence'] }}</span>
                                        <strong style="font-size: 0.9rem;">{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $service['name']) : $service['name'] !!}</strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 0.2rem 0.5rem; border: 1px solid #e5e7eb; border-radius: 0.25rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.02em; color: #374151; background: #f9fafb;">
                                    {{ $service['type'] ?? 'service' }}
                                </span>
                            </td>
                            <td>
                                @if(count($service['costings']) > 0)
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                        @foreach($service['costings'] as $costing)
                                            <span style="display: inline-block; padding: 0.15rem 0.4rem; background: #f1f5f9; color: #475569; border-radius: 0.25rem; font-size: 0.72rem;">
                                                {{ $costing['currency_code'] }} {{ number_format($costing['selling_price'], 0) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color: #94a3b8; font-size: 0.75rem;">No costings</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($service['addons']) && count($service['addons']) > 0)
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                        @foreach($service['addons'] as $addon)
                                            <span style="display: inline-block; padding: 0.15rem 0.35rem; border: 1px solid #e5e7eb; border-radius: 0.25rem; font-size: 0.7rem; color: #374151; background: #f9fafb;">
                                                {{ $addon['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color: #94a3b8; font-size: 0.75rem;">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-pill {{ strtolower($service['status']) }}">{{ $service['status'] }}</span>
                            </td>
                            <td style="vertical-align: middle; white-space: nowrap; width: 1%;">
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <a href="{{ route('services.edit', $service['record_id']) }}" class="icon-action-btn edit" title="Edit"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="{{ route('services.destroy', $service['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $service['name'] }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="icon-action-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endforeach
</div>

<script>
    (function () {
        const tbodies = document.querySelectorAll('.services-sortable-body');
        if (!tbodies.length) return;

        let draggedRow = null;

        tbodies.forEach(tbody => {
            tbody.querySelectorAll('tr[draggable="true"]').forEach((row) => {
                row.addEventListener('dragstart', function () {
                    draggedRow = this;
                    this.style.opacity = '0.5';
                });

                row.addEventListener('dragend', function () {
                    this.style.opacity = '1';
                });

                row.addEventListener('dragover', function (e) {
                    e.preventDefault();
                });

                row.addEventListener('drop', function (e) {
                    e.preventDefault();
                    if (!draggedRow || draggedRow === this) return;

                    const tbodyOfTarget = this.closest('tbody');
                    const rows = Array.from(tbodyOfTarget.children);
                    const draggedIndex = rows.indexOf(draggedRow);
                    const targetIndex = rows.indexOf(this);

                    if (draggedIndex < targetIndex) {
                        this.after(draggedRow);
                    } else {
                        this.before(draggedRow);
                    }

                    updateSequenceBadges(tbodyOfTarget);
                    saveOrder(tbodyOfTarget);
                });
            });
        });

        function updateSequenceBadges(tbody) {
            const rows = tbody.querySelectorAll('tr[data-service-id]');
            rows.forEach((row, index) => {
                const badge = row.querySelector('[data-seq-badge]');
                if (badge) {
                    badge.textContent = index + 1;
                }
            });
        }

        function saveOrder(tbody) {
            const order = Array.from(tbody.querySelectorAll('tr[data-service-id]')).map((row) => row.dataset.serviceId);
            fetch("{{ route('services.reorder') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ order }),
            });
        }
    })();

    function editCategory(id, name, description, status) {
        const form = document.getElementById('catForm');
        const title = document.getElementById('formTitle');
        const submitBtn = document.getElementById('catSubmitBtn');
        const cancelBtn = document.getElementById('catCancelBtn');
        const methodField = document.getElementById('methodField');

        form.action = 'product-categories/' + id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('catName').value = name;
        document.getElementById('catDescription').value = description;
        document.getElementById('catStatus').value = status;

        title.innerText = 'Editing Category';
        submitBtn.innerText = 'Update Now';
        cancelBtn.style.display = 'inline-block';
        document.getElementById('catName').focus();
    }

    function resetCatForm() {
        const form = document.getElementById('catForm');
        const title = document.getElementById('formTitle');
        const submitBtn = document.getElementById('catSubmitBtn');
        const cancelBtn = document.getElementById('catCancelBtn');
        const methodField = document.getElementById('methodField');

        form.action = "{{ route('product-categories.store') }}";
        methodField.innerHTML = '';
        form.reset();

        title.innerText = 'Add New Category';
        submitBtn.innerText = 'Save Category';
        cancelBtn.style.display = 'none';
    }
</script>
@endsection
