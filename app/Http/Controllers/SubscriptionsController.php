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
        $query = Subscription::with(['client', 'item']);
        $searchTerm = request('search', '');

        if ($searchTerm) {
            $query->whereHas('client', function ($q) use ($searchTerm) {
                $q->where('business_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
            })
                ->orWhereHas('item', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%');
                });
        }
        $resultCount = $query->count();

        $subscriptions = $query->latest()->take(20)->get()->map(function ($subscription) {
            return [
                'record_id' => $subscription->subscriptionid,
                'client' => $subscription->client->business_name ?? 'Client',
                'service' => $subscription->item->name ?? 'Item',
                'next_bill' => $subscription->next_billing_date?->format('d M Y'),
                'amount' => 'Rs ' . number_format($subscription->price ?? 0),
                'status' => $subscription->status ?? 'Active',
            ];
        });

        return view('subscriptions.index', [
            'title' => 'Subscription Billing',
            'subtitle' => $searchTerm
                ? $resultCount . ' subscriptions matching "' . $searchTerm . '"'
                : count($subscriptions) . ' subscriptions',
            'subscriptions' => $subscriptions,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function subscriptionsCreate(): View
    {
        return view('subscriptions.create', [
            'title' => 'New Subscription',
            'subtitle' => 'Recurring Revenue',
            'clients' => Client::all(),
            'services' => Service::where('billing_type', 'recurring')->orderBy('sequence')->orderBy('name')->get(),
        ]);
    }

    public function subscriptionsStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'itemid' => 'required|exists:items,itemid',
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
        $subscription->load('client', 'item');
        return view('subscriptions.show', [
            'title' => $subscription->item->name ?? 'Subscription',
            'subtitle' => 'Subscription Details',
            'subscription' => $subscription,
        ]);
    }

    public function subscriptionsEdit(Subscription $subscription): View
    {
        return view('subscriptions.edit', [
            'title' => 'Edit Subscription',
            'subtitle' => 'Update subscription details',
            'subscription' => $subscription,
            'clients' => Client::all(),
            'services' => Service::where('billing_type', 'recurring')->orderBy('sequence')->orderBy('name')->get(),
        ]);
    }

    public function subscriptionsUpdate(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'itemid' => 'required|exists:items,itemid',
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
