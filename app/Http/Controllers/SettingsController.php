<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\AccountQuotationDetail;
use App\Models\FinancialYear;
use App\Models\Setting;
use App\Models\TermsCondition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $editingSetting = null;
        if (request('edit')) {
            $editingSetting = Setting::find(request('edit'));
        }

        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        $billingDetails = $account ? $account->billingDetails : collect();
        $quotationDetails = $account ? $account->quotationDetails : collect();

        $editingBillingDetail = request('edit_bd') ? AccountBillingDetail::where('account_bdid', request('edit_bd'))->where('accountid', $accountid)->first() : ($account->billingDetails()->first());

        $editingQuotationDetail = request('edit_qd') ? AccountQuotationDetail::where('account_qdid', request('edit_qd'))->where('accountid', $accountid)->first() : ($account->quotationDetails()->first());

        $termsQuery = TermsCondition::query()
            ->where('accountid', $accountid)
            ->orderByRaw('COALESCE(sort_order, 999999), created_at DESC');

        $billingTerms = (clone $termsQuery)
            ->where('type', 'billing')
            ->get();

        $quotationTerms = (clone $termsQuery)
            ->where('type', 'quotation')
            ->get();

        $editingTerm = null;
        if (request('edit_tc')) {
            $editingTerm = TermsCondition::query()
                ->where('accountid', $accountid)
                ->where('tc_id', request('edit_tc'))
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
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'editingSetting' => $editingSetting,
            'currencies' => $currencies,
            'suggestedKeys' => $suggestedKeys,
            'billingTerms' => $billingTerms,
            'quotationTerms' => $quotationTerms,
            'editingTerm' => $editingTerm,
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
        ]);

        if (!empty($validated['fy_month']) && !empty($validated['fy_day'])) {
            $validated['fy_startdate'] = $validated['fy_month'] . '-' . $validated['fy_day'];
        }

        $account->update($validated);

        return redirect()->to(route('settings.index') . '#personal')->with('success', 'Profile updated successfully.');
    }

    public function accountBillingUpdate(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = Account::find($accountid);

        if (! $account) {
            return redirect()->back()->with('error', 'Account not found.');
        }

        $validated = $request->validate([
            'account_bdid' => 'nullable|string|size:6|exists:account_billing_details,account_bdid',
            'serial_number' => 'nullable|string|max:20',
            'prefix' => 'nullable|string|max:50',
            'suffix' => 'nullable|string|max:50',
            'serial_mode' => 'required|in:auto_generate,auto_increment',
            'alphanumeric_length' => 'nullable|integer|in:4,6',
            'auto_increment_start' => 'nullable|integer|min:1|max:99999',
            'reset_on_fy' => 'boolean',
            'billing_name' => 'required|string|max:150',
            'address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'gstin' => 'nullable|string|max:50',
            'tin' => 'nullable|string|max:50',
            'authorize_signatory' => 'nullable|string|max:255',
            'signature_upload' => 'nullable|string|max:500',
            'billing_from_email' => 'nullable|email|max:255',
            'terms_conditions' => 'nullable|string',
        ]);

        if (!empty($validated['account_bdid'])) {
            $billingDetail = AccountBillingDetail::where('account_bdid', $validated['account_bdid'])->where('accountid', $accountid)->firstOrFail();
            $billingDetail->update($validated);
        } else {
            $validated['accountid'] = $accountid;
            AccountBillingDetail::create($validated);
        }

        return redirect()->to(route('settings.index') . '#billing-details')->with('success', 'Billing details updated successfully.');
    }

    public function accountQuotationUpdate(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = Account::find($accountid);

        if (! $account) {
            return redirect()->back()->with('error', 'Account not found.');
        }

        $validated = $request->validate([
            'account_qdid' => 'nullable|string|size:6|exists:account_quotation_details,account_qdid',
            'serial_number' => 'nullable|string|max:20',
            'prefix' => 'nullable|string|max:50',
            'suffix' => 'nullable|string|max:50',
            'serial_mode' => 'required|in:auto_generate,auto_increment',
            'alphanumeric_length' => 'nullable|integer|in:4,6',
            'auto_increment_start' => 'nullable|integer|min:1|max:99999',
            'reset_on_fy' => 'boolean',
            'quotation_name' => 'required|string|max:150',
            'address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'gstin' => 'nullable|string|max:50',
            'tin' => 'nullable|string|max:50',
            'authorize_signatory' => 'nullable|string|max:255',
            'signature_upload' => 'nullable|string|max:500',
            'billing_from_email' => 'nullable|email|max:255',
            'terms_conditions' => 'nullable|string',
        ]);

        if (!empty($validated['account_qdid'])) {
            $quotationDetail = AccountQuotationDetail::where('account_qdid', $validated['account_qdid'])->where('accountid', $accountid)->firstOrFail();
            $quotationDetail->update($validated);
        } else {
            $validated['accountid'] = $accountid;
            AccountQuotationDetail::create($validated);
        }

        return redirect()->to(route('settings.index') . '#quotation-details')->with('success', 'Quotation details updated successfully.');
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

        $fy = FinancialYear::updateOrCreate(
            [
                'accountid' => $account->accountid,
                'financial_year' => $fyString,
            ],
            [
                'default' => true,
            ]
        );

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

        FinancialYear::where('accountid', $accountid)->update(['default' => false]);
        $financialYear->update(['default' => true]);

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
        return redirect()->to(route('settings.index', ['edit' => $setting->settingid]) . '#config');
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
}
