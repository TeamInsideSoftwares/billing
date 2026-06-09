<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-white py-2">
                <h5 class="modal-title fw-semibold" id="editOrderModalLabel">
                    Edit Order <span class="text-primary small" id="editOrderNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-3">
                <form id="editOrderForm" method="POST" class="mainForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="clientid" id="edit_clientid">
                    <input type="hidden" name="items_data" id="edit_items_data">
                    <div class="bg-secondary p-2 rounded-3 mb-3">
                        <div class="row g-2">
                            <div class="col-12 col-md-12">
                                <input type="text" class="form-control" id="editClientName" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-md-12">
                            <div class="mb-0">
                                <h5 class="fw-semibold small lh-sm text-primary mb-0">Add Items
                                </h5>
                            </div>
                        </div>
                        <div class="col-12 col-md-12">
                            <select id="edit_itemid" class="form-select" required>
                                <option value="">Select Items</option>
                                @php
                                $groupedServices = $services->groupBy(fn($service) => $service->category->name ?? 'No Category');
                                @endphp
                                @foreach($groupedServices as $categoryName => $categoryServices)
                                <optgroup label="{{ $categoryName }}">
                                    @foreach($categoryServices as $service)
                                    <option value="{{ $service->itemid }}"
                                        data-description="{{ $service->description ?? '' }}"
                                        data-user-wise="{{ (int) ($service->user_wise ?? 0) }}">
                                        {{ $service->name }}
                                    </option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <textarea id="edit_item_description" class="form-control" rows="2"
                                placeholder="Description"></textarea>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Qty</label>
                            <input type="number" id="edit_quantity" class="form-control" min="1" step="1" value="1">
                        </div>

                        <div class="col-12 col-md-2" id="edit_users_wrapper">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">User</label>
                            <input type="number" id="edit_no_of_users" class="form-control" min="1" step="1" value="1">
                        </div>

                        <div class="col-12 col-md-5">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Frequency</label>
                            <select id="edit_frequency" class="form-select">
                                <option value="One-Time">One-Time</option>
                                <option value="Day(s)">Day(s)</option>
                                <option value="Week(s)">Week(s)</option>
                                <option value="Month(s)">Month(s)</option>
                                <option value="Quarter(s)">Quarter(s)</option>
                                <option value="Year(s)">Year(s)</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-2" id="edit_duration_wrapper">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Dur</label>
                            <input type="number" id="edit_duration" class="form-control" min="1" step="1" value="1">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Start Date</label>
                            <div class="input-group">
                                <input type="date" id="edit_start_date" class="form-control" readonly>
                                <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Expiry</label>
                            <div class="input-group">
                                <input type="date" id="edit_end_date" class="form-control" required>
                                <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Delivery Date</label>
                            <div class="input-group">
                                <input type="date" id="edit_delivery_date" class="form-control">
                                <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Document</label>
                            <select id="edit_client_docid" class="form-select">
                                <option value="">Select</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                        <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                            Update Order <i class="fas fa-arrow-right btn-icon ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const config = window.__editModalConfig || {};
        const clientDocuments = config.clientDocuments || {};
        const todayStr = config.todayStr || '';

        if (typeof bootstrap !== 'undefined') {
            const editModalEl = document.getElementById('editOrderModal');
            if (!editModalEl) return;
            const editModal = new bootstrap.Modal(editModalEl);
            const editForm = document.getElementById('editOrderForm');
            const editClientidInput = document.getElementById('edit_clientid');
            const editClientNameInput = document.getElementById('editClientName');
            const editOrderNumberEl = document.getElementById('editOrderNumber');
            const editItemSelect = document.getElementById('edit_itemid');
            const editDescriptionInput = document.getElementById('edit_item_description');
            const editQuantityInput = document.getElementById('edit_quantity');
            const editNoOfUsersInput = document.getElementById('edit_no_of_users');
            const editFrequencyInput = document.getElementById('edit_frequency');
            const editDurationInput = document.getElementById('edit_duration');
            const editDurationWrapper = document.getElementById('edit_duration_wrapper');
            const editStartDateInput = document.getElementById('edit_start_date');
            const editEndDateInput = document.getElementById('edit_end_date');
            const editDeliveryDateInput = document.getElementById('edit_delivery_date');
            const editClientDocidSelect = document.getElementById('edit_client_docid');
            const editUsersWrapper = document.getElementById('edit_users_wrapper');
            const maxEndDate = '2099-12-31';
            let editOriginalStartDate = '';
            let editOriginalEndDate = '';
            let editScheduleDirty = false;

            function editIsSelectedUserWise() {
                const option = editItemSelect?.options?.[editItemSelect.selectedIndex];
                return String(option?.dataset?.userWise || '0') === '1';
            }

            function editSyncSelectedItemFields() {
                if (!editItemSelect) return;
                const option = editItemSelect.options[editItemSelect.selectedIndex];
                if (editDescriptionInput) {
                    editDescriptionInput.value = option?.dataset.description || '';
                }
                editToggleUsersField();
            }

            function editToggleUsersField() {
                if (!editUsersWrapper || !editNoOfUsersInput) return;
                const show = editIsSelectedUserWise();
                editNoOfUsersInput.disabled = !show;
                if (!show) {
                    editNoOfUsersInput.value = '';
                }
            }

            function editIsOneTimeFrequency() {
                const selectedFrequency = editFrequencyInput?.value || '';
                return selectedFrequency === '' || selectedFrequency === 'One-Time';
            }

            function editToggleDurationField() {
                if (!editDurationWrapper || !editDurationInput) return;
                const show = !editIsOneTimeFrequency();
                editDurationInput.disabled = !show;
                if (!show) {
                    editDurationInput.value = 1;
                }
                if (!editDurationInput.value || Number(editDurationInput.value) < 1) {
                    editDurationInput.value = 1;
                }
            }

            function editCalculateEndDate(startDate, frequency, duration) {
                if (!frequency || frequency === 'One-Time') return maxEndDate;
                if (!startDate) return maxEndDate;

                const start = new Date(startDate + 'T00:00:00');
                const end = new Date(start);
                const count = Math.max(1, parseInt(duration, 10) || 1);

                switch (frequency) {
                    case 'Day(s)': end.setDate(end.getDate() + count - 1); break;
                    case 'Week(s)': end.setDate(end.getDate() + (count * 7) - 1); break;
                    case 'Month(s)': end.setMonth(end.getMonth() + count); end.setDate(end.getDate() - 1); break;
                    case 'Quarter(s)': end.setMonth(end.getMonth() + (count * 3)); end.setDate(end.getDate() - 1); break;
                    case 'Year(s)': end.setFullYear(end.getFullYear() + count); end.setDate(end.getDate() - 1); break;
                }

                const max = new Date(maxEndDate + 'T00:00:00');
                if (end > max) return maxEndDate;

                const year = end.getFullYear();
                const month = String(end.getMonth() + 1).padStart(2, '0');
                const day = String(end.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function editRefreshEndDate() {
                editToggleDurationField();
                editScheduleDirty = true;
                if (editEndDateInput && editStartDateInput && editFrequencyInput && editDurationInput) {
                    editEndDateInput.value = editCalculateEndDate(
                        editStartDateInput.value || todayStr,
                        editFrequencyInput.value || '',
                        editDurationInput.value || 1
                    );
                }
            }

            if (editItemSelect) {
                editItemSelect.addEventListener('change', editSyncSelectedItemFields);
            }

            if (editFrequencyInput) {
                editFrequencyInput.addEventListener('change', editRefreshEndDate);
            }

            if (editDurationInput) {
                editDurationInput.addEventListener('input', editRefreshEndDate);
            }

            function editPopulateDocuments(clientId, selectedDocId) {
                if (!editClientDocidSelect) return;
                editClientDocidSelect.innerHTML = '<option value="">Select</option>';
                const docs = clientDocuments[clientId] || [];
                docs.forEach(function (doc) {
                    const option = document.createElement('option');
                    option.value = doc.client_docid;
                    option.textContent = doc.title || doc.document_number || ('Document #' + doc.client_docid);
                    if (String(doc.client_docid) === String(selectedDocId || '')) {
                        option.selected = true;
                    }
                    editClientDocidSelect.appendChild(option);
                });
            }

            document.querySelectorAll('.js-edit-order-btn').forEach((button) => {
                button.addEventListener('click', function () {
                    if (!editModal) return;
                    const orderId = this.dataset.orderId || '';
                    if (!orderId) return;

                    editForm.action = "{{ route('orders.update', ['order' => '__ORDER__']) }}".replace('__ORDER__', orderId);
                    editOrderNumberEl.textContent = '#' + (this.dataset.orderNumber || orderId);
                    editClientidInput.value = this.dataset.clientId || '';
                    editClientNameInput.value = this.dataset.clientName || '';
                    editItemSelect.value = this.dataset.itemId || '';
                    editSyncSelectedItemFields();
                    if (editDescriptionInput && !editDescriptionInput.value) {
                        editDescriptionInput.value = this.dataset.itemDescription || '';
                    }
                    editQuantityInput.value = this.dataset.quantity || 1;
                    editNoOfUsersInput.value = this.dataset.noOfUsers === undefined || this.dataset.noOfUsers === null
                        ? ''
                        : String(this.dataset.noOfUsers).trim();
                    editOriginalStartDate = this.dataset.startDate || todayStr;
                    editOriginalEndDate = this.dataset.endDate || maxEndDate;
                    editScheduleDirty = false;
                    editStartDateInput.value = editOriginalStartDate;
                    editEndDateInput.value = editOriginalEndDate;
                    editDeliveryDateInput.value = this.dataset.deliveryDate || '';
                    editPopulateDocuments(this.dataset.clientId || '', this.dataset.clientDocid || '');
                    editToggleUsersField();
                    editToggleDurationField();
                    editModal.show();
                });
            });

            if (editForm) {
                editForm.addEventListener('submit', function (event) {
                    event.preventDefault();

                    if (!editItemSelect || !editItemSelect.value) {
                        alert('Select an item first.');
                        return;
                    }

                    const payload = {
                        itemid: editItemSelect.value,
                        item_description: editDescriptionInput?.value || '',
                        quantity: Math.max(1, Math.round(Number(editQuantityInput?.value || 1))),
                        no_of_users: editNoOfUsersInput && !editNoOfUsersInput.disabled && String(editNoOfUsersInput.value || '').trim() !== ''
                            ? Math.max(1, Math.round(Number(editNoOfUsersInput.value)))
                            : null,
                        start_date: editOriginalStartDate || editStartDateInput?.value || todayStr,
                        end_date: editScheduleDirty
                            ? (editEndDateInput?.value || maxEndDate)
                            : (editOriginalEndDate || editEndDateInput?.value || maxEndDate),
                        delivery_date: editDeliveryDateInput?.value || '',
                    };

                    document.getElementById('edit_items_data').value = JSON.stringify([payload]);

                    const formData = new FormData(editForm);
                    if (editClientDocidSelect?.value) {
                        formData.set('client_docid', editClientDocidSelect.value);
                    }
                    const submitBtn = editForm.querySelector('button[type="submit"]');
                    const originalText = submitBtn?.innerHTML;
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin btn-icon"></i> Updating...';
                    }

                    fetch(editForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    })
                        .then(response => response.json().then(data => ({ ok: response.ok, data })))
                        .then(({ ok, data }) => {
                            if (ok && data.success) {
                                editModal.hide();
                                window.location.reload();
                            } else {
                                let msg = data.message || 'Failed to update order.';
                                if (data.errors) {
                                    msg += '\n' + Object.values(data.errors).flat().join('\n');
                                }
                                alert(msg);
                            }
                        })
                        .catch(() => {
                            alert('An error occurred while updating the order.');
                        })
                        .finally(() => {
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        });
                });
            }
        }
    });
</script>
