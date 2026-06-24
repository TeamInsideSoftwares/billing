<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientDocumentsController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('clients.index');
    }

    public function list(Client $client): JsonResponse
    {
        $accountId = $this->resolveAccountId();
        if ((string) $client->accountid !== $accountId) {
            abort(404);
        }

        return $this->documentsJsonResponse($client, 'Documents loaded.');
    }

    public function store(Request $request, Client $client)
    {
        $accountId = $this->resolveAccountId();
        if ((string) $client->accountid !== $accountId) {
            abort(404);
        }

        $validated = $request->validate([
            'type' => 'required|in:po,agreement',
            'title' => 'nullable|string|max:150',
            'document_number' => 'nullable|string|max:100',
            'document_date' => 'nullable|date',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $folder = $validated['type'] === 'po' ? 'client-documents/po' : 'client-documents/agreements';
            $filePath = $request->file('file')->store($folder, 'public');
        }

        ClientDocument::create([
            'accountid' => $accountId,
            'clientid' => $client->clientid,
            'type' => $validated['type'],
            'status' => 'active',
            'title' => $validated['title'] ?? null,
            'document_number' => $validated['document_number'] ?? null,
            'document_date' => $validated['document_date'] ?? null,
            'file_path' => $filePath,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return $this->documentsJsonResponse($client, ucfirst($validated['type']).' saved successfully.');
        }

        return redirect()
            ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $validated['type']])
            ->with('success', ucfirst($validated['type']).' saved successfully.');
    }

    public function update(Request $request, Client $client, ClientDocument $document)
    {
        $accountId = $this->resolveAccountId();
        if (
            (string) $client->accountid !== $accountId ||
            (string) $document->accountid !== $accountId ||
            (string) $document->clientid !== (string) $client->clientid
        ) {
            abort(404);
        }

        if (($document->status ?? 'active') === 'cancelled') {
            return redirect()
                ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $document->type])
                ->with('error', ucfirst($document->type).' is cancelled. Restore it before editing.');
        }

        $validated = $request->validate([
            'type' => 'required|in:po,agreement',
            'title' => 'nullable|string|max:150',
            'document_number' => 'nullable|string|max:100',
            'document_date' => 'nullable|date',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $filePath = $document->file_path;
        if ($request->hasFile('file')) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }

            $folder = $validated['type'] === 'po' ? 'client-documents/po' : 'client-documents/agreements';
            $filePath = $request->file('file')->store($folder, 'public');
        }

        $document->update([
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'document_number' => $validated['document_number'] ?? null,
            'document_date' => $validated['document_date'] ?? null,
            'file_path' => $filePath,
            'status' => 'active',
        ]);

        $client->load('documents');

        if ($request->expectsJson() || $request->ajax()) {
            return $this->documentsJsonResponse($client, ucfirst($validated['type']).' updated successfully.');
        }

        return redirect()
            ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $validated['type']])
            ->with('success', ucfirst($validated['type']).' updated successfully.');
    }

    public function cancel(Client $client, ClientDocument $document)
    {
        $accountId = $this->resolveAccountId();
        if (
            (string) $client->accountid !== $accountId ||
            (string) $document->accountid !== $accountId ||
            (string) $document->clientid !== (string) $client->clientid
        ) {
            abort(404);
        }

        $document->update(['status' => 'cancelled']);

        return redirect()
            ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $document->type])
            ->with('success', ucfirst($document->type).' cancelled successfully.');
    }

    public function restore(Client $client, ClientDocument $document)
    {
        $accountId = $this->resolveAccountId();
        if (
            (string) $client->accountid !== $accountId ||
            (string) $document->accountid !== $accountId ||
            (string) $document->clientid !== (string) $client->clientid
        ) {
            abort(404);
        }

        $document->update(['status' => 'active']);

        return redirect()
            ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $document->type])
            ->with('success', ucfirst($document->type).' restored successfully.');
    }

    public function destroy(Client $client, ClientDocument $document)
    {
        $accountId = $this->resolveAccountId();
        if (
            (string) $client->accountid !== $accountId ||
            (string) $document->accountid !== $accountId ||
            (string) $document->clientid !== (string) $client->clientid
        ) {
            abort(404);
        }

        $type = $document->type;
        $document->delete();

        $client->load('documents');

        $request = request();
        if ($request->expectsJson() || $request->ajax()) {
            return $this->documentsJsonResponse($client, ucfirst($type).' deleted successfully.');
        }

        return redirect()
            ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $type])
            ->with('success', ucfirst($type).' deleted successfully.');
    }

    private function documentsJsonResponse(Client $client, string $message): JsonResponse
    {
        $client->load(['documents' => function ($query) {
            $query->latest('document_date')
                ->latest('created_at');
        }]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'documents' => $client->documents->map(function ($d) use ($client) {
                return [
                    'client_docid' => $d->client_docid,
                    'type' => $d->type,
                    'title' => $d->title,
                    'document_number' => $d->document_number,
                    'document_date' => $d->document_date?->format('Y-m-d'),
                    'document_date_display' => $d->document_date?->format('d M Y') ?? '—',
                    'file_path' => $d->file_path,
                    'status' => $d->status ?? 'active',
                    'file_url' => $d->file_path ? route('clients.documents.file', ['client' => $client->clientid, 'document' => $d->client_docid]) : null,
                ];
            }),
        ]);
    }

    public function file(Client $client, ClientDocument $document)
    {
        $accountId = $this->resolveAccountId();
        if (
            (string) $client->accountid !== $accountId ||
            (string) $document->accountid !== $accountId ||
            (string) $document->clientid !== (string) $client->clientid
        ) {
            abort(404);
        }

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return response()->file(Storage::disk('public')->path($document->file_path));
    }
}
