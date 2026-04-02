@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">All Services</h3>
            @if(request('search'))
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                    Found {{ $resultCount }} result(s) for "{{ request('search') }}"
                </p>
            @endif
        </div>
        <div class="button-group" style="display: flex; gap: 0.75rem;">
            <a href="{{ route('services.create') }}" class="primary-button">Add Products/Services</a>
            <button class="secondary-button" data-bs-toggle="modal" data-bs-target="#productCategoriesModal">Manage Categories</button>
        </div>
    </section>

    <!-- Product Categories Modal -->
    <div class="modal fade" id="productCategoriesModal" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--line);">
                    <h5 class="modal-title" id="catModalTitle" style="font-size: 1.1rem; font-weight: 700;">Manage Categories</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 1.25rem;">
                    <form id="catForm" method="POST" action="{{ route('product-categories.store') }}" class="mb-4" style="background: var(--bg); padding: 1rem; border-radius: 0.75rem; border: 1px solid var(--line);">
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

                    @if(isset($productCategories) && count($productCategories) > 0)
                    <div>
                        <h6 class="eyebrow" style="margin-bottom: 0.5rem; font-size: 0.7rem;">Existing ({{ $catResultCount ?? 0 }})</h6>
                        <div style="max-height: 250px; overflow-y: auto; border: 1px solid var(--line); border-radius: 0.5rem; background: white;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                                <tbody>
                                    @foreach ($productCategories ?? [] as $category)
                                        <tr style="border-bottom: 1px solid var(--line);">
                                            <td style="padding: 0.6rem 0.75rem;">
                                                <strong style="display: block; color: var(--text);">{{ $category['name'] }}</strong>
                                                <span class="status-pill {{ $category['status'] }}" style="font-size: 0.65rem; padding: 0.1rem 0.4rem; margin-top: 0.2rem; display: inline-block;">{{ ucfirst($category['status']) }}</span>
                                            </td>
                                            <td style="padding: 0.6rem 0.75rem; text-align: right; vertical-align: middle;">
                                                <button type="button" class="icon-action-btn edit" style="margin-right: 0.25rem;" title="Edit"
                                                    onclick="editCategory('{{ $category['record_id'] }}', '{{ addslashes($category['name']) }}', '{{ addslashes($category['description']) }}', '{{ $category['status'] }}')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" action="{{ route('product-categories.destroy', $category['record_id']) }}" style="display: inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="icon-action-btn delete" title="Delete" onclick="return confirm('Delete?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('open_cat_modal'))
                const catModal = new bootstrap.Modal(document.getElementById('productCategoriesModal'));
                catModal.show();
            @endif
        });

        function editCategory(id, name, description, status) {
            const form = document.getElementById('catForm');
            const title = document.getElementById('formTitle');
            const submitBtn = document.getElementById('catSubmitBtn');
            const cancelBtn = document.getElementById('catCancelBtn');
            const methodField = document.getElementById('methodField');
            
            // Fix: Use relative path to avoid 404 in subdirectory deployments
            form.action = 'product-categories/' + id;
            
            // Add PUT method
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            
            // Fill fields
            document.getElementById('catName').value = name;
            document.getElementById('catDescription').value = description;
            document.getElementById('catStatus').value = status;
            
            // Update UI
            title.innerText = 'Editing Category';
            submitBtn.innerText = 'Update Now';
            cancelBtn.style.display = 'inline-block';
            
            // Focus name field
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
                                <th style="width: 40px;">#</th>
                                <th>Type</th>
                                <th>Service</th>
                                <th>Costings</th>
                                <th>Add-ons</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="services-sortable-body">
                        @foreach ($servicesInCategory as $index => $service)
                            <tr draggable="true" data-service-id="{{ $service['record_id'] }}" style="cursor: move;">
                                <td style="text-align: center; color: var(--text-muted); font-weight: 600; width: 40px;">
                                    {{ $index + 1 }}
                                </td>
                                <td>
                                    <span class="badge" style="display: inline-block; padding: 0.25rem 0.5rem; background: {{ ($service['type'] ?? 'service') === 'product' ? '#fef3c7' : '#dbeafe' }}; color: {{ ($service['type'] ?? 'service') === 'product' ? '#92400e' : '#1e40af' }}; border-radius: 0.25rem; font-size: 0.75rem; text-transform: capitalize;">
                                        {{ $service['type'] ?? 'service' }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $service['name']) : $service['name'] !!}</strong>
                                </td>
                                <td>
                                    @if(count($service['costings']) > 0)
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                            @foreach($service['costings'] as $costing)
                                                <span class="badge" style="display: inline-block; padding: 0.25rem 0.5rem; background: #e0e7ff; color: #4338ca; border-radius: 0.25rem; font-size: 0.75rem;">
                                                    {{ $costing['currency_code'] }} {{ number_format($costing['selling_price'], 0) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="eyebrow">No costings</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($service['addons']) && count($service['addons']) > 0)
                                        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                            @foreach($service['addons'] as $addon)
                                                <div style="font-size: 0.85rem;">
                                                    <span style="color: var(--text); font-weight: 500;">{{ $addon['name'] }}</span>
                                                    @if(count($addon['costings']) > 0)
                                                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.25rem;">
                                                            @foreach($addon['costings'] as $ac)
                                                                <span class="badge" style="display: inline-block; padding: 0.2rem 0.4rem; background: #f3f4f6; color: #374151; border-radius: 0.25rem; font-size: 0.7rem;">
                                                                    {{ $ac['currency_code'] }} {{ number_format($ac['selling_price'], 0) }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: var(--muted); font-size: 0.8rem;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-pill {{ strtolower($service['status']) }}">{{ $service['status'] }}</span>
                                </td>
                                <td style="white-space: nowrap; width: 1%;">
                                    <div class="table-actions">
                                        <a href="{{ route('services.edit', $service['record_id']) }}" class="icon-action-btn edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('services.destroy', $service['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $service['name'] }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="icon-action-btn delete" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

                        saveOrder(tbodyOfTarget);
                    });
                });
            });

            function saveOrder(tbody) {
                const rows = Array.from(tbody.querySelectorAll('tr[data-service-id]'));
                const order = rows.map(row => row.dataset.serviceId);

                fetch("{{ route('services.reorder') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ order }),
                });
            }
        })();
    </script>
@endsection
