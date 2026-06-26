@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    @if(auth()->user()->hasPermission('items.view'))
    <button type="button"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium"
        data-bs-toggle="modal" data-bs-target="#productCategoriesModal">
        <i class="fas fa-tags btn-icon"></i> Manage Categories
    </button>
    @endif
    @if(auth()->user()->hasPermission('items.create'))
    <a href="{{ route('services.create') }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        Create Item <i class="fas fa-arrow-right btn-icon ms-1"></i>
    </a>
    @endif
</div>
@endsection

@section('content')
<!-- Product Categories Modal -->
<div class="modal fade" id="productCategoriesModal" tabindex="-1" aria-labelledby="productCategoriesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="productCategoriesModalLabel">Manage Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <!-- Category Form -->
                <div id="add-cat-pane" class="bg-DarkLight p-2 rounded-3 mb-2">
                    <form id="catForm" method="POST" action="{{ route('product-categories.store') }}" class="mainForm">
                        @csrf
                        <input type="hidden" id="catId" name="_cat_id" value="">
                        <div id="catMethodField"></div>
                        <div class="row g-2">
                            <div class="col-12 col-md-9">
                                <input type="text" name="name" id="catName" class="form-control"
                                    placeholder="Category Name*" value="{{ old('name') }}" required maxlength="150">
                            </div>
                            <div class="col-12 col-md-3">
                                <select name="status" id="catStatus" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <textarea name="description" id="catDescription" rows="2" placeholder="Description"
                                    class="form-control">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-end mt-1">
                                    <button type="submit" id="catSubmitBtn"
                                        class="btn btn-outline-primary btn-primary text-white fw-medium text-end">
                                        Add Category <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Categories List -->
                <div id="cat-list-pane" class="position-relative bg-DarkLight p-2 rounded-3">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span id="cat-list-tab">Category List ({{ count($productCategories) }})</span>
                    </h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="70%">Category Details</th>
                                        <th class="text-end" width="30%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($productCategories as $pc)
                                    @php
                                    $words = explode(' ', trim($pc['name']));
                                    $initials = strtoupper(count($words) >= 2 ? substr($words[0], 0, 1) .
                                    substr($words[1], 0, 1) : substr($pc['name'], 0, 2));
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div
                                                    class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold flex-shrink-0">
                                                    <span class="d-block position-absolute">{{ $initials }}</span>
                                                    <div class="status-dot {{ strtolower($pc['status']) }}"
                                                        title="{{ ucfirst($pc['status']) }}"></div>
                                                </div>
                                                <div>
                                                    <span class="d-block fw-semibold">{{ $pc['name'] }}</span>
                                                    @if($pc['description'])
                                                    <span class="d-block text-dark small">{{ $pc['description']
                                                        }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                @if(auth()->user()->hasPermission('items.edit'))
                                                <button type="button" class="bg03 color03 border-0"
                                                    onclick="editCategory(this)" data-id="{{ $pc['record_id'] }}"
                                                    data-name="{{ $pc['name'] }}"
                                                    data-description="{{ $pc['description'] ?? '' }}"
                                                    data-status="{{ strtolower($pc['status']) }}">Edit</button>
                                                @endif
                                                @if(auth()->user()->hasPermission('items.delete'))
                                                <form method="POST"
                                                    action="{{ route('product-categories.destroy', $pc['record_id']) }}"
                                                    class="d-inline cat-delete-form" data-name="{{ $pc['name'] }}">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="bg04 color04 border-0">Delete</button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-4 text-muted bg-white">
                                            <i class="fas fa-folder-open text-muted mb-2 fs-2 opacity-50"></i>
                                            <p class="text-muted small mb-0">No categories yet. Create one above!</p>
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

<div class="position-relative bg-white p-2 rounded-3 shadow-sm">
    @php
    $groupedServices = collect($services)->groupBy('category_name');
    @endphp

    @if (count($services) > 0)
    <!-- Category Tabs Slider -->
    <div class="tabs-slider-container position-relative d-flex align-items-center mb-3">
        <!-- Left Navigation Arrow -->
        <button type="button" class="btn btn-sm btn-outline-primary tab-nav-btn tab-nav-prev d-none me-2">
            <i class="fas fa-chevron-left"></i>
        </button>

        <!-- Tabs Scrollable Container -->
        <div class="tabs-scroll-container flex-grow-1">
            <div class="btn-group d-flex flex-row flex-nowrap" role="group" aria-label="Category Tabs"
                style="width: max-content;">
                @foreach ($groupedServices as $categoryName => $servicesInCategory)
                <button type="button"
                    class="btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $loop->first ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold active' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }} d-inline-flex align-items-center gap-2 fw-medium category-tab-btn flex-shrink-0"
                    data-category="{{ \Illuminate\Support\Str::slug($categoryName) }}">
                    {{ $categoryName }} <span
                        class="badge rounded-pill {{ $loop->first ? 'bg-primary text-white' : 'bg-primary-subtle text-primary' }}">{{
                        count($servicesInCategory) }}</span>
                </button>
                @endforeach
            </div>
        </div>

        <!-- Right Navigation Arrow -->
        <button type="button" class="btn btn-sm btn-outline-primary tab-nav-btn tab-nav-next d-none ms-2">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    @endif

    <div class="services-tab-container">
        @forelse ($groupedServices as $categoryName => $servicesInCategory)
        <div class="category-pane {{ $loop->first ? '' : 'd-none' }}"
            data-category="{{ \Illuminate\Support\Str::slug($categoryName) }}">
            <h5 class="fw-semibold text-dark mb-2 px-1 category-title-header d-none">{{ $categoryName }}</h5>
            <div class="table-responsive card border-0 overflow-hidden shadow-sm">
                <table class="table mainTable align-middle mb-0" style="table-layout: fixed; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th width="25%">Item</th>
                            <th class="text-center" width="10%">Type</th>
                            <th class="text-end" width="10%">Costings</th>
                            <th class="text-center" width="10%">Grace</th>
                            <th width="25%">Add-ons</th>
                            <th class="text-end" width="20%">Actions</th>
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
                                        <span class="d-block position-absolute">{{ strtoupper(substr($service['name'],
                                            0, 2)) }}</span>
                                        <div class="status-dot {{ strtolower($service['status']) }}"
                                            title="{{ ucfirst($service['status']) }}"></div>
                                    </div>
                                    <div>
                                        <span class="d-block fw-semibold">{!! isset($searchTerm) && $searchTerm
                                            ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' .
                                                $searchTerm . '</mark>', $service['name'])
                                            : $service['name'] !!}</span>
                                        <span class="d-block text-dark small">Seq: <span data-seq-badge>{{
                                                $service['sequence'] }}</span></span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="service-type-badge text-capitalize">
                                    {{ $service['type'] ?? 'service' }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if(count($service['costings']) > 0)
                                <div class="d-flex flex-column align-items-end gap-1">
                                    @foreach($service['costings'] as $costing)
                                    <span class="fw-semibold text-dark">
                                        {{ rtrim(rtrim(number_format($costing['selling_price'], 2, '.', ''), '0'), '.') }}
                                        <span class="currency-code-small text-muted d-block">
                                            {{ $costing['currency_code'] }}
                                        </span>
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
                                    <span class="">
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
                                    @if(auth()->user()->hasPermission('items.edit'))
                                    <a href="{{ route('services.edit', $service['record_id']) }}"
                                        class="bg03 color03">Edit</a>
                                    @endif
                                    @if(auth()->user()->hasPermission('items.delete'))
                                    <form method="POST" action="{{ route('services.destroy', $service['record_id']) }}"
                                        class="d-inline service-delete-form" data-name="{{ $service['name'] }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bg04 color04">Delete</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
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
        const methodField = document.getElementById('catMethodField');

        form.action = 'product-categories/' + id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('catName').value = name;
        document.getElementById('catDescription').value = description;
        document.getElementById('catStatus').value = status;

        submitBtn.innerHTML = 'Update Category <i class="fas fa-arrow-right btn-icon ms-1"></i>';

        const catTabTitle = document.getElementById('catTabTitle');
        if (catTabTitle) {
            catTabTitle.innerText = 'Edit Category';
        }

        document.getElementById('catName').focus();
    }

    function resetCatForm() {
        const form = document.getElementById('catForm');
        const submitBtn = document.getElementById('catSubmitBtn');
        const methodField = document.getElementById('catMethodField');

        form.action = "{{ route('product-categories.store') }}";
        methodField.innerHTML = '';
        form.reset();

        submitBtn.innerHTML = 'Add Category <i class="fas fa-arrow-right btn-icon ms-1"></i>';

        const catTabTitle = document.getElementById('catTabTitle');
        if (catTabTitle) {
            catTabTitle.innerText = 'Add Category';
        }

        document.querySelectorAll('#add-cat-pane .text-danger.small.mt-1').forEach(function (el) { el.remove(); });
        document.querySelectorAll('#add-cat-pane .is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    }

    function buildCatRow(cat) {
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var nameTrimmed = (cat.name || '').trim();
        var words = nameTrimmed.split(/\s+/);
        var initials = (words.length >= 2 ? words[0].substring(0, 1) + words[1].substring(0, 1) : nameTrimmed.substring(0, 2)).toUpperCase();
        var statusDot = '<div class="status-dot ' + (cat.status || '').toLowerCase() + '" title="' + (cat.status || '') + '"></div>';

        var descHtml = '';
        if (cat.description) {
            descHtml = '<span class="d-block text-muted small">' + cat.description.replace(/</g, '&lt;') + '</span>';
        }

        return '<tr>' +
            '<td>' +
            '<div class="d-flex align-items-center gap-3">' +
            '<div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold flex-shrink-0">' +
            '<span class="d-block position-absolute">' + initials + '</span>' +
            statusDot +
            '</div>' +
            '<div>' +
            '<span class="d-block fw-semibold">' + (cat.name || '').replace(/</g, '&lt;') + '</span>' +
            descHtml +
            '</div>' +
            '</div>' +
            '</td>' +
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
            tbody.innerHTML = '<tr><td colspan="2" class="text-center py-4 text-muted bg-white">' +
                '<i class="fas fa-folder-open text-muted mb-2 fs-2 opacity-50"></i>' +
                '<p class="text-muted small mb-0">No categories yet. Create one above!</p>' +
                '</td></tr>';
        } else {
            tbody.innerHTML = categories.map(buildCatRow).join('');
        }
        const catListTab = document.querySelector('#cat-list-tab');
        if (catListTab) {
            catListTab.innerHTML = 'Category List (' + categories.length + ')';
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
                    if (res.status === 403) {
                        return res.json().then(function (data) { throw new Error(data.message || 'Unauthorized action.'); });
                    }
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
                    showCatToast(err.message || 'Something went wrong. Please try again.', 'danger');
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
        if (typeof window.showToast === 'function') {
            window.showToast(type || 'success', message);
        }
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

        document.querySelector('#cat-list-pane').addEventListener('submit', async function (e) {
            var deleteForm = e.target.closest('.cat-delete-form');
            if (!deleteForm) return;
            e.preventDefault();
            var name = deleteForm.dataset.name || 'this category';
            const confirmed = await window.appConfirm('Delete category ' + name + '?');
            if (!confirmed) return;

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
                    if (!res.ok) {
                        if (res.status === 403) {
                            return res.json().then(function (data) { throw new Error(data.message || 'Unauthorized action.'); });
                        }
                        throw new Error('Server error');
                    }
                    return res.json();
                })
                .then(function (data) {
                    if (data.success) {
                        refreshCatsTable(data.categories, 'list');
                        showCatToast(data.message);
                    }
                })
                .catch(function (err) {
                    showCatToast(err.message || 'Something went wrong. Please try again.', 'danger');
                });
        });

        // Category Tabs Click Handling
        const categoryTabs = document.querySelectorAll('.category-tab-btn');
        const categoryPanes = document.querySelectorAll('.category-pane');

        categoryTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const selectedCat = this.dataset.category;

                categoryTabs.forEach(t => {
                    if (t === this) {
                        t.classList.add('rounded-top', 'bg-primary-subtle', 'border-primary', 'fw-bold', 'active');
                        t.classList.remove('rounded-0', 'bg-transparent', 'border-transparent');
                        t.style.opacity = '';
                        const badge = t.querySelector('.badge');
                        if (badge) {
                            badge.className = 'badge rounded-pill bg-primary text-white';
                        }
                        // Scroll active tab into view smoothly
                        t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                    } else {
                        t.classList.remove('rounded-top', 'bg-primary-subtle', 'border-primary', 'fw-bold', 'active');
                        t.classList.add('rounded-0', 'bg-transparent', 'border-transparent');
                        t.style.opacity = '';
                        const badge = t.querySelector('.badge');
                        if (badge) {
                            badge.className = 'badge rounded-pill bg-primary-subtle text-primary';
                        }
                    }
                });

                categoryPanes.forEach(pane => {
                    const paneCat = pane.dataset.category;
                    if (paneCat === selectedCat) {
                        pane.classList.remove('d-none');
                    } else {
                        pane.classList.add('d-none');
                    }
                });
            });
        });

        // Service Deletion Confirm
        document.querySelectorAll('.service-delete-form').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const name = this.dataset.name || 'this item';
                const confirmed = await window.appConfirm('Delete ' + name + '?');
                if (confirmed) {
                    this.submit();
                }
            });
        });

        // Tabs scroll/slider logic
        const tabsContainer = document.querySelector('.tabs-scroll-container');
        const prevBtn = document.querySelector('.tab-nav-prev');
        const nextBtn = document.querySelector('.tab-nav-next');

        if (tabsContainer && prevBtn && nextBtn) {
            const updateArrows = () => {
                const scrollLeft = Math.ceil(tabsContainer.scrollLeft);
                const scrollWidth = tabsContainer.scrollWidth;
                const clientWidth = tabsContainer.clientWidth;

                // Only show arrows if container actually overflows
                if (scrollWidth > clientWidth) {
                    prevBtn.classList.remove('d-none');
                    nextBtn.classList.remove('d-none');
                    prevBtn.classList.add('d-inline-flex');
                    nextBtn.classList.add('d-inline-flex');

                    // Disable/enable arrows based on scroll position
                    if (scrollLeft <= 5) {
                        prevBtn.classList.add('opacity-50');
                        prevBtn.setAttribute('disabled', 'true');
                    } else {
                        prevBtn.classList.remove('opacity-50');
                        prevBtn.removeAttribute('disabled');
                    }

                    if (scrollLeft + clientWidth >= scrollWidth - 5) {
                        nextBtn.classList.add('opacity-50');
                        nextBtn.setAttribute('disabled', 'true');
                    } else {
                        nextBtn.classList.remove('opacity-50');
                        nextBtn.removeAttribute('disabled');
                    }
                } else {
                    prevBtn.classList.add('d-none');
                    nextBtn.classList.add('d-none');
                    prevBtn.classList.remove('d-inline-flex');
                    nextBtn.classList.remove('d-inline-flex');
                }
            };

            prevBtn.addEventListener('click', () => {
                tabsContainer.scrollBy({ left: -200, behavior: 'smooth' });
            });

            nextBtn.addEventListener('click', () => {
                tabsContainer.scrollBy({ left: 200, behavior: 'smooth' });
            });

            tabsContainer.addEventListener('scroll', updateArrows);
            window.addEventListener('resize', updateArrows);

            // Initial check + small delay to let layout settle
            updateArrows();
            setTimeout(updateArrows, 150);
        }
    });
</script>


@endsection
