@extends('layouts.app')

@php
    $clientName = $client->business_name ?? ($client->contact_name ?? 'Client');
    $focusType = $focusType ?? 'po';
    $editDocument = $editDocument ?? null;
    $editingPo = $editDocument && $editDocument->type === 'po';
    $editingAgreement = $editDocument && $editDocument->type === 'agreement';
    $showAgreementFormState = old('type', $editingAgreement ? 'agreement' : '') === 'agreement';
@endphp

@section('header_actions')
    <a href="{{ route('clients.index') }}" class="secondary-button">Back to Clients</a>
@endsection

@section('content')
    <section class="panel-card panel-card-md">
        <div class="section-header">
            <div class="section-icon"><i class="fas fa-folder-open"></i></div>
            <h4 class="section-title">PO & Agreement Documents</h4>
        </div>
        <div class="text-sm text-muted">
            {{ $clientName }} | {{ $client->email ?? 'No email' }}
        </div>
    </section>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-6">
            <section class="panel-card panel-card-md {{ $focusType === 'po' ? 'border border-primary' : '' }}">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-file-alt"></i></div>
                    <h4 class="section-title">Purchase Order</h4>
                </div>

                <form method="POST"
                    action="{{ $editingPo ? route('clients.documents.update', ['client' => $client->clientid, 'document' => $editDocument->client_docid]) : route('clients.documents.store', $client->clientid) }}"
                    enctype="multipart/form-data" class="client-form">
                    @csrf
                    @if ($editingPo)
                        @method('PUT')
                    @endif
                    <input type="hidden" name="type" value="po">

                    <div class="form-grid grid-cols-3">
                        <div>
                            <label for="po_title">PO Title</label>
                            <input type="text" id="po_title" name="title"
                                value="{{ old('type', 'po') === 'po' ? old('title', $editingPo ? $editDocument->title : '') : '' }}"
                                maxlength="150">
                            @if (old('type', 'po') === 'po')
                                @error('title')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="po_document_number">PO Number</label>
                            <input type="text" id="po_document_number" name="document_number"
                                value="{{ old('type', 'po') === 'po' ? old('document_number', $editingPo ? $editDocument->document_number : '') : '' }}"
                                maxlength="100">
                            @if (old('type', 'po') === 'po')
                                @error('document_number')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="po_document_date">PO Date</label>
                            <input type="date" id="po_document_date" name="document_date"
                                value="{{ old('type', 'po') === 'po' ? old('document_date', $editingPo && $editDocument->document_date ? $editDocument->document_date->format('Y-m-d') : '') : '' }}">
                            @if (old('type', 'po') === 'po')
                                @error('document_date')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="po_file">{{ $editingPo ? 'Replace PO File (optional)' : 'PO File' }}</label>
                            <input type="file" id="po_file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            @if (old('type', 'po') === 'po')
                                @error('file')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="primary-button">{{ $editingPo ? 'Update PO' : 'Save PO' }}</button>
                        @if ($editingPo)
                            <a href="{{ route('clients.documents.create', ['client' => $client->clientid, 'type' => 'po']) }}"
                                class="secondary-button">Cancel Edit</a>
                        @endif
                    </div>
                </form>

                <div class="mt-3">
                    <table class="data-table documents-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>PO Number</th>
                                <th>Date</th>
                                <th>File</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($poDocuments as $document)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="document-title-cell">
                                        <span class="document-title-text"
                                            title="{{ $document->title ?: '—' }}">{{ $document->title ?: '—' }}</span>
                                    </td>
                                    <td>{{ $document->document_number ?: '—' }}</td>
                                    <td>{{ $document->document_date?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        @if ($document->file_path)
                                            <a href="{{ route('clients.documents.file', ['client' => $client->clientid, 'document' => $document->client_docid]) }}"
                                                target="_blank" class="text-action-btn view">View</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="actions-cell">
                                        <div class="table-actions">
                                            <a href="{{ route('clients.documents.create', ['client' => $client->clientid, 'type' => 'po', 'edit' => $document->client_docid]) }}"
                                                class="text-action-btn edit">Edit</a>
                                            <form method="POST"
                                                action="{{ route('clients.documents.delete', ['client' => $client->clientid, 'document' => $document->client_docid]) }}"
                                                class="inline-delete" onsubmit="return confirm('Delete this PO?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-action-btn delete">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="no-records-cell">No PO records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="panel-card panel-card-md {{ $focusType === 'agreement' ? 'border border-primary' : '' }}">
                <div class="section-header">
                    <div class="section-icon"><i class="fas fa-file-signature"></i></div>
                    <h4 class="section-title">Agreement</h4>
                </div>

                <form method="POST"
                    action="{{ $editingAgreement ? route('clients.documents.update', ['client' => $client->clientid, 'document' => $editDocument->client_docid]) : route('clients.documents.store', $client->clientid) }}"
                    enctype="multipart/form-data" class="client-form">
                    @csrf
                    @if ($editingAgreement)
                        @method('PUT')
                    @endif
                    <input type="hidden" name="type" value="agreement">

                    <div class="form-grid grid-cols-3">
                        <div>
                            <label for="agreement_title">Agreement Title</label>
                            <input type="text" id="agreement_title" name="title"
                                value="{{ $showAgreementFormState ? old('title', $editingAgreement ? $editDocument->title : '') : '' }}"
                                maxlength="150">
                            @if ($showAgreementFormState)
                                @error('title')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="agreement_document_number">Agreement Number</label>
                            <input type="text" id="agreement_document_number" name="document_number"
                                value="{{ $showAgreementFormState ? old('document_number', $editingAgreement ? $editDocument->document_number : '') : '' }}"
                                maxlength="100">
                            @if ($showAgreementFormState)
                                @error('document_number')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="agreement_document_date">Agreement Date</label>
                            <input type="date" id="agreement_document_date" name="document_date"
                                value="{{ $showAgreementFormState ? old('document_date', $editingAgreement && $editDocument->document_date ? $editDocument->document_date->format('Y-m-d') : '') : '' }}">
                            @if ($showAgreementFormState)
                                @error('document_date')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label
                                for="agreement_file">{{ $editingAgreement ? 'Replace Agreement File (optional)' : 'Agreement File' }}</label>
                            <input type="file" id="agreement_file" name="file"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            @if ($showAgreementFormState)
                                @error('file')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit"
                            class="primary-button">{{ $editingAgreement ? 'Update Agreement' : 'Save Agreement' }}</button>
                        @if ($editingAgreement)
                            <a href="{{ route('clients.documents.create', ['client' => $client->clientid, 'type' => 'agreement']) }}"
                                class="secondary-button">Cancel Edit</a>
                        @endif
                    </div>
                </form>

                <div class="mt-3">
                    <table class="data-table documents-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Agreement Number</th>
                                <th>Date</th>
                                <th>File</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agreementDocuments as $document)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="document-title-cell">
                                        <span class="document-title-text"
                                            title="{{ $document->title ?: '—' }}">{{ $document->title ?: '—' }}</span>
                                    </td>
                                    <td>{{ $document->document_number ?: '—' }}</td>
                                    <td>{{ $document->document_date?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        @if ($document->file_path)
                                            <a href="{{ route('clients.documents.file', ['client' => $client->clientid, 'document' => $document->client_docid]) }}"
                                                target="_blank" class="text-action-btn view">View</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="actions-cell">
                                        <div class="table-actions">
                                            <a href="{{ route('clients.documents.create', ['client' => $client->clientid, 'type' => 'agreement', 'edit' => $document->client_docid]) }}"
                                                class="text-action-btn edit">Edit</a>
                                            <form method="POST"
                                                action="{{ route('clients.documents.delete', ['client' => $client->clientid, 'document' => $document->client_docid]) }}"
                                                class="inline-delete" onsubmit="return confirm('Delete this agreement?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-action-btn delete">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="no-records-cell">No agreement records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <style>
        .documents-table .document-title-cell {
            max-width: 180px;
        }

        .documents-table .document-title-text {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: bottom;
        }

        .documents-table .actions-cell {
            width: 1%;
            white-space: nowrap;
        }

        .documents-table .table-actions {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        .documents-table .table-actions .inline-delete {
            display: inline-flex;
            margin: 0;
        }
    </style>
@endsection
