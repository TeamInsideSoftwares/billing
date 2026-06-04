@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('clients.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-slate-700 font-semibold text-sm bg-white border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition-all cursor-pointer no-underline">
        <i class="fas fa-arrow-left"></i>Back to Clients
    </a>
@endsection

@section('content')
<section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
    <form method="POST" action="{{ isset($client) ? route('clients.update', $client) : route('clients.store') }}" class="client-form" enctype="multipart/form-data">
        @isset($client)
            @method('PUT')
        @endisset
        @csrf

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-3">
                <ul class="list-none m-0 p-0">
                    @foreach ($errors->all() as $error)
                        <li class="text-xs text-red-600">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <input type="hidden" name="accountid" value="{{ isset($client) ? ($client->accountid ?? auth()->user()->accountid ?? 'ACC0000001') : (auth()->user()->accountid ?? 'ACC0000001') }}">

        <!-- Basic Info -->
        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
            <div class="w-7 h-7 rounded-md bg-slate-100 text-slate-500 flex items-center justify-center text-xs shrink-0"><i class="fas fa-building"></i></div>
            <h4 class="text-sm font-semibold text-slate-800 m-0">Client Information</h4>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-4 mb-6">
            <div class="col-span-2">
                <label for="business_name" class="block text-xs font-semibold text-slate-500 mb-0.5">Client Business Name *</label>
                <input type="text" id="business_name" name="business_name" value="{{ old('business_name', $client->business_name ?? '') }}" required class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('business_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="contact_name" class="block text-xs font-semibold text-slate-500 mb-0.5">Contact Person</label>
                <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $client->contact_name ?? '') }}" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div>
                <label for="groupid" class="block text-xs font-semibold text-slate-500 mb-0.5">Group</label>
                <div class="flex items-center gap-2">
                    <select id="groupid" name="groupid" class="flex-1 bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">-- No Group --</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->groupid }}" {{ old('groupid', $client->groupid ?? '') == $group->groupid ? 'selected' : '' }}>
                                {{ $group->group_name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="inline-flex items-center justify-center px-2 py-1 text-sm border border-slate-200 rounded-md bg-white text-blue-600 hover:bg-blue-50 whitespace-nowrap cursor-pointer" data-bs-toggle="modal" data-bs-target="#groupsModal" title="Add Group"><i class="fas fa-plus"></i></button>
                </div>
                @error('groupid') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="primary_email" class="block text-xs font-semibold text-slate-500 mb-0.5">Primary Email *</label>
                <input type="email" id="primary_email" name="primary_email" value="{{ old('primary_email', $client->primary_email ?? '') }}" required placeholder="name@company.com" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                @error('primary_email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-500 mb-0.5">Secondary Emails</label>
                <input type="text" id="email" name="email" value="{{ old('email', $client->email ?? '') }}" placeholder="accounts@company.com, finance@company.com" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                <span class="text-xs text-slate-400">Optional. Use comma to add multiple emails</span>
                @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-500 mb-0.5">Phone</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $client->phone ?? '') }}" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div>
                <label for="whatsapp_number" class="block text-xs font-semibold text-slate-500 mb-0.5">WhatsApp</label>
                <input type="tel" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $client->whatsapp_number ?? '') }}" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div>
                <label for="status" class="block text-xs font-semibold text-slate-500 mb-0.5">Status</label>
                <select id="status" name="status" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="active" {{ old('status', $client->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="review" {{ old('status', $client->status ?? '') == 'review' ? 'selected' : '' }}>Review</option>
                    <option value="inactive" {{ old('status', $client->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label for="currency" class="block text-xs font-semibold text-slate-500 mb-0.5">Currency *</label>
                <select id="currency" name="currency" required class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">-- Select --</option>
                    @foreach(($currencies ?? []) as $currencyItem)
                        <option value="{{ $currencyItem->iso }}" {{ old('currency', $client->currency ?? 'INR') === $currencyItem->iso ? 'selected' : '' }}>
                            {{ $currencyItem->iso }} - {{ $currencyItem->name }}
                        </option>
                    @endforeach
                </select>
                @error('currency') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="logo" class="block text-xs font-semibold text-slate-500 mb-0.5">Logo</label>
                <input type="file" id="logo" name="logo" accept="image/*" class="w-full text-sm text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @isset($client)
                    @if($client->logo_path)
                        <div class="mt-2 w-16 h-16 rounded-lg border border-slate-200 overflow-hidden"><img src="{{ $client->logo_path }}" alt="Logo" class="object-contain w-full h-full"></div>
                    @endif
                @endisset
                @error('logo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Address -->
        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
            <div class="w-7 h-7 rounded-md bg-slate-100 text-slate-500 flex items-center justify-center text-xs shrink-0"><i class="fas fa-map-marker-alt"></i></div>
            <h4 class="text-sm font-semibold text-slate-800 m-0">Address</h4>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-4 mb-6">
            <div>
                <label for="country" class="block text-xs font-semibold text-slate-500 mb-0.5">Country</label>
                <select id="country" name="country" class="country-select w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" data-selected="{{ old('country', $client->country ?? 'India') }}">
                    <option value="">Select</option>
                </select>
            </div>
            <div>
                <label for="state" class="block text-xs font-semibold text-slate-500 mb-0.5">State *</label>
                <select id="state" name="state" required class="state-select w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" data-selected="{{ old('state', $client->state ?? '') }}">
                    <option value="">Select</option>
                </select>
                @error('state') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="city" class="block text-xs font-semibold text-slate-500 mb-0.5">City</label>
                <select id="city" name="city" class="city-select w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" data-selected="{{ old('city', $client->city ?? '') }}">
                    <option value="">Select</option>
                </select>
            </div>
            <div>
                <label for="postal_code" class="block text-xs font-semibold text-slate-500 mb-0.5">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $client->postal_code ?? '') }}" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div class="col-span-2">
                <label for="address_line_1" class="block text-xs font-semibold text-slate-500 mb-0.5">Address</label>
                <textarea id="address_line_1" name="address_line_1" rows="2" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">{{ old('address_line_1', $client->address_line_1 ?? '') }}</textarea>
            </div>
        </div>

        <!-- Billing Details -->
        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
            <div class="w-7 h-7 rounded-md bg-slate-100 text-slate-500 flex items-center justify-center text-xs shrink-0"><i class="fas fa-file-invoice-dollar"></i></div>
            <h4 class="text-sm font-semibold text-slate-800 m-0">Billing Details</h4>
        </div>

        <div class="mb-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="billing_same_as_client" name="billing_same_as_client" value="1" {{ old('billing_same_as_client') ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-slate-700">Same as client details</span>
            </label>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 mb-3">
            <div class="flex justify-between items-center">
                <label for="existing_bd_id" class="text-xs font-semibold text-slate-500">Use existing billing profile</label>
                <select id="existing_bd_id" name="existing_bd_id" class="w-auto bg-white border border-slate-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">-- New billing profile --</option>
                    @foreach($billingProfiles ?? [] as $profile)
                        <option value="{{ $profile->bd_id }}" {{ old('existing_bd_id', $client->bd_id ?? '') === $profile->bd_id ? 'selected' : '' }}>
                            {{ $profile->business_name }} ({{ $profile->bd_id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="button" id="new-billing-btn" class="text-blue-600 font-semibold text-xs hover:underline mt-3 bg-transparent border-0 cursor-pointer p-0"><i class="fas fa-plus"></i> Create new billing profile</button>
            @error('existing_bd_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div id="new-billing-fields">
            <div class="grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-4 mb-6">
                <div class="col-span-2">
                    <label for="billing_business_name" class="block text-xs font-semibold text-slate-500 mb-0.5">Billing Business Name *</label>
                    <input type="text" id="billing_business_name" name="billing_business_name" value="{{ old('billing_business_name', isset($client) ? ($client->billingDetail->business_name ?? $client->business_name) : '') }}" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @error('billing_business_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_gstin" class="block text-xs font-semibold text-slate-500 mb-0.5">GSTIN</label>
                    <input type="text" id="billing_gstin" name="billing_gstin" value="{{ old('billing_gstin', $client->billingDetail->gstin ?? '') }}"
                        maxlength="15" minlength="15" pattern="[A-Z0-9]{15}"
                        title="GSTIN must be exactly 15 characters"
                        oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
                        onblur="if(this.value && this.value.length!==15){this.setCustomValidity('GSTIN must be exactly 15 characters');this.reportValidity();}else{this.setCustomValidity('');}"
                        class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <span id="gstin_hint" class="text-xs text-slate-400">Exactly 15 characters required</span>
                    @error('billing_gstin') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_email" class="block text-xs font-semibold text-slate-500 mb-0.5">Billing Email</label>
                    <input type="email" id="billing_email" multiple name="billing_email" value="{{ old('billing_email', $client->billingDetail->billing_email ?? '') }}" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @error('billing_email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_phone" class="block text-xs font-semibold text-slate-500 mb-0.5">Billing Phone</label>
                    <input type="tel" id="billing_phone" name="billing_phone" value="{{ old('billing_phone', $client->billingDetail->billing_phone ?? '') }}" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @error('billing_phone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_country" class="block text-xs font-semibold text-slate-500 mb-0.5">Country</label>
                    <select id="billing_country" name="billing_country" class="country-select w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" data-selected="{{ old('billing_country', $client->billingDetail->country ?? 'India') }}">
                        <option value="">Select</option>
                    </select>
                </div>
                <div>
                    <label for="billing_state" class="block text-xs font-semibold text-slate-500 mb-0.5">State *</label>
                    <select id="billing_state" name="billing_state" class="state-select w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" data-selected="{{ old('billing_state', $client->billingDetail->state ?? '') }}" required>
                        <option value="">Select</option>
                    </select>
                    @error('billing_state') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_city" class="block text-xs font-semibold text-slate-500 mb-0.5">City</label>
                    <select id="billing_city" name="billing_city" class="city-select w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" data-selected="{{ old('billing_city', $client->billingDetail->city ?? '') }}">
                        <option value="">Select</option>
                    </select>
                </div>
                <div>
                    <label for="billing_postal_code" class="block text-xs font-semibold text-slate-500 mb-0.5">Postal Code</label>
                    <input type="text" id="billing_postal_code" name="billing_postal_code" value="{{ old('billing_postal_code', $client->billingDetail->postal_code ?? '') }}" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label for="billing_address_line_1" class="block text-xs font-semibold text-slate-500 mb-0.5">Billing Address</label>
                    <textarea id="billing_address_line_1" name="billing_address_line_1" rows="2" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">{{ old('billing_address_line_1', $client->billingDetail->address_line_1 ?? '') }}</textarea>
                    @error('billing_address_line_1') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-5 mt-4 border-t border-slate-200">
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white font-semibold text-sm bg-blue-600 hover:bg-blue-700 shadow-md hover:shadow-lg transition-all no-underline cursor-pointer">{{ isset($client) ? 'Update Client' : 'Create Client' }}</button>
            <a href="{{ route('clients.index') }}" class="text-blue-600 font-semibold text-sm hover:underline">Cancel</a>
        </div>
    </form>
</section>

<!-- Groups Modal -->
<div id="groupsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <!-- Backdrop overlay -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm modal-close-overlay" onclick="closeModal('groupsModal')"></div>
    
    <!-- Dialog container -->
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden z-10 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-slate-100 bg-slate-50">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-layer-group text-slate-400"></i> Manage Groups
            </h3>
            <button type="button" class="text-slate-400 hover:text-slate-600 text-lg font-bold" onclick="closeModal('groupsModal')">&times;</button>
        </div>
        <!-- Body -->
        <div class="p-6 overflow-y-auto flex-1 text-left">
            <form id="groupForm" method="POST" action="{{ route('groups.store') }}" class="space-y-4">
                @csrf
                <div id="groupMethodField"></div>
                <h6 id="groupFormTitle" class="text-sm font-bold text-slate-700 mb-2">Add New Group</h6>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Group Name *</label>
                        <input type="text" name="group_name" id="groupName" required class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Email</label>
                        <input type="email" name="email" id="groupEmail" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Address Line 1</label>
                        <input type="text" name="address_line_1" id="groupAddress1" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Address Line 2</label>
                        <input type="text" name="address_line_2" id="groupAddress2" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Country</label>
                        <select id="groupCountry" name="country" class="country-select w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" data-selected="India">
                            <option value="">Select Country</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">State</label>
                        <select id="groupState" name="state" class="state-select w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" data-selected="">
                            <option value="">Select State</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">City</label>
                        <select id="groupCity" name="city" class="city-select w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" data-selected="">
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Postal Code</label>
                        <input type="text" name="postal_code" id="groupPostalCode" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <button type="submit" id="groupSubmitBtn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold shadow-sm transition-colors">Save</button>
                    <button type="button" id="groupCancelBtn" class="text-blue-600 hover:underline text-xs font-semibold hidden" onclick="resetGroupForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const existingSelect = document.getElementById('existing_bd_id');
        const billingName = document.getElementById('billing_business_name');
        const billingGstin = document.getElementById('billing_gstin');
        const billingEmail = document.getElementById('billing_email');
        const billingPhone = document.getElementById('billing_phone');
        const billingAddress = document.getElementById('billing_address_line_1');
        const billingCity = document.getElementById('billing_city');
        const billingState = document.getElementById('billing_state');
        const billingPostal = document.getElementById('billing_postal_code');
        const billingCountry = document.getElementById('billing_country');
        const newBillingBtn = document.getElementById('new-billing-btn');
        const sameAsClientCheckbox = document.getElementById('billing_same_as_client');
        const clientBusinessName = document.getElementById('business_name');
        const clientPrimaryEmail = document.getElementById('primary_email');
        const clientSecondaryEmails = document.getElementById('email');
        const clientPhone = document.getElementById('phone');
        const clientAddress = document.getElementById('address_line_1');
        const clientCity = document.getElementById('city');
        const clientState = document.getElementById('state');
        const clientPostal = document.getElementById('postal_code');
        const clientCountry = document.getElementById('country');
        const billingProfiles = @json(($billingProfiles ?? collect())->keyBy('bd_id'));

        function loadSelectedBillingProfile() {
            const bdId = existingSelect.value;
            if (!bdId || !billingProfiles[bdId]) return;
            const profile = billingProfiles[bdId];
            billingName.value = profile.business_name || '';
            billingGstin.value = profile.gstin || '';
            billingEmail.value = profile.billing_email || '';
            billingPhone.value = profile.billing_phone || '';
            billingAddress.value = profile.address_line_1 || '';
            billingCity.value = profile.city || '';
            billingState.value = profile.state || '';
            billingPostal.value = profile.postal_code || '';
            billingCountry.value = profile.country || 'India';
        }

        function clearBillingFields() {
            billingName.value = '';
            billingGstin.value = '';
            billingEmail.value = '';
            billingPhone.value = '';
            billingAddress.value = '';
            billingCity.value = '';
            billingState.value = '';
            billingPostal.value = '';
            billingCountry.value = 'India';
        }

        function setSelectValueAndNotify(selectEl, value) {
            if (!selectEl) return;
            selectEl.value = value || '';
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        }

        async function waitForOption(selectEl, value, attempts = 20, delayMs = 100) {
            if (!selectEl || !value) return false;
            for (let i = 0; i < attempts; i++) {
                const hasOption = Array.from(selectEl.options || []).some(function (opt) {
                    return opt.value === value;
                });
                if (hasOption) return true;
                await new Promise(function (resolve) { setTimeout(resolve, delayMs); });
            }
            return false;
        }

        async function syncBillingLocationFromClient() {
            const country = clientCountry.value || 'India';
            const state = clientState.value || '';
            const city = clientCity.value || '';

            setSelectValueAndNotify(billingCountry, country);

            if (state) {
                await waitForOption(billingState, state);
                setSelectValueAndNotify(billingState, state);
            } else {
                setSelectValueAndNotify(billingState, '');
            }

            if (city) {
                await waitForOption(billingCity, city);
                billingCity.value = city;
            } else {
                billingCity.value = '';
            }
        }

        async function copyClientDetailsToBilling() {
            billingName.value = clientBusinessName.value || '';
            const emailParts = [
                (clientPrimaryEmail.value || '').trim(),
                (clientSecondaryEmails.value || '').trim(),
            ].filter(Boolean);
            billingEmail.value = Array.from(new Set(emailParts)).join(', ');
            billingPhone.value = clientPhone.value || '';
            billingAddress.value = clientAddress.value || '';
            billingPostal.value = clientPostal.value || '';
            await syncBillingLocationFromClient();
        }

        existingSelect.addEventListener('change', loadSelectedBillingProfile);
        newBillingBtn.addEventListener('click', function () { existingSelect.value = ''; clearBillingFields(); });
        sameAsClientCheckbox.addEventListener('change', function () { if (this.checked) copyClientDetailsToBilling(); });
        [clientBusinessName, clientPrimaryEmail, clientSecondaryEmails, clientPhone, clientAddress, clientPostal].forEach(function(el) {
            el.addEventListener('input', function () { if (sameAsClientCheckbox.checked) copyClientDetailsToBilling(); });
        });
        [clientCity, clientState, clientCountry].forEach(function(el) {
            el.addEventListener('change', function () { if (sameAsClientCheckbox.checked) copyClientDetailsToBilling(); });
        });
        billingName.required = true;
        loadSelectedBillingProfile();
        if (sameAsClientCheckbox.checked) copyClientDetailsToBilling();
    })();

    function selectGroup(id, name) {
        document.getElementById('groupid').value = id;
        const modal = bootstrap.Modal.getInstance(document.getElementById('groupsModal'));
        modal.hide();
    }

    function resetGroupForm() {
        const form = document.getElementById('groupForm');
        const title = document.getElementById('groupFormTitle');
        const submitBtn = document.getElementById('groupSubmitBtn');
        const cancelBtn = document.getElementById('groupCancelBtn');
        const methodField = document.getElementById('groupMethodField');
        form.action = "{{ route('groups.store') }}";
        methodField.innerHTML = '';
        form.reset();
        title.innerText = 'Add New Group';
        submitBtn.innerText = 'Save';
        cancelBtn.style.display = 'none';
    }
</script>
@endsection
