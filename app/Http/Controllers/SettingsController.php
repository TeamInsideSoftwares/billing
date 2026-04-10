<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\AccountQuotationDetail;
use App\Models\FinancialYear;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\TermsCondition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function settings(): View
    {
        $query = Setting::query();
        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('setting_key', 'like', '%' . $searchTerm . '%');
        }
        $resultCount = $query->count();
        $settings = $query->latest()->take(20)->get()->map(function ($setting) {
            return [
                'record_id' => $setting->settingid,
                'key' => $setting->setting_key,
                'value' => $setting->setting_value,
                'status' => 'Active',
            ];
        });

        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = Account::find($accountid);
        $financialYears = $account ? $account->financialYears()->orderBy('financial_year')->get() : collect();

        $editId = request('e') ? base64_decode(request('e')) : null;

        $editingSetting = null;
        if ($editId && str_starts_with($editId, 'SET')) {
            $editingSetting = Setting::find($editId);
        }

        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        $billingDetails = $account ? $account->billingDetails : collect();
        $quotationDetails = $account ? $account->quotationDetails : collect();

        $editingBillingDetail = ($editId && str_starts_with($editId, 'ABD')) ? AccountBillingDetail::where('account_bdid', $editId)->where('accountid', $accountid)->first() : ($account->billingDetails()->first());

        $editingQuotationDetail = ($editId && str_starts_with($editId, 'AQD')) ? AccountQuotationDetail::where('account_qdid', $editId)->where('accountid', $accountid)->first() : ($account->quotationDetails()->first());

        // Serial configurations from dedicated table
        $proformaSerialConfig   = \App\Models\SerialConfiguration::where('accountid', $accountid)->where('document_type', 'proforma_invoice')->first();
        $taxInvoiceSerialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)->where('document_type', 'tax_invoice')->first();
        $quotationSerialConfig  = \App\Models\SerialConfiguration::where('accountid', $accountid)->where('document_type', 'quotation')->first();

        $termsQuery = TermsCondition::query()
            ->where('accountid', $accountid)
            ->orderByRaw('COALESCE(sequence, 999999), created_at DESC');

        $billingTerms = (clone $termsQuery)
            ->where('type', 'billing')
            ->get();

        $quotationTerms = (clone $termsQuery)
            ->where('type', 'quotation')
            ->get();

        $taxes = $account ? $account->taxes()->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() : collect();

        $editingTerm = null;
        if ($editId && strlen($editId) === 6 && !str_starts_with($editId, 'SET') && !str_starts_with($editId, 'ABD') && !str_starts_with($editId, 'AQD')) {
            $editingTerm = TermsCondition::query()
                ->where('accountid', $accountid)
                ->where('tc_id', $editId)
                ->first();
        }

        $suggestedKeys = [
            'Email Settings' => [
                'MAIL_HOST' => 'SMTP Host',
                'MAIL_PORT' => 'SMTP Port',
                'MAIL_USERNAME' => 'SMTP Username',
                'MAIL_PASSWORD' => 'SMTP Password',
                'MAIL_ENCRYPTION' => 'SMTP Encryption (tls/ssl)',
                'MAIL_FROM_ADDRESS' => 'From Email Address',
                'MAIL_FROM_NAME' => 'From Name',
            ],
            'Payment Gateways' => [
                'STRIPE_KEY' => 'Stripe Publishable Key',
                'STRIPE_SECRET' => 'Stripe Secret Key',
                'RAZORPAY_KEY' => 'Razorpay Key ID',
                'RAZORPAY_SECRET' => 'Razorpay Key Secret',
                'CASHFREE_APP_ID' => 'Cashfree App ID',
                'CASHFREE_SECRET_KEY' => 'Cashfree Secret Key',
                'CASHFREE_ENVIRONMENT' => 'Cashfree Environment (production/sandbox)',
            ],
            'General' => [
                'SUPPORT_EMAIL' => 'Support Email',
                'CONTACT_PHONE' => 'Contact Phone',
                'WEBSITE_URL' => 'Website URL',
            ],
        ];

        return view('settings.index', [
            'title' => 'Settings',
            'settings' => $settings,
            'account' => $account,
            'financialYears' => $financialYears,
            'billingDetails' => $billingDetails,
            'quotationDetails' => $quotationDetails,
            'editingBillingDetail' => $editingBillingDetail,
            'editingQuotationDetail' => $editingQuotationDetail,
            'proformaSerialConfig' => $proformaSerialConfig,
            'taxInvoiceSerialConfig' => $taxInvoiceSerialConfig,
            'quotationSerialConfig' => $quotationSerialConfig,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'editingSetting' => $editingSetting,
            'currencies' => $currencies,
            'suggestedKeys' => $suggestedKeys,
            'billingTerms' => $billingTerms,
            'quotationTerms' => $quotationTerms,
            'editingTerm' => $editingTerm,
            'taxes' => $taxes,
        ]);
    }

    public function accountUpdate(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = Account::find($accountid);

        if (! $account) {
            return redirect()->back()->with('error', 'Profile not found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'legal_name' => 'nullable|string|max:150',
            'tax_number' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:150',
            'currency_code' => 'required|string|size:3|exists:currency,iso',
            'timezone' => 'required|string|max:100',
            'fy_month' => 'nullable|string|size:2',
            'fy_day' => 'nullable|string|size:2',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'logo' => 'nullable|image|max:5120',
            'allow_multi_taxation' => 'nullable|boolean',
            'have_users' => 'nullable|boolean',
            'fixed_tax_rate' => 'nullable|numeric|min:0|max:100',
            'fixed_tax_type' => 'nullable|in:GST,VAT',
        ]);

        // Handle checkbox values (unchecked = not sent, so default to false)
        $validated['allow_multi_taxation'] = $request->has('allow_multi_taxation');
        $validated['have_users'] = $request->has('have_users');

        // Set fixed tax rate and type (default to 0 and GST if not provided)
        $validated['fixed_tax_rate'] = $request->input('fixed_tax_rate', 0);
        $validated['fixed_tax_type'] = $request->input('fixed_tax_type', 'GST');

        if (!empty($validated['fy_month']) && !empty($validated['fy_day'])) {
            $validated['fy_startdate'] = $validated['fy_month'] . '-' . $validated['fy_day'];
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($account->logo_path && \Storage::exists($account->logo_path)) {
                \Storage::delete($account->logo_path);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo_path'] = 'storage/' . $path;
        }

        $account->update($validated);

        // Determine redirect based on form source
        $redirectTo = $request->input('from_tax_modal') ? '#personal' : '#personal';
        return redirect()->to(route('settings.index') . $redirectTo)->with('success', 'Profile updated successfully.');
    }

    public function fixedTaxUpdate(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = Account::find($accountid);

        if (! $account) {
            return redirect()->back()->with('error', 'Account not found.');
        }

        $validated = $request->validate([
            'fixed_tax_rate' => 'required|numeric|min:0|max:100',
            'fixed_tax_type' => 'required|in:GST,VAT',
        ]);

        $account->update($validated);

        return redirect()->to(route('settings.index') . '#personal')->with('success', 'Fixed tax rate updated successfully.');
    }

    public function serialConfigUpdate(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';

        $validated = $request->validate([
            'document_type'   => 'required|in:proforma_invoice,tax_invoice,quotation',
            'serial_configid' => 'nullable|string|size:6|exists:serial_configurations,serial_configid',
            'prefix_type'     => 'nullable|string|max:50',
            'prefix_value'    => 'nullable|string|max:50',
            'prefix_length'   => 'nullable|integer|min:0|max:20',
            'prefix_separator'=> 'nullable|string|max:10',
            'number_type'     => 'nullable|string|max:50',
            'number_value'    => 'nullable|string|max:50',
            'number_length'   => 'nullable|integer|min:0|max:20',
            'number_separator'=> 'nullable|string|max:10',
            'suffix_type'     => 'nullable|string|max:50',
            'suffix_value'    => 'nullable|string|max:50',
            'suffix_length'   => 'nullable|integer|min:0|max:20',
            'reset_on_fy'     => 'boolean',
        ]);

        $validated['reset_on_fy'] = $request->has('reset_on_fy');
        $validated = $this->normalizeSerialConfiguration($validated);

        $configId = $validated['serial_configid'] ?? null;
        unset($validated['serial_configid']);

        if ($configId) {
            \App\Models\SerialConfiguration::where('serial_configid', $configId)
                ->where('accountid', $accountid)
                ->update($validated);
        } else {
            $validated['accountid'] = $accountid;
            \App\Models\SerialConfiguration::create($validated);
        }

        return redirect()->to(route('settings.index') . '#financial-year')
            ->with('success', ucfirst(str_replace('_', ' ', $validated['document_type'])) . ' serial configuration saved.');
    }

    public function accountBillingUpdate(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = Account::find($accountid);

        if (! $account) {
            return redirect()->to(route('settings.index') . '#billing-details')->with('error', 'Account not found.');
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'account_bdid'       => 'nullable|string|size:6|exists:account_billing_details,account_bdid',
            'billing_name'       => 'nullable|string|max:150',
            'address'            => 'nullable|string',
            'city'               => 'nullable|string|max:100',
            'state'              => 'nullable|string|max:100',
            'country'            => 'nullable|string|max:100',
            'postal_code'        => 'nullable|string|max:20',
            'gstin'              => 'nullable|string|max:50',
            'tin'                => 'nullable|string|max:50',
            'authorize_signatory'=> 'nullable|string|max:255',
            'signature_upload'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'billing_from_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->to(route('settings.index') . '#billing-details')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        // Handle file upload
        if ($request->hasFile('signature_upload') && $request->file('signature_upload')->isValid()) {
            try {
                $file = $request->file('signature_upload');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('signatures', $filename, 'public');
                $validated['signature_upload'] = $path;
            } catch (\Exception $e) {
                return redirect()->to(route('settings.index') . '#billing-details')
                    ->with('error', 'Failed to upload signature: ' . $e->getMessage())
                    ->withInput();
            }
        } else {
            unset($validated['signature_upload']);
        }

        if (!empty($validated['account_bdid'])) {
            $billingDetail = AccountBillingDetail::where('account_bdid', $validated['account_bdid'])->where('accountid', $accountid)->firstOrFail();
            $billingDetail->update($validated);
        } else {
            $validated['accountid'] = $accountid;
            AccountBillingDetail::create($validated);
        }

        $redirectTo = $request->input('from_tab') === 'financial-year' ? '#financial-year' : '#billing-details';
        return redirect()->to(route('settings.index') . $redirectTo)->with('success', 'Billing details updated successfully.');
    }

    public function accountQuotationUpdate(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = Account::find($accountid);

        if (! $account) {
            return redirect()->to(route('settings.index') . '#quotation-details')->with('error', 'Account not found.');
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'account_qdid'       => 'nullable|string|size:6|exists:account_quotation_details,account_qdid',
            'quotation_name'     => 'nullable|string|max:150',
            'address'            => 'nullable|string',
            'city'               => 'nullable|string|max:100',
            'state'              => 'nullable|string|max:100',
            'country'            => 'nullable|string|max:100',
            'postal_code'        => 'nullable|string|max:20',
            'gstin'              => 'nullable|string|max:50',
            'tin'                => 'nullable|string|max:50',
            'authorize_signatory'=> 'nullable|string|max:255',
            'signature_upload'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'billing_from_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->to(route('settings.index') . '#quotation-details')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        // Handle file upload
        if ($request->hasFile('signature_upload') && $request->file('signature_upload')->isValid()) {
            try {
                $file = $request->file('signature_upload');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('signatures', $filename, 'public');
                $validated['signature_upload'] = $path;
            } catch (\Exception $e) {
                return redirect()->to(route('settings.index') . '#quotation-details')
                    ->with('error', 'Failed to upload signature: ' . $e->getMessage())
                    ->withInput();
            }
        } else {
            unset($validated['signature_upload']);
        }

        if (!empty($validated['account_qdid'])) {
            $quotationDetail = AccountQuotationDetail::where('account_qdid', $validated['account_qdid'])->where('accountid', $accountid)->firstOrFail();
            $quotationDetail->update($validated);
        } else {
            $validated['accountid'] = $accountid;
            AccountQuotationDetail::create($validated);
        }

        $redirectTo = $request->input('from_tab') === 'financial-year' ? '#financial-year' : '#quotation-details';
        return redirect()->to(route('settings.index') . $redirectTo)->with('success', 'Quotation details updated successfully.');
    }

    /**
     * Reset serial numbers if reset_on_fy is enabled
     */
    private function resetSerialNumbersIfRequired($accountid)
    {
        $account = Account::find($accountid);
        if (!$account || !$account->fy_startdate) {
            return; // No FY start date configured
        }

        // Check if it's a standard FY (like April 1 to March 31)
        $fyParts = explode('-', $account->fy_startdate);
        $fyMonth = $fyParts[0] ?? '04';
        $fyDay = $fyParts[1] ?? '01';
        
        // If FY starts on 1st of any month, it's a "full year" FY
        $isFullYearFy = ($fyDay == '01');
        
        if (!$isFullYearFy) {
            return; // Don't reset for non-standard FY
        }

        // Reset billing serial
        $billingDetail = AccountBillingDetail::where('accountid', $accountid)->first();
        if ($billingDetail && $billingDetail->reset_on_fy) {
            $billingDetail->update([
                'number_value' => null,
            ]);
        }

        // Reset quotation serial
        $quotationDetail = AccountQuotationDetail::where('accountid', $accountid)->first();
        if ($quotationDetail && $quotationDetail->reset_on_fy) {
            $quotationDetail->update([
                'number_value' => null,
            ]);
        }
    }

    public function financialYearUpdate(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = Account::find($accountid);
        if (! $account) {
            return redirect()->back()->with('error', 'Account not found.');
        }

        $validated = $request->validate([
            'year_start' => 'required|integer|min:2000|max:2100',
            'year_end' => 'required|integer|min:2000|max:2100',
        ]);

        $fyString = $validated['year_start'] . '-' . $validated['year_end'];

        // Check if we're changing the default FY
        $previousDefault = FinancialYear::where('accountid', $account->accountid)
            ->where('default', true)
            ->first();

        $fy = FinancialYear::updateOrCreate(
            [
                'accountid' => $account->accountid,
                'financial_year' => $fyString,
            ],
            [
                'default' => true,
            ]
        );

        // Reset serials if default FY changed
        $defaultChanged = !$previousDefault || $previousDefault->fy_id !== $fy->fy_id;
        
        if ($defaultChanged) {
            $this->resetSerialNumbersIfRequired($accountid);
        }

        FinancialYear::where('accountid', $account->accountid)
            ->where('fy_id', '!=', $fy->fy_id)
            ->update(['default' => false]);

        return redirect()->to(route('settings.index') . '#financial-year')->with('success', 'Financial Year "' . $fyString . '" set as default.');
    }

    public function financialYearSetDefault(FinancialYear $financialYear)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';

        if ($financialYear->accountid !== $accountid) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Check if we're changing the default FY
        $previousDefault = FinancialYear::where('accountid', $accountid)
            ->where('default', true)
            ->first();

        FinancialYear::where('accountid', $accountid)->update(['default' => false]);
        $financialYear->update(['default' => true]);

        // Reset serials if default FY changed
        if (!$previousDefault || $previousDefault->fy_id !== $financialYear->fy_id) {
            $this->resetSerialNumbersIfRequired($accountid);
        }

        return redirect()->to(route('settings.index') . '#financial-year')->with('success', 'Financial Year "' . $financialYear->financial_year . '" set as default.');
    }

    public function settingsCreate(): View
    {
        return view('settings.create', ['title' => 'New Setting']);
    }

    public function settingsStore(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
            'accountid' => 'nullable|size:10',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        Setting::create([
            'setting_key' => $validated['key'],
            'setting_value' => $validated['value'],
            'accountid' => $validated['accountid'] ?? $userAccountId,
        ]);

        return redirect()->to(route('settings.index') . '#config')->with('success', 'Setting created successfully.');
    }

    public function settingsShow(Setting $setting): View
    {
        return view('settings.show', [
            'title' => 'Setting Details',
            'setting' => $setting,
        ]);
    }

    public function settingsEdit(Setting $setting)
    {
        return redirect()->to(route('settings.index', ['e' => base64_encode($setting->settingid)]) . '#config');
    }

    public function settingsUpdate(Request $request, Setting $setting)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
        ]);

        $setting->update([
            'setting_key' => $validated['key'],
            'setting_value' => $validated['value'],
        ]);

        return redirect()->to(route('settings.index') . '#config')->with('success', 'Setting updated successfully.');
    }

    public function settingsDestroy(Setting $setting)
    {
        $setting->delete();

        return redirect()->to(route('settings.index') . '#config')->with('success', 'Setting deleted successfully.');
    }

    public function termsConditionsStore(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';

        $editId = $request->e ? base64_decode($request->e) : null;

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'tc_id' => 'nullable|string|size:6|exists:terms_conditions,tc_id',
            'type' => 'required|in:billing,quotation',
            'content' => 'required|string',
            'sequence' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->to(route('settings.index') . '#terms-conditions')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $tc_id = $validated['tc_id'] ?? $editId;

        $validated['accountid'] = $accountid;
        $validated['is_active'] = true;

        if (!empty($tc_id)) {
            // Update existing term
            $term = TermsCondition::where('tc_id', $tc_id)->where('accountid', $accountid)->firstOrFail();
            $term->update($validated);
            $message = 'T&C updated successfully.';
        } else {
            // Get the next sequence number for this type
            $maxSequence = TermsCondition::where('accountid', $accountid)
                ->where('type', $validated['type'])
                ->max('sequence');
            
            $validated['sequence'] = ($maxSequence ?? 0) + 1;
            
            TermsCondition::create($validated);
            $message = 'Term created successfully.';
        }

        // Redirect without edit_tc parameter to clear the form
        return redirect()->to(route('settings.index') . '#terms-conditions')->with('success', $message);
    }

    public function termsConditionsUpdateSequence(Request $request, TermsCondition $term)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        if ($term->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'sequence' => 'required|integer|min:1',
        ]);

        $newSequence = $validated['sequence'];
        $oldSequence = $term->sequence;

        // If sequence is changing, swap with the term that has the target sequence
        if ($newSequence != $oldSequence) {
            // Find the term that currently has the target sequence
            $targetTerm = TermsCondition::where('accountid', $accountid)
                ->where('type', $term->type)
                ->where('sequence', $newSequence)
                ->first();

            // Swap sequences
            if ($targetTerm) {
                // Temporarily set to a high number to avoid unique constraint issues
                $targetTerm->update(['sequence' => 9999]);
                $term->update(['sequence' => $newSequence]);
                $targetTerm->update(['sequence' => $oldSequence]);
            } else {
                // No term at target sequence, just update
                $term->update(['sequence' => $newSequence]);
            }
        }

        return redirect()->to(route('settings.index') . '#terms-conditions')->with('success', 'Sequence updated successfully.');
    }

    public function termsConditionsToggle(TermsCondition $term)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        if ($term->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }
        $term->update(['is_active' => !$term->is_active]);
        return redirect()->to(route('settings.index') . '#terms-conditions')->with('success', 'Term status toggled.');
    }

    public function termsConditionsDestroy(TermsCondition $term)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        if ($term->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }
        $term->delete();
        return redirect()->to(route('settings.index') . '#terms-conditions')->with('success', 'Term deleted successfully.');
    }

    public function fyPrefixUpdate(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = Account::find($accountid);
        if (!$account) {
            return redirect()->back()->with('error', 'Account not found.');
        }

        $validated = $request->validate([
            'fy_prefix_type' => 'required|in:fixed_value,value/number',
            'fy_prefix_sep' => 'required|in:-,/,none',
            'fy_prefix_value' => 'nullable|string|max:50',
            'fy_number_sep' => 'required|in:-,/,none',
        ]);

        $keys = [
            'fy_prefix_type' => $validated['fy_prefix_type'],
            'fy_prefix_sep' => $validated['fy_prefix_sep'] === 'none' ? '' : $validated['fy_prefix_sep'],
            'fy_prefix_value' => $validated['fy_prefix_value'] ?? '',
            'fy_number_sep' => $validated['fy_number_sep'] === 'none' ? '' : $validated['fy_number_sep'],
        ];

        foreach ($keys as $key => $value) {
            Setting::updateOrCreate(
                ['accountid' => $accountid, 'setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        return redirect()->to(route('settings.index') . '#financial-year')->with('success', 'FY Prefix configuration saved successfully.');
    }

    // ─── Tax Management ───

    protected function normalizeSerialConfiguration(array $validated): array
    {
        $validated['prefix_type'] = $validated['prefix_type'] ?? 'manual text';
        $validated['number_type'] = $validated['number_type'] ?? 'auto increment';
        $validated['suffix_type'] = $validated['suffix_type'] ?? 'manual text';

        if (($validated['number_type'] ?? null) === 'auto increment') {
            $start = $validated['number_value'] ?? 1;
            $validated['number_value'] = (string) max(1, (int) $start);
        }

        return $validated;
    }

    public function taxStore(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'tax_name' => 'nullable|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:GST,VAT,Sales Tax,Service Tax,Other',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return redirect()->to(route('settings.index') . '#taxes')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $validated['accountid'] = $accountid;
        $validated['is_active'] = true;

        // Generate tax name if not provided
        if (empty($validated['tax_name'])) {
            $validated['tax_name'] = $validated['type'];
        }

        $maxSequence = Tax::where('accountid', $accountid)->max('sequence') ?? 0;
        $validated['sequence'] = $maxSequence + 1;

        Tax::create($validated);

        // If coming from services create/edit page, redirect back
        if ($request->input('redirect_back')) {
            return redirect()->back()->with('success', 'Tax created successfully.');
        }

        return redirect()->to(route('settings.index') . '#taxes')->with('success', 'Tax created successfully.');
    }

    public function taxUpdate(Request $request, Tax $tax)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        if ($tax->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'tax_name' => 'nullable|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:GST,VAT,Sales Tax,Service Tax,Other',
        ]);

        $tax->update($validated);

        return redirect()->to(route('settings.index') . '#taxes')->with('success', 'Tax updated successfully.');
    }

    public function taxDestroy(Tax $tax)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        if ($tax->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }
        $tax->delete();
        return redirect()->to(route('settings.index') . '#taxes')->with('success', 'Tax deleted successfully.');
    }

    public function taxToggle(Tax $tax)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        if ($tax->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }
        $tax->update(['is_active' => !$tax->is_active]);
        return redirect()->to(route('settings.index') . '#taxes')->with('success', 'Tax status toggled.');
    }
}
