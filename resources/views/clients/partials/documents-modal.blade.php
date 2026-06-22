<!-- Documents Modal -->
<div class="modal fade" id="documentsModal" tabindex="-1" aria-labelledby="documentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="documentsModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <!-- Document Form -->
                <div id="add-document-pane" class="bg-DarkLight p-2 rounded-3 mb-3">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span id="documentTabTitle">Add Document</span>
                    </h6>
                    <form id="documentForm" method="POST" enctype="multipart/form-data" class="mainForm">
                        @csrf
                        <input type="hidden" id="docClientId" name="clientid" value="">
                        <input type="hidden" id="docId" name="_doc_id" value="">
                        <div id="docMethodField"></div>
                        <div class="row g-2">
                            <div class="col-12 col-md-2">
                                <select id="docType" name="type" class="form-select" required>
                                    <option value="">Select type</option>
                                    <option value="po">Purchase Order</option>
                                    <option value="agreement">Agreement</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <input type="text" id="docTitle" name="title" class="form-control" placeholder="Title"
                                    maxlength="150">
                            </div>
                            <div class="col-12 col-md-2">
                                <input type="text" id="docNumber" name="document_number" class="form-control"
                                    placeholder="Doc Number" maxlength="100">
                            </div>
                            <div class="col-12 col-md-2">
                                <div class="input-group">
                                    <input type="date" id="docDate" name="document_date" class="form-control"
                                        placeholder="Doc Date" value="{{ date('Y-m-d') }}">
                                    <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                                </div>
                            </div>
                            <div class="col-12 col-md-10">
                                <div class="logo-drag-drop-zone border border-dashed rounded-3 text-center bg-white position-relative py-2"
                                    style="cursor:pointer;" id="docUploadDropZone">
                                    <input type="file" id="docFile" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                        class="position-absolute top-0 start-0 w-100 h-100 opacity-0">
                                    <div class="drop-zone-prompt d-flex align-items-center justify-content-start"
                                        id="docDropPrompt">
                                        <i class="far fa-file text-secondary mb-2 fs-4"></i>
                                        <span class="text-muted fw-medium ms-2">Drag and drop or <span
                                                class="text-primary fw-semibold">browse files</span></span>
                                    </div>
                                    <div class="drop-zone-preview d-none align-items-center justify-content-between w-100"
                                        id="docDropPreview">
                                        <div class="d-flex align-items-center gap-2">
                                            <img id="docPreviewImg" src="#" alt="Preview"
                                                class="img-fluid rounded shadow-sm d-none" width="50px">
                                            <i id="docFileIcon" class="far fa-file-alt fs-3 text-secondary d-none"></i>
                                            <span id="docFileName" class="text-muted small fw-medium"></span>
                                        </div>
                                        <button type="button" id="docRemoveBtn"
                                            class="btn btn-sm p-0 bg-transparent text-dark border-0"
                                            title="Remove File">
                                            <i class="fas fa-times fs-5 lh-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-2 mt-auto">
                                <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                                    <button type="submit" id="documentSubmitBtn"
                                        class="btn btn-outline-primary btn-primary text-white fw-medium text-end">
                                        Save Document <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Document List -->
                <div id="document-list-pane" class="position-relative bg-DarkLight p-2 rounded-3">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span id="documentListTabLabel">Document List (0)</span>
                    </h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="15%">Type</th>
                                        <th width="35%">Title</th>
                                        <th width="15%">Document Number</th>
                                        <th width="15%">Document Date</th>
                                        <th class="text-end" width="20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="documentTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted bg-white">
                                            <i class="fas fa-file-alt text-muted mb-2 fs-2 opacity-50"></i>
                                            <p class="text-muted small mb-0">Select a client to view documents.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    if (typeof window.showToast !== 'function') {
        window.showToast = function(message, type) {
            type = type || 'success';
            var container = document.getElementById('app-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'app-toast-container';
                container.className = 'app-toast-container';
                document.body.appendChild(container);
            }
            var toast = document.createElement('div');
            toast.className = 'app-toast app-toast-' + (type === 'danger' ? 'error' : type);

            var iconClass = 'fa-check-circle';
            if (type === 'error' || type === 'danger') {
                iconClass = 'fa-times-circle';
            } else if (type === 'warning') {
                iconClass = 'fa-exclamation-circle';
            } else if (type === 'info') {
                iconClass = 'fa-info-circle';
            }

            toast.innerHTML = '<i class="fas ' + iconClass + ' toast-icon"></i><span>' + message + '</span>';
            toast.onclick = function () { this.remove(); };
            container.appendChild(toast);

            setTimeout(function () {
                if (toast.parentNode) {
                    toast.classList.add('app-toast-leaving');
                    setTimeout(function () {
                        if (toast.parentNode) toast.remove();
                    }, 300);
                }
            }, 3500);
        };
    }

    // === Documents Modal Logic ===
    let currentDocClientId = '';
    let currentDocClientName = '';
    let docChanged = false;

    if (typeof window.clientsBaseUrl === 'undefined') {
        window.clientsBaseUrl = "{{ url('/') }}";
    }
    if (typeof window.documentsListUrlTemplate === 'undefined') {
        window.documentsListUrlTemplate = "{{ route('clients.documents.list', ['client' => '__CLIENT__']) }}";
    }

    function openDocumentsModal(btn, e) {
        if (e) {
            e.preventDefault();
        }

        currentDocClientId = btn.dataset.clientId || '';
        currentDocClientName = btn.dataset.clientName || '';
        document.getElementById('docClientId').value = currentDocClientId;
        document.getElementById('documentsModalLabel').textContent = currentDocClientName;
        resetDocumentForm();

        var modalEl = document.getElementById('documentsModal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        loadDocuments(currentDocClientId);
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.open-documents-modal');
        if (!trigger) return;
        openDocumentsModal(trigger, e);
    });

    function loadDocuments(clientId) {
        var tbody = document.getElementById('documentTableBody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Loading...</td></tr>';

        var documentsUrl = window.documentsListUrlTemplate.replace('__CLIENT__', clientId);

        fetch(documentsUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                if (data.success) {
                    refreshDocumentsTable(data.documents);
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Failed to load documents.</td></tr>';
                }
            })
            .catch(function (err) {
                console.error('Documents load error:', err);
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Failed to load documents.</td></tr>';
            });
    }

    function refreshDocumentsTable(documents) {
        var tbody = document.getElementById('documentTableBody');
        var listTabLabel = document.getElementById('documentListTabLabel');
        if (!documents || documents.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted bg-white">' +
                '<i class="fas fa-file-alt text-muted mb-2 fs-2 opacity-50"></i>' +
                '<p class="text-muted small mb-0">No documents yet. Add one above!</p>' +
                '</td></tr>';
        } else {
            tbody.innerHTML = documents.map(buildDocumentRow).join('');
        }
        if (listTabLabel) {
            listTabLabel.textContent = 'Document List (' + (documents ? documents.length : 0) + ')';
        }
    }

    function buildDocumentRow(doc) {
        var typeLabel = doc.type === 'po' ? 'Purchase Order' : 'Agreement';
        var typeBadge = doc.type === 'po'
            ? '<span class="border border-primary rounded-pill small lh-sm px-2 py-1 bg-primary text-white">PO</span>'
            : '<span class="border rounded-pill small lh-sm px-2 py-1 text-white" style="background-color: #346739; border-color: #346739;">Agreement</span>';
        var fileLink = doc.file_url
            ? '<a href="' + doc.file_url + '" target="_blank" class="bg01 color01 text-decoration-none">View</a>'
            : '';
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var numberHtml = doc.document_number ? doc.document_number : '';
        var titleHtml = '<span class="d-block fw-semibold text-dark">' + (doc.title || '—') + '</span>';
        return '<tr>' +
            '<td>' + typeBadge + '</td>' +
            '<td>' + titleHtml + '</td>' +
            '<td>' + numberHtml + '</td>' +
            '<td>' + doc.document_date_display + '</td>' +
            '<td class="text-end">' +
            '<div class="tableActionButton d-inline-flex gap-1">' +
            fileLink +
            '<button type="button" class="bg03 color03 border-0" onclick="editDocument(this)"' +
            ' data-id="' + doc.client_docid + '"' +
            ' data-type="' + doc.type + '"' +
            ' data-title="' + (doc.title || '').replace(/"/g, '&quot;') + '"' +
            ' data-number="' + (doc.document_number || '').replace(/"/g, '&quot;') + '"' +
            ' data-date="' + (doc.document_date || '') + '">Edit</button>' +
            '<form class="d-inline document-delete-form" onsubmit="return false;">' +
            '<input type="hidden" name="_token" value="' + csrf + '">' +
            '<input type="hidden" name="_method" value="DELETE">' +
            '<button type="button" class="bg04 color04 border-0" onclick="deleteDocument(\'' + doc.client_docid + '\', this)">Delete</button>' +
            '</form>' +
            '</div>' +
            '</td>' +
            '</tr>';
    }

    function editDocument(btn) {
        var id = btn.dataset.id;
        var type = btn.dataset.type;
        var title = btn.dataset.title;
        var number = btn.dataset.number;
        var date = btn.dataset.date;

        var form = document.getElementById('documentForm');
        var submitBtn = document.getElementById('documentSubmitBtn');
        var cancelBtn = document.getElementById('documentCancelBtn');
        var methodField = document.getElementById('docMethodField');

        form.action = window.clientsBaseUrl + '/clients/' + currentDocClientId + '/documents/' + id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('docId').value = id;
        document.getElementById('docType').value = type;
        document.getElementById('docTitle').value = title;
        document.getElementById('docNumber').value = number;
        document.getElementById('docDate').value = date;

        submitBtn.innerHTML = 'Update Document <i class="fas fa-arrow-right btn-icon ms-1"></i>';
        if (cancelBtn) {
            cancelBtn.classList.remove('d-none');
        }

        document.getElementById('documentTabTitle').innerText = 'Edit Document';
        var addTabEl = document.getElementById('add-document-tab');
        if (addTabEl) addTabEl.click();

        setTimeout(function () { document.getElementById('docTitle').focus(); }, 150);
    }

    function deleteDocument(docId, btn) {
        if (!confirm('Delete this document?')) return;
        var form = btn.closest('form');
        var formData = new FormData(form);
        formData.append('clientid', currentDocClientId);

        fetch(window.clientsBaseUrl + '/clients/' + currentDocClientId + '/documents/' + docId, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    docChanged = true;
                    refreshDocumentsTable(data.documents);
                    window.showToast(data.message);
                }
            })
            .catch(function () {
                window.showToast('Something went wrong. Please try again.', 'danger');
            });
    }

    function resetDocumentForm() {
        var form = document.getElementById('documentForm');
        var submitBtn = document.getElementById('documentSubmitBtn');
        var cancelBtn = document.getElementById('documentCancelBtn');
        var methodField = document.getElementById('docMethodField');

        form.action = window.clientsBaseUrl + '/clients/' + currentDocClientId + '/documents';
        methodField.innerHTML = '';
        document.getElementById('docId').value = '';
        document.getElementById('docType').value = '';
        document.getElementById('docTitle').value = '';
        document.getElementById('docNumber').value = '';
        document.getElementById('docDate').value = '';
        document.getElementById('docFile').value = '';
        if (window._resetDocUpload) window._resetDocUpload();

        submitBtn.innerHTML = 'Save Document <i class="fas fa-arrow-right btn-icon ms-1"></i>';
        if (cancelBtn) {
            cancelBtn.classList.add('d-none');
        }
        document.getElementById('documentTabTitle').innerText = 'Add Document';

        document.querySelectorAll('#add-document-pane .text-danger.small.mt-1').forEach(function (el) { el.remove(); });
        document.querySelectorAll('#add-document-pane .is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    }

    function handleDocumentFormSubmit(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        formData.set('clientid', currentDocClientId);
        var url = form.action;
        var method = (form.querySelector('input[name="_method"]')?.value || 'POST').toUpperCase();
        if (method !== 'POST') {
            formData.set('_method', method);
        }

        document.getElementById('documentSubmitBtn').disabled = true;

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
                if (!res.ok) throw new Error('Server error');
                return res.json();
            })
            .then(function (data) {
                if (data.success) {
                    docChanged = true;
                    refreshDocumentsTable(data.documents);
                    resetDocumentForm();
                    window.showToast(data.message);
                }
            })
            .catch(function (err) {
                if (err && err.errors) {
                    showDocFormErrors(err.errors);
                } else {
                    window.showToast('Something went wrong. Please try again.', 'danger');
                }
            })
            .finally(function () {
                document.getElementById('documentSubmitBtn').disabled = false;
            });
    }

    function showDocFormErrors(errors) {
        document.querySelectorAll('#add-document-pane .text-danger.small.mt-1').forEach(function (el) { el.remove(); });
        document.querySelectorAll('#add-document-pane .is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });

        Object.keys(errors).forEach(function (field) {
            var input = document.querySelector('#add-document-pane [name="' + field + '"]');
            if (input) {
                input.classList.add('is-invalid');
                var errorDiv = document.createElement('div');
                errorDiv.className = 'text-danger small mt-1';
                errorDiv.textContent = errors[field].join(', ');
                input.parentNode.appendChild(errorDiv);
            }
        });
    }

    (function () {
        var dropZone = document.getElementById('docUploadDropZone');
        var fileInput = document.getElementById('docFile');
        var prompt = document.getElementById('docDropPrompt');
        var preview = document.getElementById('docDropPreview');
        var previewImg = document.getElementById('docPreviewImg');
        var fileIcon = document.getElementById('docFileIcon');
        var fileName = document.getElementById('docFileName');
        var removeBtn = document.getElementById('docRemoveBtn');

        if (!dropZone || !fileInput) return;

        ['dragenter', 'dragover'].forEach(function (ev) {
            dropZone.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(function (ev) {
            dropZone.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('dragover');
            }, false);
        });

        fileInput.addEventListener('change', function () {
            var file = this.files[0];
            if (!file) {
                resetDocUpload();
                return;
            }
            fileName.textContent = file.name;
            previewImg.classList.add('d-none');
            fileIcon.classList.add('d-none');
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                fileIcon.classList.remove('d-none');
            }
            prompt.classList.add('d-none');
            preview.classList.remove('d-none');
            preview.classList.add('d-flex');
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.value = '';
                fileInput.dispatchEvent(new Event('change'));
            });
        }

        function resetDocUpload() {
            prompt.classList.remove('d-none');
            prompt.classList.add('d-flex');
            preview.classList.add('d-none');
            preview.classList.remove('d-flex');
            previewImg.classList.add('d-none');
            fileIcon.classList.add('d-none');
            fileName.textContent = '';
        }

        window._resetDocUpload = resetDocUpload;
    })();

    document.getElementById('documentsModal').addEventListener('hidden.bs.modal', function () {
        resetDocumentForm();
        currentDocClientId = '';
        currentDocClientName = '';
        if (docChanged && window.location.pathname.includes('client-dashboard')) {
            window.location.reload();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var docForm = document.getElementById('documentForm');
        if (docForm) {
            docForm.removeEventListener('submit', handleDocumentFormSubmit);
            docForm.addEventListener('submit', handleDocumentFormSubmit);
        }
    });
</script>
@endpush
