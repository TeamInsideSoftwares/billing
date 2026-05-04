@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('services.create') }}" class="primary-button">Add Item</a>
    <button class="secondary-button" data-bs-toggle="modal" data-bs-target="#productCategoriesModal"><i class="fas fa-folder icon-spaced-sm"></i>Manage Categories</button>
@endsection

@section('content')
<div class="modal fade" id="productCategoriesModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered modal-650">
        <div class="modal-content rounded-panel">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title service-modal-title"><i class="fas fa-folder icon-spaced text-muted"></i>Manage Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body service-modal-body">
                <!-- Add/Edit Category Form -->
                <form id="catForm" method="POST" action="{{ route('product-categories.store') }}" class="panel-note">
                    @csrf
                    <div id="methodField"></div>
                    <h6 id="formTitle" class="eyebrow mb-3">Add New Category</h6>
                    <div class="field-gap">
                        <label class="label-compact">Name *</label>
                        <input type="text" name="name" id="catName" value="{{ old('name') }}" required maxlength="150" class="service-input-full">
                    </div>
                    <div class="field-gap">
                        <label class="label-compact">Status</label>
                        <select name="status" id="catStatus" class="service-input-full">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="field-gap">
                        <label class="label-compact">Description</label>
                        <textarea name="description" id="catDescription" rows="1" class="service-input-full">{{ old('description') }}</textarea>
                    </div>
                    <div class="flex-center-gap">
                        <button type="submit" id="catSubmitBtn" class="primary-button small">Save Category</button>
                        <button type="button" id="catCancelBtn" class="text-link small hidden" onclick="resetCatForm()">Cancel</button>
                    </div>
                </form>

                <!-- Categories List -->
                <div class="group-list-wrap">
                    <h6 class="group-list-title">{{ count($productCategories) }} Categories</h6>
                    @forelse($productCategories as $pc)
                        <div class="group-list-item">
                            <div class="flex-fill">
                                <div class="group-list-item-head">
                                    <div class="group-list-item-icon"><i class="fas fa-folder"></i></div>
                                    <div>
                                        <strong class="group-list-item-name">{{ $pc['name'] }}</strong>
                                        @if($pc['description'])
                                            <div class="group-list-item-email">{{ $pc['description'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="group-list-item-actions">
                                <span class="small-tag">Seq: {{ $pc['sequence'] }}</span>
                                <button type="button" class="icon-action-btn edit icon-action-compact" onclick="editCategory('{{ $pc['record_id'] }}', '{{ addslashes($pc['name']) }}', '{{ addslashes($pc['description'] ?? '') }}', '{{ strtolower($pc['status']) }}')" title="Edit"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="{{ route('product-categories.destroy', $pc['record_id']) }}" class="inline" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action-btn delete icon-action-compact" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="group-list-empty">
                            <i class="fas fa-folder-open empty-state-icon-sm"></i>
                            <p class="group-list-empty-text">No categories yet. Create one above!</p>
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

    @forelse ($groupedServices as $categoryName => $servicesInCategory)
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
                            <th class="w-35">Item</th>
                            <th class="w-8">Type</th>
                            <th class="w-18">Costings</th>
                            <th class="w-14">Add-ons</th>
                            <th class="w-8">Status</th>
                            <th class="w-8">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="services-sortable-body">
                    @foreach ($servicesInCategory as $index => $service)
                        <tr draggable="true" data-service-id="{{ $service['record_id'] }}" class="service-row-draggable">
                            <td>
                                <div class="service-item-head">
                                    <div class="service-item-icon">
                                        <i class="fas fa-{{ $service['type'] === 'product' ? 'box' : 'cog' }}"></i>
                                    </div>
                                    <div>
                                        <span class="service-seq-badge" data-seq-badge>{{ $service['sequence'] }}</span>
                                        <strong class="service-item-name">{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $service['name']) : $service['name'] !!}</strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="service-type-badge">
                                    {{ $service['type'] ?? 'service' }}
                                </span>
                            </td>
                            <td>
                                @if(count($service['costings']) > 0)
                                    <div class="service-pill-wrap">
                                        @foreach($service['costings'] as $costing)
                                            <span class="service-cost-pill">
                                                {{ $costing['currency_code'] }} {{ number_format($costing['selling_price'], 0) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="service-muted">No costings</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($service['addons']) && count($service['addons']) > 0)
                                    <div class="service-pill-wrap">
                                        @foreach($service['addons'] as $addon)
                                            <span class="service-addon-pill">
                                                {{ $addon['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="service-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-pill {{ strtolower($service['status']) }}">{{ $service['status'] }}</span>
                            </td>
                            <td class="service-actions-cell">
                                <div class="service-actions-wrap">
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
    @empty
        <div class="panel-card no-padding">
            <div class="no-records-cell">
                <i class="fas fa-boxes empty-state-icon"></i>
                <p class="no-empty-state-text">No items found</p>
                <p class="small-text">Get started by adding your first product or service.</p>
            </div>
        </div>
    @endforelse
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
