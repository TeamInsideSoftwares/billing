@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <button type="button"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium"
        data-bs-toggle="modal" data-bs-target="#productCategoriesModal">
        <i class="fas fa-folder btn-icon"></i> Manage Categories
    </button>
    <a href="{{ route('services.create') }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-plus btn-icon"></i> Add Item
    </a>
</div>
@endsection

@section('content')
<!-- Product Categories Modal -->
<div class="modal fade" id="productCategoriesModal" tabindex="-1" aria-labelledby="productCategoriesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom py-2">
                <h5 class="modal-title fw-semibold" id="productCategoriesModalLabel">
                    <i class="fas fa-folder me-2 text-primary"></i>Manage Categories
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-4">
                <ul class="nav nav-tabs mb-0" id="catModalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold rounded-0 px-3" id="add-cat-tab"
                            data-bs-toggle="tab" data-bs-target="#add-cat-pane" type="button" role="tab"
                            aria-controls="add-cat-pane" aria-selected="true">
                            <i class="fas fa-plus-circle me-1"></i><span id="catTabTitle">Add Category</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold rounded-0 px-3" id="cat-list-tab" data-bs-toggle="tab"
                            data-bs-target="#cat-list-pane" type="button" role="tab" aria-controls="cat-list-pane"
                            aria-selected="false">
                            <i class="fas fa-list me-1"></i>Category List ({{ count($productCategories) }})
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="catModalTabsContent">
                    <div class="tab-pane fade show bg-light p-3 active" id="add-cat-pane" role="tabpanel"
                        aria-labelledby="add-cat-tab">
                        <form id="catForm" method="POST" action="{{ route('product-categories.store') }}"
                            class="mainForm">
                            @csrf
                            <input type="hidden" id="catId" name="_cat_id" value="">
                            <div id="catMethodField"></div>
                            <div class="row g-2 mb-3">
                                <div class="col-12 col-md-12">
                                    <label for="catName"
                                        class="form-label small lh-sm fw-semibold text-dark mb-1">Category Name<span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="catName" class="form-control"
                                        value="{{ old('name') }}" required maxlength="150">
                                </div>
                                <div class="col-12 col-md-12">
                                    <label for="catStatus"
                                        class="form-label small lh-sm fw-semibold text-dark mb-1">Status</label>
                                    <select name="status" id="catStatus" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="catDescription"
                                        class="form-label small lh-sm fw-semibold text-dark mb-1">Description</label>
                                    <textarea name="description" id="catDescription" rows="2"
                                        class="form-control">{{ old('description') }}</textarea>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <div><button type="button" id="catCancelBtn"
                                        class="btn btn-outline-primary bg-white text-primary fw-medium d-none"
                                        onclick="resetCatForm()">
                                        <i class="fas fa-arrow-left btn-icon me-1"></i> Back to Add Category
                                    </button></div>
                                <button type="submit" id="catSubmitBtn"
                                    class="btn btn-outline-primary btn-primary text-white fw-medium text-end">
                                    Save Category <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane bg-light p-3 fade" id="cat-list-pane" role="tabpanel"
                        aria-labelledby="cat-list-tab">
                        <div class="position-relative">
                            <div class="card border-0 shadow-sm overflow-hidden">
                                <div class="table-responsive">
                                    <table class="table mainTable border align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="40%">Category</th>
                                                <th width="20%">Status</th>
                                                <th width="20%">Description</th>
                                                <th class="text-end" width="20%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($productCategories as $pc)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div
                                                            class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                                            <span class="d-block position-absolute"><i class="fas fa-folder"></i></span>
                                                        </div>
                                                        <div>
                                                            <span class="d-block fw-semibold">{{ $pc['name'] }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $pc['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}"
                                                        style="font-size: 0.65rem;">{{ ucfirst($pc['status']) }}</span>
                                                </td>
                                                <td class="text-muted small">{{ $pc['description'] ?: '—' }}</td>
                                                <td class="text-end">
                                                    <div class="tableActionButton d-inline-flex gap-1">
                                                        <button type="button" class="bg03 color03 border-0"
                                                            onclick="editCategory(this)"
                                                            data-id="{{ $pc['record_id'] }}"
                                                            data-name="{{ $pc['name'] }}"
                                                            data-description="{{ $pc['description'] ?? '' }}"
                                                            data-status="{{ strtolower($pc['status']) }}">Edit</button>
                                                        <form method="POST"
                                                            action="{{ route('product-categories.destroy', $pc['record_id']) }}"
                                                            class="d-inline cat-delete-form"
                                                            data-name="{{ $pc['name'] }}">
                                                            @csrf @method('DELETE')
                                                            <button type="submit"
                                                                class="bg04 color04 border-0">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted bg-white">
                                                    <i class="fas fa-folder-open text-muted mb-2 fs-2 opacity-50"></i>
                                                    <p class="text-muted small mb-0">No categories yet. Create one above!
                                                    </p>
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
</div>

<div class="position-relative bg-white p-3 rounded-3 shadow-sm">
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
            <div class="accordion-content p-0 border-top-0">
                <div class="table-responsive">
                    <table class="table mainTable border align-middle mb-0" style="table-layout: fixed; width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th class="w-25">Item</th>
                                <th>Type</th>
                                <th>Costings</th>
                                <th class="text-center">Grace</th>
                                <th>Add-ons</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="services-sortable-body">
                            @foreach ($servicesInCategory as $index => $service)
                            <tr draggable="true" data-service-id="{{ $service['record_id'] }}"
                                class="service-row-draggable">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div
                                            class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold flex-shrink-0">
                                            <span class="d-block position-absolute">
                                                <i
                                                    class="fas fa-{{ $service['type'] === 'product' ? 'box' : 'cog' }}"></i>
                                            </span>
                                            <div class="status-dot {{ strtolower($service['status']) }}"
                                                title="{{ ucfirst($service['status']) }}"></div>
                                        </div>
                                        <div>
                                            <span class="d-block fw-semibold">{!! isset($searchTerm) && $searchTerm
                                                ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' .
                                                    $searchTerm . '</mark>', $service['name'])
                                                : $service['name'] !!}</span>
                                            <span class="d-block text-muted small">Seq: <span data-seq-badge>{{
                                                    $service['sequence'] }}</span></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="service-type-badge text-capitalize">
                                        {{ $service['type'] ?? 'service' }}
                                    </span>
                                </td>
                                <td>
                                    @if(count($service['costings']) > 0)
                                    <div class="service-pill-wrap">
                                        @foreach($service['costings'] as $costing)
                                        <span class="service-cost-pill">
                                            {{ $costing['currency_code'] }} {{ number_format($costing['selling_price'],
                                            0) }}
                                        </span>
                                        @endforeach
                                    </div>
                                    @else
                                    <span class="service-muted">No costings</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="service-muted">{{ (int) ($service['grace_period'] ?? 0) }}
                                        day(s)</span>
                                </td>
                                <td>
                                    @if(!empty($service['addons']) && count($service['addons']) > 0)
                                    <div class="service-pill-wrap">
                                        @foreach($service['addons'] as $addon)
                                        <span class="app-badge app-badge--sm app-badge--gray">
                                            {{ $addon['name'] }}
                                        </span>
                                        @endforeach
                                    </div>
                                    @else
                                    <span class="service-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="tableActionButton d-inline-flex gap-1">
                                        <a href="{{ route('services.edit', $service['record_id']) }}"
                                            class="bg03 color03">Edit</a>
                                        <form method="POST"
                                            action="{{ route('services.destroy', $service['record_id']) }}"
                                            class="d-inline" data-name="{{ $service['name'] }}"
                                            onsubmit="return confirm('Delete ' + this.dataset.name + '?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="bg04 color04">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
        @empty
        <div class="card border-0 shadow-sm py-5 text-center text-muted">
            <div class="card-body">
                <i class="fas fa-boxes mb-3 text-secondary fs-1 opacity-50"></i>
                <p class="fw-semibold text-dark mb-1">No items found</p>
                <p class="small text-muted mb-0">Get started by adding your first product or service.</p>
            </div>
        </div>
        @endforelse
    </div>
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

    function editCategory(btn) {
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const description = btn.dataset.description || '';
        const status = btn.dataset.status;

        const form = document.getElementById('catForm');
        const submitBtn = document.getElementById('catSubmitBtn');
        const cancelBtn = document.getElementById('catCancelBtn');
        const methodField = document.getElementById('catMethodField');

        form.action = 'product-categories/' + id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('catName').value = name;
        document.getElementById('catDescription').value = description;
        document.getElementById('catStatus').value = status;

        submitBtn.innerHTML = 'Update Category <i class="fas fa-arrow-right btn-icon ms-1"></i>';
        cancelBtn.classList.remove('d-none');

        const addTabEl = document.getElementById('add-cat-tab');
        if (addTabEl) {
            document.getElementById('catTabTitle').innerText = 'Edit Category';
            addTabEl.click();
        }

        document.getElementById('catName').focus();
    }

    function resetCatForm() {
        const form = document.getElementById('catForm');
        const submitBtn = document.getElementById('catSubmitBtn');
        const cancelBtn = document.getElementById('catCancelBtn');
        const methodField = document.getElementById('catMethodField');

        form.action = "{{ route('product-categories.store') }}";
        methodField.innerHTML = '';
        form.reset();

        submitBtn.innerHTML = 'Save Category <i class="fas fa-arrow-right btn-icon ms-1"></i>';
        cancelBtn.classList.add('d-none');

        const addTabEl = document.getElementById('add-cat-tab');
        if (addTabEl) {
            document.getElementById('catTabTitle').innerText = 'Add Category';
        }

        document.querySelectorAll('#add-cat-pane .text-danger.small.mt-1').forEach(function (el) { el.remove(); });
        document.querySelectorAll('#add-cat-pane .is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    }

    function buildCatRow(cat) {
        var statusBadge = cat.status === 'active'
            ? '<span class="badge text-bg-success" style="font-size: 0.65rem;">Active</span>'
            : '<span class="badge text-bg-secondary" style="font-size: 0.65rem;">Inactive</span>';
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        return '<tr>' +
            '<td>' +
                '<div class="d-flex align-items-center gap-3">' +
                    '<div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">' +
                        '<span class="d-block position-absolute"><i class="fas fa-folder"></i></span>' +
                    '</div>' +
                    '<div>' +
                        '<span class="d-block fw-semibold">' + (cat.name || '').replace(/</g, '&lt;') + '</span>' +
                    '</div>' +
                '</div>' +
            '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td class="text-muted small">' + ((cat.description || '').replace(/</g, '&lt;') || '\u2014') + '</td>' +
            '<td class="text-end">' +
                '<div class="tableActionButton d-inline-flex gap-1">' +
                    '<button type="button" class="bg03 color03 border-0" onclick="editCategory(this)"' +
                        ' data-id="' + cat.record_id + '"' +
                        ' data-name="' + (cat.name || '').replace(/"/g, '&quot;') + '"' +
                        ' data-description="' + (cat.description || '').replace(/"/g, '&quot;') + '"' +
                        ' data-status="' + cat.status + '">Edit</button>' +
                    '<form method="POST" action="product-categories/' + cat.record_id + '" class="d-inline cat-delete-form">' +
                        '<input type="hidden" name="_token" value="' + csrf + '">' +
                        '<input type="hidden" name="_method" value="DELETE">' +
                        '<button type="submit" class="bg04 color04 border-0">Delete</button>' +
                    '</form>' +
                '</div>' +
            '</td>' +
        '</tr>';
    }

    function refreshCatsTable(categories, activeTab) {
        var tbody = document.querySelector('#cat-list-pane tbody');
        if (categories.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted bg-white">' +
                '<i class="fas fa-folder-open text-muted mb-2 fs-2 opacity-50"></i>' +
                '<p class="text-muted small mb-0">No categories yet. Create one above!</p>' +
                '</td></tr>';
        } else {
            tbody.innerHTML = categories.map(buildCatRow).join('');
        }
        document.querySelector('#cat-list-tab').innerHTML = '<i class="fas fa-list me-1"></i>Category List (' + categories.length + ')';

        if (activeTab === 'list') {
            document.getElementById('cat-list-tab').click();
        }
    }

    function handleCatFormSubmit(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        var url = form.action;
        var method = (form.querySelector('input[name="_method"]')?.value || 'POST').toUpperCase();
        if (method !== 'POST') {
            formData.set('_method', method);
        }

        document.getElementById('catSubmitBtn').disabled = true;

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
        .then(function (res) {
            if (res.status === 422) {
                return res.json().then(function (data) { throw data; });
            }
            if (!res.ok) {
                throw new Error('Server error');
            }
            return res.json();
        })
        .then(function (data) {
            if (data.success) {
                refreshCatsTable(data.categories, 'list');
                resetCatForm();
                showCatToast(data.message);
            }
        })
        .catch(function (err) {
            if (err && err.errors) {
                showCatFormErrors(err.errors);
            } else {
                showCatToast('Something went wrong. Please try again.', 'danger');
            }
        })
        .finally(function () {
            document.getElementById('catSubmitBtn').disabled = false;
        });
    }

    function showCatFormErrors(errors) {
        document.querySelectorAll('#add-cat-pane .text-danger.small.mt-1').forEach(function (el) { el.remove(); });
        document.querySelectorAll('#add-cat-pane .is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });

        Object.keys(errors).forEach(function (field) {
            var input = document.querySelector('#add-cat-pane [name="' + field + '"]');
            if (input) {
                input.classList.add('is-invalid');
                var errorDiv = document.createElement('div');
                errorDiv.className = 'text-danger small mt-1';
                errorDiv.textContent = errors[field].join(', ');
                input.parentNode.appendChild(errorDiv);
            }
        });
    }

    function showCatToast(message, type) {
        type = type || 'success';
        var container = document.getElementById('catToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'catToastContainer';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999';
            document.body.appendChild(container);
        }
        var toast = document.createElement('div');
        toast.className = 'app-toast app-toast-' + type;
        toast.innerHTML = '<span>' + message + '</span>';
        toast.onclick = function () { this.remove(); };
        container.appendChild(toast);
        setTimeout(function () { if (toast.parentNode) toast.remove(); }, 4000);
    }

    const catModalEl = document.getElementById('productCategoriesModal');
    if (catModalEl) {
        catModalEl.addEventListener('hidden.bs.modal', resetCatForm);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var catForm = document.getElementById('catForm');
        if (catForm) {
            catForm.removeEventListener('submit', handleCatFormSubmit);
            catForm.addEventListener('submit', handleCatFormSubmit);
        }

        document.querySelector('#cat-list-pane').addEventListener('submit', function (e) {
            var deleteForm = e.target.closest('.cat-delete-form');
            if (!deleteForm) return;
            e.preventDefault();
            var name = deleteForm.dataset.name || 'this category';
            if (!confirm('Delete category ' + name + '?')) return;

            var formData = new FormData(deleteForm);
            var url = deleteForm.action;

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
            .then(function (res) {
                if (!res.ok) throw new Error('Server error');
                return res.json();
            })
            .then(function (data) {
                if (data.success) {
                    refreshCatsTable(data.categories, 'list');
                    showCatToast(data.message);
                }
            })
            .catch(function () {
                showCatToast('Something went wrong. Please try again.', 'danger');
            });
        });
    });
</script>

<style>
    #productCategoriesModal .nav-tabs {
        border-bottom: 1px solid #dee2e6;
    }
    #productCategoriesModal .nav-tabs .nav-link {
        color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.6) !important;
        border: none;
        border-bottom: 2px solid transparent;
        background: transparent;
        padding: 0.5rem 1rem;
    }
    #productCategoriesModal .nav-tabs .nav-link:hover {
        color: var(--bs-primary, #0d6efd) !important;
        border-bottom-color: transparent;
    }
    #productCategoriesModal .nav-tabs .nav-link.active {
        color: var(--bs-primary, #0d6efd) !important;
        border-bottom: 2px solid var(--bs-primary, #0d6efd) !important;
        background-color: transparent !important;
    }
</style>
@endsection
