<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientContactsController extends Controller
{
    /**
     * Save or update client contact via AJAX.
     */
    public function saveAjax(Request $request, Client $client): JsonResponse
    {
        $accountId = $this->resolveAccountId();
        if ((string) $client->accountid !== $accountId) {
            abort(403);
        }

        $validated = $request->validate([
            'contactid' => 'nullable|string|exists:client_contacts,contactid',
            'name' => 'required|string|max:150',
            'designation' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'is_primary' => 'boolean',
        ]);

        $isPrimary = ! empty($validated['is_primary']);

        if ($isPrimary) {
            $client->contacts()->update(['is_primary' => false]);
        }

        if (! empty($validated['contactid'])) {
            $contact = $client->contacts()->where('contactid', $validated['contactid'])->firstOrFail();
            $contact->update([
                'name' => $validated['name'],
                'designation' => $validated['designation'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'is_primary' => $isPrimary,
            ]);
            $message = 'Contact updated successfully.';
        } else {
            if ($client->contacts()->count() === 0) {
                $isPrimary = true;
            }
            $contact = $client->contacts()->create([
                'accountid' => $accountId,
                'name' => $validated['name'],
                'designation' => $validated['designation'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'is_primary' => $isPrimary,
            ]);
            $message = 'Contact added successfully.';
        }

        $contacts = $client->contacts()->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'contacts' => $contacts,
        ]);
    }

    /**
     * Delete client contact via AJAX.
     */
    public function deleteAjax(Client $client, ClientContact $contact): JsonResponse
    {
        $accountId = $this->resolveAccountId();
        if ((string) $client->accountid !== $accountId || (string) $contact->clientid !== (string) $client->clientid) {
            abort(403);
        }

        $wasPrimary = $contact->is_primary;
        $contact->delete();

        if ($wasPrimary) {
            $firstContact = $client->contacts()->orderBy('created_at')->first();
            if ($firstContact) {
                $firstContact->update(['is_primary' => true]);
            }
        }

        $contacts = $client->contacts()->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully.',
            'contacts' => $contacts,
        ]);
    }
}
