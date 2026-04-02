<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubscriptionsController extends Controller
{
    public function subscriptions(): View
    {
        $query = Subscription::with(['client', 'service']);
        $searchTerm = request('search', '');

        if ($searchTerm) {
            $query->whereHas('client', function ($q) use ($searchTerm) {
                $q->where('business_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
            })
                ->orWhereHas('service', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%');
                });
        }
        $resultCount = $query->count();

        $subscriptions = $query->latest()->take(20)->get()->map(function ($subscription) {
            return [
                'record_id' => $subscription->subscriptionid,
                'client' => $subscription->client->business_name ?? 'Client',
                'service' => $subscription->service->name ?? 'Service',
                'next_bill' => $subscription->next_billing_date?->format('d M Y'),
                'amount' => 'Rs ' . number_format($subscription->price ?? 0),
                'status' => $subscription->status ?? 'Active',
            ];
        });

        return view('subscriptions.index', [
            'title' => 'Subscriptions',
            'subscriptions' => $subscriptions,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function subscriptionsCreate(): View
    {
        return view('subscriptions.create', [
            'title' => 'New Subscription',
            'clients' => Client::all(),
            'services' => Service::where('billing_type', 'recurring')->orderBy('sequence')->orderBy('name')->get(),
        ]);
    }

    public function subscriptionsStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'serviceid' => 'required|exists:services,serviceid',
            'start_date' => 'required|date',
            'next_billing_date' => 'required|date|after:start_date',
            'price' => 'required|numeric|min:0',
            'accountid' => 'nullable|size:10',
            'status' => 'required|in:active,cancelled,expired',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

        Subscription::create($validated);

        return redirect()->route('subscriptions.index')->with('success', 'Subscription created successfully.');
    }

    public function subscriptionsShow(Subscription $subscription): View
    {
        $subscription->load('client', 'service');
        return view('subscriptions.show', [
            'title' => 'Subscription Details',
            'subscription' => $subscription,
        ]);
    }

    public function subscriptionsEdit(Subscription $subscription): View
    {
        return view('subscriptions.edit', [
            'title' => 'Edit Subscription',
            'subscription' => $subscription,
            'clients' => Client::all(),
            'services' => Service::where('billing_type', 'recurring')->orderBy('sequence')->orderBy('name')->get(),
        ]);
    }

    public function subscriptionsUpdate(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'serviceid' => 'required|exists:services,serviceid',
            'start_date' => 'required|date',
            'next_billing_date' => 'required|date|after_or_equal:start_date',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,cancelled,expired',
        ]);

        $subscription->update($validated);

        return redirect()->route('subscriptions.index')->with('success', 'Subscription updated successfully.');
    }

    public function subscriptionsDestroy(Subscription $subscription)
    {
        $subscription->delete();

        return redirect()->route('subscriptions.index')->with('success', 'Subscription deleted successfully.');
    }
}
