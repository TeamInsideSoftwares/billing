<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\FinancialYear;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\TermsCondition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    private function messageTemplateTypes(): array
    {
        return [
            'pi' => 'Proforma Invoice (PI)',
            'ti' => 'Tax Invoice (TI/DSI)',
            'quotation' => 'Quotation',
            'reminder' => 'Reminder Before Expiry',
            'expiry' => 'Expiry',
            'payment_received' => 'Payment Received',
        ];
    }

    public function settings(): View
    {
        $accountid = $this->resolveAccountId();
        $query = Setting::where('accountid', $accountid);
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

        $accountid = $this->resolveAccountId();

        // Optimize: Use with() to eager load relationships
        $account = Account::with(['financialYears', 'billingDetails', 'taxes'])
            ->find($accountid);

        if (!$account) {
            return redirect()->route('login')->withErrors([
                'email' => 'Account mapping not found for this login. Please contact support.',
            ]);
        }
        $hasPersistedAccount = (bool) ($account && $account->exists);

        if (!$account) {
            $account = new Account([
                'accountid' => $accountid,
                'allow_multi_taxation' => false,
                'have_users' => false,
                'fixed_tax_rate' => 0,
                'fixed_tax_type' => 'GST',
            ]);
        }

        $financialYears = $hasPersistedAccount ? $account->financialYears->sortBy('financial_year')->values() : collect();

        $editId = request('e') ? base64_decode(request('e')) : null;

        $editingSetting = null;
        if ($editId && str_starts_with($editId, 'SET')) {
            $editingSetting = Setting::where('settingid', $editId)->where('accountid', $accountid)->first();
        }

        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        $billingDetails = $hasPersistedAccount ? $account->billingDetails : collect();

        $editingBillingDetail = ($editId && str_starts_with($editId, 'ABD'))
            ? $account->billingDetails->firstWhere('account_bdid', $editId)
            : ($hasPersistedAccount ? $account->billingDetails->first() : null);

        // Default billing_name to account name if not set (sync with Business Info tab)
        if ($editingBillingDetail && empty($editingBillingDetail->billing_name)) {
            $editingBillingDetail->billing_name = $account->name;
        }

        // Serial configurations from dedicated table
        $proformaSerialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)->where('document_type', 'proforma_invoice')->first();
        $taxInvoiceSerialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)->where('document_type', 'tax_invoice')->first();
        $quotationSerialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)->where('document_type', 'quotation')->first();
        $orderSerialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)->where('document_type', 'order')->first();
        $paymentReceiptSerialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)->where('document_type', 'payment_receipt')->first();

        $termsQuery = TermsCondition::query()
            ->where('accountid', $accountid)
            ->orderByRaw('COALESCE(sequence, 999999), created_at DESC');

        $billingTerms = (clone $termsQuery)
            ->where('type', 'billing')
            ->get();

        $quotationTerms = (clone $termsQuery)
            ->where('type', 'quotation')
            ->get();

        $proformaTerms = (clone $termsQuery)
            ->where('type', 'proforma')
            ->get();

        $taxes = $hasPersistedAccount ? $account->taxes->sortByDesc('created_at')->values() : collect();
        $messageTemplates = MessageTemplate::query()
            ->where('accountid', $accountid)
            ->orderBy('template_type')
            ->orderBy('channel')
            ->orderByDesc('created_at')
            ->get();
        $messageTemplatesByType = $messageTemplates->groupBy('template_type');

        $editingTerm = null;
        if ($editId && strlen($editId) === 6 && !str_starts_with($editId, 'SET') && !str_starts_with($editId, 'ABD')) {
            $editingTerm = TermsCondition::query()
                ->where('accountid', $accountid)
                ->where('tc_id', $editId)
                ->first();
        }

        $suggestedKeys = [
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
            'subtitle' => $searchTerm ? 'Search results for "' . $searchTerm . '"' : null,
            'settings' => $settings,
            'account' => $account,
            'financialYears' => $financialYears,
            'billingDetails' => $billingDetails,
            'editingBillingDetail' => $editingBillingDetail,
            'proformaSerialConfig' => $proformaSerialConfig,
            'taxInvoiceSerialConfig' => $taxInvoiceSerialConfig,
            'quotationSerialConfig' => $quotationSerialConfig,
            'orderSerialConfig' => $orderSerialConfig,
            'paymentReceiptSerialConfig' => $paymentReceiptSerialConfig,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'editingSetting' => $editingSetting,
            'currencies' => $currencies,
            'suggestedKeys' => $suggestedKeys,
            'billingTerms' => $billingTerms,
            'quotationTerms' => $quotationTerms,
            'proformaTerms' => $proformaTerms,
            'editingTerm' => $editingTerm,
            'taxes' => $taxes,
            'messageTemplates' => $messageTemplates,
            'messageTemplatesByType' => $messageTemplatesByType,
            'messageTemplateTypes' => $this->messageTemplateTypes(),
        ]);
    }

    public function accountUpdate(Request $request)
    {
        $accountid = $this->resolveAccountId();
        $account = Account::find($accountid);

        if (!$account) {
            return redirect()->back()->with('error', 'Profile not found.');
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string',
            'phone' => 'nullable|string',
            'legal_name' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'website' => 'nullable|url',
            'currency_code' => 'required|string|size:3|exists:currency,iso',
            'timezone' => 'required|string',
            'fy_month' => 'nullable|string|size:2',
            'fy_day' => 'nullable|string|size:2',
            'address_line_1' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'required|string',
            'country' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'logo' => 'nullable|image|max:5120',
            'allow_multi_taxation' => 'nullable|boolean',
            'have_users' => 'nullable|boolean',
            'fixed_tax_rate' => 'nullable|numeric|min:0|max:100',
            'fixed_tax_type' => 'nullable|in:GST,VAT',
        ]);

        $validated['email'] = $this->normalizeCommaSeparatedEmails(
            (string) ($validated['email'] ?? ''),
            true,
            'email'
        );
        $validated['phone'] = $this->normalizeCommaSeparatedValues(
            (string) ($validated['phone'] ?? ''),
            false
        );

        // Handle checkbox values (unchecked = not sent, so default to false)
        $validated['allow_multi_taxation'] = $request->has('allow_multi_taxation');
        $validated['have_users'] = $request->has('have_users');

        // Do not overwrite fixed tax values on generic profile updates
        // unless these fields are explicitly submitted.
        if ($request->filled('fixed_tax_rate')) {
            $validated['fixed_tax_rate'] = $request->input('fixed_tax_rate');
            $validated['fixed_tax_type'] = $request->input('fixed_tax_type', 'GST');
        }

        if (!empty($validated['fy_month']) && !empty($validated['fy_day'])) {
            $validated['fy_startdate'] = $validated['fy_month'] . '-' . $validated['fy_day'];
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            try {
                $file = $request->file('logo');
                $filename = time() . '_' . $file->getClientOriginalName();
                $uploadDir = public_path('uploads/logos');
                File::ensureDirectoryExists($uploadDir, 0755, true);
                $file->move($uploadDir, $filename);
                chmod($uploadDir . '/' . $filename, 0644);
                $validated['logo_path'] = asset('uploads/logos/' . $filename);
            } catch (\Exception $e) {
                return redirect()->back()
                    ->with('error', 'Failed to upload logo: ' . $e->getMessage())
                    ->withInput();
            }
        }

        $account->update($validated);

        // Determine redirect based on form source
        $redirectTo = $request->input('from_tax_modal') ? '#personal' : '#personal';
        return redirect()->to(route('settings.index') . $redirectTo)->with('success', 'Profile updated successfully.');
    }

    public function fixedTaxUpdate(Request $request)
    {
        $accountid = $this->resolveAccountId();
        $account = Account::find($accountid);

        if (!$account) {
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
        $accountid = $this->resolveAccountId();

        $validated = $request->validate([
            'document_type' => 'required|in:proforma_invoice,tax_invoice,quotation,order,payment_receipt',
            'serial_configid' => 'nullable|string|size:6|exists:serial_configurations,serial_configid',
            'prefix_show' => 'boolean',
            'number_show' => 'boolean',
            'suffix_show' => 'boolean',
            'prefix_type' => 'nullable|string',
            'prefix_value' => 'nullable|string',
            'prefix_length' => 'nullable|integer|min:0|max:20',
            'prefix_separator' => 'nullable|string',
            'number_type' => 'nullable|string',
            'number_value' => 'nullable|string',
            'number_length' => 'nullable|integer|min:0|max:20',
            'number_separator' => 'nullable|string',
            'suffix_type' => 'nullable|string',
            'suffix_value' => 'nullable|string',
            'suffix_length' => 'nullable|integer|min:0|max:20',
            'reset_on_fy' => 'boolean',
        ]);

        // Normalize checkbox values (1/0)
        $validated['prefix_show'] = $request->has('prefix_show') ? 1 : 0;
        $validated['number_show'] = $request->has('number_show') ? 1 : 0;
        $validated['suffix_show'] = $request->has('suffix_show') ? 1 : 0;

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
        $accountid = $this->resolveAccountId();
        $account = Account::find($accountid);

        if (!$account) {
            return redirect()->to(route('settings.index') . '#billing-details')->with('error', 'Account not found.');
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'account_bdid' => 'nullable|string|size:6|exists:account_billing_details,account_bdid',
            'billing_name' => 'nullable|string',
            'address' => 'nullable|string',
            'billing_city' => 'nullable|string',
            'billing_state' => 'required|string',
            'billing_country' => 'nullable|string',
            'billing_postal_code' => 'nullable|string',
            'gstin' => 'nullable|string|size:15',
            'tin' => 'nullable|string',
            'authorize_signatory' => 'nullable|string',
            'signature_upload' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'billing_from_email' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->to(route('settings.index') . '#billing-details')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $validated['city'] = $validated['billing_city'] ?? null;
        $validated['state'] = $validated['billing_state'] ?? null;
        $validated['country'] = $validated['billing_country'] ?? null;
        $validated['postal_code'] = $validated['billing_postal_code'] ?? null;
        unset($validated['billing_city'], $validated['billing_state'], $validated['billing_country'], $validated['billing_postal_code']);
        $validated['billing_from_email'] = $this->normalizeCommaSeparatedEmails(
            (string) ($validated['billing_from_email'] ?? ''),
            false,
            'billing_from_email'
        );

        // Handle file upload
        if ($request->hasFile('signature_upload') && $request->file('signature_upload')->isValid()) {
            try {
                $file = $request->file('signature_upload');
                $filename = time() . '_' . $file->getClientOriginalName();
                $uploadDir = public_path('uploads/signatures');
                File::ensureDirectoryExists($uploadDir, 0755, true);
                $file->move($uploadDir, $filename);
                chmod($uploadDir . '/' . $filename, 0644);
                $validated['signature_upload'] = asset('uploads/signatures/' . $filename);
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

    private function normalizeCommaSeparatedEmails(string $raw, bool $required = false, string $field = 'email'): ?string
    {
        $emails = collect(explode(',', $raw))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            if ($required) {
                throw ValidationException::withMessages([
                    $field => 'At least one email is required.',
                ]);
            }
            return null;
        }

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    $field => 'Invalid email address: ' . $email,
                ]);
            }
        }

        return $emails->implode(', ');
    }

    private function normalizeCommaSeparatedValues(string $raw, bool $required = false): ?string
    {
        $values = collect(explode(',', $raw))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            return $required ? '' : null;
        }

        return $values->implode(', ');
    }


    /**
     * Reset serial counters in serial_configurations when FY changes.
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

        \App\Models\SerialConfiguration::query()
            ->where('accountid', $accountid)
            ->where('reset_on_fy', true)
            ->update(['number_value' => null]);
    }

    public function financialYearUpdate(Request $request)
    {
        $accountid = $this->resolveAccountId();
        $account = Account::find($accountid);
        if (!$account) {
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
        $accountid = $this->resolveAccountId();

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

    public function financialYearSelect(Request $request): RedirectResponse
    {
        $accountid = $this->resolveAccountId();
        $validated = $request->validate([
            'fy_id' => 'required|string',
        ]);

        $financialYear = FinancialYear::query()
            ->where('accountid', $accountid)
            ->where('fy_id', $validated['fy_id'])
            ->first();

        if (!$financialYear) {
            return redirect()->back()->with('error', 'Selected financial year is not available for this account.');
        }

        session(['selected_financial_year_id' => $financialYear->fy_id]);

        return redirect()->back()->with('success', 'Financial Year switched to "' . $financialYear->financial_year . '".');
    }

    public function settingsCreate(): View
    {
        return view('settings.form', ['title' => 'Add System Setting']);
    }

    public function settingsStore(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required',
            'accountid' => 'nullable|string',
        ]);

        $userAccountId = $this->resolveAccountId();

        Setting::create([
            'setting_key' => $validated['key'],
            'setting_value' => $validated['value'],
            'accountid' => $userAccountId,
        ]);

        return redirect()->to(route('settings.index') . '#config')->with('success', 'Setting created successfully.');
    }

    public function settingsShow(Setting $setting): View
    {
        $accountid = $this->resolveAccountId();
        if ($setting->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }

        return view('settings.show', [
            'title' => $setting->setting_key ?? $setting->key ?? 'Setting',
            'subtitle' => 'Setting Details',
            'setting' => $setting,
        ]);
    }

    public function settingsEdit(Setting $setting)
    {
        $accountid = $this->resolveAccountId();
        if ($setting->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }

        return redirect()->to(route('settings.index', ['e' => base64_encode($setting->settingid)]) . '#config');
    }

    public function settingsUpdate(Request $request, Setting $setting)
    {
        $accountid = $this->resolveAccountId();
        if ($setting->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'key' => 'required|string',
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
        $accountid = $this->resolveAccountId();
        if ($setting->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }

        $setting->delete();

        return redirect()->to(route('settings.index') . '#config')->with('success', 'Setting deleted successfully.');
    }

    public function messageTemplateStore(Request $request): RedirectResponse
    {
        $accountid = $this->resolveAccountId();
        $templateTypes = implode(',', array_keys($this->messageTemplateTypes()));

        $validator = Validator::make($request->all(), [
            'template_type' => 'required|in:' . $templateTypes,
            'channel' => 'required|in:email,whatsapp,sms',
            'name' => 'nullable|required_if:channel,email|string',
            'template_id' => 'nullable|required_if:channel,sms,whatsapp|string',
            'meta_template_id' => 'nullable|string',
            'sender_id' => 'nullable|string',
            'subject' => 'nullable|string',
            'body' => 'nullable|required_if:channel,email|string',
            'is_active' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            $errorText = collect($validator->errors()->all())->filter()->implode("\n");
            return redirect()->to(route('settings.index') . '#message-templates')
                ->withErrors($validator)
                ->with('mt_error_toast', $errorText)
                ->with('mt_active_type', (string) $request->input('template_type', 'pi'))
                ->with('mt_active_channel', (string) $request->input('channel', 'email'))
                ->withInput();
        }
        $validated = $validator->validated();
        $channel = (string) ($validated['channel'] ?? '');
        $templateType = (string) ($validated['template_type'] ?? '');
        $incomingTemplateId = trim((string) ($validated['template_id'] ?? ''));
        $existingTemplate = MessageTemplate::query()
            ->where('accountid', $accountid)
            ->where('template_type', $templateType)
            ->where('channel', $channel)
            ->first();
        $previousTemplateId = trim((string) ($existingTemplate?->template_id ?? ''));
        $templateIdChanged = $previousTemplateId !== $incomingTemplateId;
        $prefetchedCampioTemplate = null;
        if (in_array($channel, ['sms', 'whatsapp'], true) && $incomingTemplateId !== '') {
            $prefetchedCampioTemplate = $this->findCampioTemplateById($accountid, $channel, $incomingTemplateId);
            if ($templateIdChanged && !is_array($prefetchedCampioTemplate)) {
                return redirect()->to(route('settings.index') . '#message-templates')
                    ->withErrors(['template_id' => 'Template ID not found in provider. Please verify and try again.'])
                    ->with('mt_error_toast', 'Template ID not found in provider. Please verify and try again.')
                    ->with('mt_active_type', $templateType)
                    ->with('mt_active_channel', $channel)
                    ->withInput();
            }
        }

        DB::transaction(function () use ($accountid, $validated, $templateType, $prefetchedCampioTemplate, $templateIdChanged) {
            $channel = (string) ($validated['channel'] ?? '');
            $templateId = $validated['template_id'] ?? null;
            $campioTemplate = $prefetchedCampioTemplate;

            $resolvedName = trim((string) ($validated['name'] ?? ''));
            $resolvedBody = (string) ($validated['body'] ?? '');
            $resolvedSenderId = $validated['sender_id'] ?? null;
            $resolvedMetaTemplateId = null;

            if (is_array($campioTemplate)) {
                if ($templateIdChanged && !empty($campioTemplate['name'])) {
                    $resolvedName = (string) $campioTemplate['name'];
                } elseif ($resolvedName === '' && !empty($campioTemplate['name'])) {
                    $resolvedName = (string) $campioTemplate['name'];
                }
                if ($templateIdChanged && !empty($campioTemplate['body'])) {
                    $resolvedBody = (string) $campioTemplate['body'];
                } elseif (trim($resolvedBody) === '' && !empty($campioTemplate['body'])) {
                    $resolvedBody = (string) $campioTemplate['body'];
                }
                if ($channel === 'sms' && empty($resolvedSenderId) && !empty($campioTemplate['sender_id'])) {
                    $resolvedSenderId = (string) $campioTemplate['sender_id'];
                }
                if ($channel === 'whatsapp') {
                    $resolvedMetaTemplateId = (string) ($campioTemplate['meta_template_id'] ?? $templateId);
                }
            } elseif ($channel === 'whatsapp') {
                $resolvedMetaTemplateId = (string) $templateId;
            }

            if ($channel !== 'email') {
                if ($resolvedName === '') {
                    $resolvedName = ucfirst($channel) . ' Template ' . (string) $templateId;
                }
                if (trim($resolvedBody) === '') {
                    $resolvedBody = '@{{client_name}}';
                }
            }

            MessageTemplate::updateOrCreate([
                'accountid' => $accountid,
                'template_type' => $templateType,
                'channel' => $channel,
            ], [
                'name' => $resolvedName,
                'template_id' => $templateId,
                'meta_template_id' => $resolvedMetaTemplateId,
                'sender_id' => $resolvedSenderId,
                'subject' => $validated['subject'] ?? null,
                'body' => $resolvedBody,
                'is_active' => array_key_exists('is_active', $validated)
                    ? (bool) $validated['is_active']
                    : true,
            ]);
        });

        return redirect()->to(route('settings.index') . '#message-templates')
            ->with('success', 'Message template saved successfully.');
    }

    public function messageTemplateUpdate(Request $request, MessageTemplate $template): RedirectResponse
    {
        $accountid = $this->resolveAccountId();
        if ($template->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'channel' => 'required|in:email,whatsapp,sms',
            'name' => 'nullable|required_if:channel,email|string',
            'template_id' => 'nullable|required_if:channel,sms,whatsapp|string',
            'meta_template_id' => 'nullable|string',
            'sender_id' => 'nullable|string',
            'subject' => 'nullable|string',
            'body' => 'nullable|required_if:channel,email|string',
            'is_active' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            $errorText = collect($validator->errors()->all())->filter()->implode("\n");
            return redirect()->to(route('settings.index') . '#message-templates')
                ->withErrors($validator)
                ->with('mt_error_toast', $errorText)
                ->with('mt_active_type', (string) $request->input('template_type', (string) $template->template_type))
                ->with('mt_active_channel', (string) $request->input('channel', 'email'))
                ->withInput();
        }
        $validated = $validator->validated();
        $channel = (string) ($validated['channel'] ?? '');
        $templateType = (string) ($template->template_type ?? '');
        $incomingTemplateId = trim((string) ($validated['template_id'] ?? ''));
        $previousTemplateId = trim((string) ($template->template_id ?? ''));
        $templateIdChanged = $previousTemplateId !== $incomingTemplateId;
        $prefetchedCampioTemplate = null;
        if (in_array($channel, ['sms', 'whatsapp'], true) && $incomingTemplateId !== '') {
            $prefetchedCampioTemplate = $this->findCampioTemplateById($accountid, $channel, $incomingTemplateId);
            if ($templateIdChanged && !is_array($prefetchedCampioTemplate)) {
                return redirect()->to(route('settings.index') . '#message-templates')
                    ->withErrors(['template_id' => 'Template ID not found in provider. Please verify and try again.'])
                    ->with('mt_error_toast', 'Template ID not found in provider. Please verify and try again.')
                    ->with('mt_active_type', $templateType)
                    ->with('mt_active_channel', $channel)
                    ->withInput();
            }
        }

        DB::transaction(function () use ($template, $validated, $prefetchedCampioTemplate, $templateIdChanged) {
            $channel = (string) ($validated['channel'] ?? '');
            $templateId = $validated['template_id'] ?? null;
            $campioTemplate = $prefetchedCampioTemplate;

            $resolvedName = trim((string) ($validated['name'] ?? ''));
            $resolvedBody = (string) ($validated['body'] ?? '');
            $resolvedSenderId = $validated['sender_id'] ?? null;
            $resolvedMetaTemplateId = null;

            if (is_array($campioTemplate)) {
                // Refresh from provider when template ID changes; otherwise keep user-edited values.
                if ($templateIdChanged && !empty($campioTemplate['name'])) {
                    $resolvedName = (string) $campioTemplate['name'];
                } elseif ($resolvedName === '' && !empty($campioTemplate['name'])) {
                    $resolvedName = (string) $campioTemplate['name'];
                }
                if ($templateIdChanged && !empty($campioTemplate['body'])) {
                    $resolvedBody = (string) $campioTemplate['body'];
                } elseif (trim($resolvedBody) === '' && !empty($campioTemplate['body'])) {
                    $resolvedBody = (string) $campioTemplate['body'];
                }
                if ($channel === 'sms' && empty($resolvedSenderId) && !empty($campioTemplate['sender_id'])) {
                    $resolvedSenderId = (string) $campioTemplate['sender_id'];
                }
                if ($channel === 'whatsapp') {
                    $resolvedMetaTemplateId = (string) ($campioTemplate['meta_template_id'] ?? $templateId);
                }
            } elseif ($channel === 'whatsapp') {
                $resolvedMetaTemplateId = (string) $templateId;
            }

            if ($channel !== 'email') {
                if ($resolvedName === '') {
                    $resolvedName = $template->name ?: (ucfirst($channel) . ' Template ' . (string) $templateId);
                }
                if (trim($resolvedBody) === '') {
                    $resolvedBody = $template->body ?: '@{{client_name}}';
                }
            }

            $template->update([
                'name' => $resolvedName,
                'template_id' => $templateId,
                'meta_template_id' => $resolvedMetaTemplateId,
                'sender_id' => $resolvedSenderId,
                'subject' => $validated['subject'] ?? null,
                'body' => $resolvedBody,
                'is_active' => array_key_exists('is_active', $validated)
                    ? (bool) $validated['is_active']
                    : (bool) $template->is_active,
            ]);
        });

        return redirect()->to(route('settings.index') . '#message-templates')
            ->with('success', 'Message template updated successfully.');
    }

    public function messageTemplateDestroy(MessageTemplate $template): RedirectResponse
    {
        $accountid = $this->resolveAccountId();
        if ($template->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }

        $template->delete();

        return redirect()->to(route('settings.index') . '#message-templates')
            ->with('success', 'Message template deleted successfully.');
    }

    private function findCampioTemplateById(string $accountid, string $channel, string $templateId): ?array
    {
        $templateId = trim($templateId);
        if ($templateId === '' || !in_array($channel, ['sms', 'whatsapp'], true)) {
            return null;
        }

        $baseUrl = rtrim((string) env('CAMPIO_BASE_URL', 'http://alpha.skoolready.com/campio'), '/');
        $token = trim((string) env('CAMPIO_AUTH_TOKEN', ''));
        $apiKey = trim((string) env('CAMPIO_API_KEY', ''));

        $request = Http::acceptJson()->timeout(20);
        if ($token !== '') {
            $request = $request->withToken($token);
        }
        if ($apiKey !== '') {
            $request = $request->withHeaders(['X-API-KEY' => $apiKey]);
        }

        try {
            $response = $request->get($baseUrl . '/api/templates/' . $channel, [
                'account_id' => $accountid,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $json = $response->json();
        $templates = data_get($json, 'data.templates', []);
        if (!is_array($templates)) {
            return null;
        }

        foreach ($templates as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowId = trim((string) ($row['id'] ?? ''));
            $rowMetaTemplateId = trim((string) ($row['meta_template_id'] ?? ''));
            $matches = $rowId === $templateId;
            if ($channel === 'whatsapp') {
                $matches = $matches || ($rowMetaTemplateId !== '' && $rowMetaTemplateId === $templateId);
            }
            if (!$matches) {
                continue;
            }

            return [
                'name' => (string) ($row['name'] ?? ''),
                'body' => (string) (
                    $row['body']
                    ?? $row['template_body']
                    ?? $row['message']
                    ?? ''
                ),
                'sender_id' => (string) ($row['sender_id'] ?? ''),
                'meta_template_id' => (string) ($row['meta_template_id'] ?? ''),
            ];
        }

        return null;
    }

    public function messageTemplateToggle(Request $request, MessageTemplate $template)
    {
        $accountid = $this->resolveAccountId();
        if ($template->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }

        $template->update([
            'is_active' => ! (bool) $template->is_active,
        ]);

        // If the request expects JSON (AJAX), return a JSON response so the frontend
        // can update the UI immediately without relying on a redirect.
        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool) $template->is_active,
                'message' => 'Message template status updated successfully.',
            ]);
        }

        return redirect()->to(route('settings.index') . '#message-templates')
            ->with('success', 'Message template status updated successfully.');
    }

    public function termsConditionsStore(Request $request)
    {
        $accountid = $this->resolveAccountId();

        $editId = $request->e ? base64_decode($request->e) : null;

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'tc_id' => 'nullable|string|size:6|exists:terms_conditions,tc_id',
            'type' => 'required|in:billing,quotation,proforma',
            'content' => 'required|string',
            'sequence' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
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
        $validated['is_default'] = (bool) ($validated['is_default'] ?? false);

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
            $message = 'T&C created successfully.';
        }

        // Redirect without edit_tc parameter to clear the form
        return redirect()->to(route('settings.index') . '#terms-conditions')->with('success', $message);
    }

    public function termsConditionsUpdateSequence(Request $request, TermsCondition $term)
    {
        $accountid = $this->resolveAccountId();
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

    public function termsConditionsToggle(Request $request, TermsCondition $term)
    {
        $accountid = $this->resolveAccountId();
        if ($term->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }
        $term->update(['is_active' => !$term->is_active]);

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $term->is_active,
                'message' => 'T&C status updated successfully.',
            ]);
        }

        return redirect()->to(route('settings.index') . '#terms-conditions')->with('success', 'T&C status toggled.');
    }

    public function termsConditionsDestroy(TermsCondition $term)
    {
        $accountid = $this->resolveAccountId();
        if ($term->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }
        $term->delete();
        return redirect()->to(route('settings.index') . '#terms-conditions')->with('success', 'T&C deleted successfully.');
    }

    public function fyPrefixUpdate(Request $request)
    {
        $accountid = $this->resolveAccountId();
        $account = Account::find($accountid);
        if (!$account) {
            return redirect()->back()->with('error', 'Account not found.');
        }

        $validated = $request->validate([
            'fy_prefix_type' => 'required|in:fixed_value,value/number',
            'fy_prefix_sep' => 'required|in:-,/,none',
            'fy_prefix_value' => 'nullable|string',
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

        foreach (['prefix', 'number', 'suffix'] as $part) {
            $typeKey = $part . '_type';
            $valueKey = $part . '_value';
            $lengthKey = $part . '_length';
            $type = $validated[$typeKey] ?? ($part === 'number' ? 'auto increment' : 'manual text');

            if ($type === 'auto increment') {
                $start = $validated[$valueKey] ?? 1;
                $validated[$valueKey] = (string) max(1, (int) $start);
                $validated[$lengthKey] = null;
                continue;
            }

            if ($type === 'auto generate') {
                $validated[$lengthKey] = max(1, (int) ($validated[$lengthKey] ?? 4));
                continue;
            }

            // Date/year/manual text modes should not carry stale length metadata.
            $validated[$lengthKey] = null;

            if (in_array($type, ['date', 'year', 'month-year', 'date-month'], true)) {
                $validated[$valueKey] = null;
            }
        }

        return $validated;
    }

    public function taxStore(Request $request)
    {
        $accountid = $this->resolveAccountId();

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'tax_name' => 'nullable|string',
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
        $accountid = $this->resolveAccountId();
        if ($tax->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'tax_name' => 'nullable|string',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:GST,VAT,Sales Tax,Service Tax,Other',
        ]);
        $tax->update($validated);

        return redirect()->to(route('settings.index') . '#taxes')->with('success', 'Tax updated successfully.');
    }

    public function taxDestroy(Tax $tax)
    {
        $accountid = $this->resolveAccountId();
        if ($tax->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }
        $tax->delete();
        return redirect()->to(route('settings.index') . '#taxes')->with('success', 'Tax deleted successfully.');
    }

    public function taxToggle(Tax $tax)
    {
        $accountid = $this->resolveAccountId();
        if ($tax->accountid !== $accountid) {
            abort(403, 'Unauthorized');
        }
        $tax->update(['is_active' => !$tax->is_active]);
        return redirect()->to(route('settings.index') . '#taxes')->with('success', 'Tax status toggled.');
    }

}
