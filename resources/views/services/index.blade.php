@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('services.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search services by name..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            @if (isset($searchTerm) && $searchTerm)
                <p class="eyebrow">{{ $resultCount }} services matching "{{ $searchTerm }}"</p>
                <span class="search-badge">Filtered</span>
            @else
                <p class="eyebrow">{{ count($services) }} services</p>
            @endif
            <h3>Billable services</h3>

        </div>
        <div class="button-group" style="display: flex; gap: 0.75rem;">
            <a href="{{ route('services.create') }}" class="primary-button">Add Service</a>
            <button class="primary-button" data-bs-toggle="modal" data-bs-target="#productCategoriesModal">Manage Categories</button>
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
                                                <button type="button" class="text-link small" style="margin-right: 0.5rem;"
                                                    onclick="editCategory('{{ $category['record_id'] }}', '{{ addslashes($category['name']) }}', '{{ addslashes($category['description']) }}', '{{ $category['status'] }}')">
                                                    Edit
                                                </button>
                                                <form method="POST" action="{{ route('product-categories.destroy', $category['record_id']) }}" style="display: inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-link danger small" onclick="return confirm('Delete?')">Del</button>
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

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Cost Price</th>
                    <th>Selling Price</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($services as $service)
                <tr>
                    <td>
                        <strong>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $service['name']) : $service['name'] !!}</strong>
                        <span class="eyebrow" style="display:block; margin: 0.25rem 0;">{{ $service['category_name'] }}</span>
                    </td>
                    <td>
                        <strong>{{ $service['cost_price'] }}</strong>
                    </td>
                    <td>
                        <strong>{{ $service['selling_price'] }}</strong>
                        <span style="display:block; font-size: 0.8rem; color: var(--text-muted);">Tax {{ $service['tax'] }}</span>
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($service['status']) }}">{{ $service['status'] }}</span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('services.edit', $service['record_id']) }}" class="text-link">Edit</a>
                        <form method="POST" action="{{ route('services.destroy', $service['record_id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $service['name'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-link danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endsection

