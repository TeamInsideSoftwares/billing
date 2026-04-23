<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

use App\Models\Client;
use App\Models\ClientBillingDetail;
use App\Models\AccountBillingDetail;
use App\Models\AccountQuotationDetail;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Group;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceAddonCosting;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\TermsCondition;
use App\Models\User;
use App\Models\Account;
use App\Models\ProductCategory;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ServiceCosting;

class BillingUiController extends Controller
{
    public function dashboard(): View
    {
        $accountid = auth()->id();
        
        $stats = [
            ['label' => 'Total Clients', 'value' => Client::where('accountid', $accountid)->count(), 'change' => '', 'tone' => 'positive'],
            ['label' => 'Total Invoices', 'value' => Invoice::where('accountid', $accountid)->count(), 'change' => '', 'tone' => 'warning'],
            ['label' => 'Total Revenue', 'value' => 'Rs ' . number_format(Payment::where('accountid', $accountid)->sum('amount'), 0), 'change' => '', 'tone' => 'positive'],
            ['label' => 'Overdue Count', 'value' => Invoice::where('accountid', $accountid)->where('status', 'overdue')->count(), 'change' => '', 'tone' => 'warning'],
        ];

        return view('dashboard', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'upcomingInvoices' => [],
            'activities' => [
                'Welcome to your Billing Workspace.',
            ],
        ]);
    }

    // Clients CRUD
    public function clients(): View
    {
        $query = Client::query();
        $searchTerm = request('search', '');
        
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('business_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('contact_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%');
            });
        }
        $resultCount = $query->count();
        
        $clients = $query->latest()->take(20)->get()->map(function ($client) {
            $outstanding = Invoice::where('clientid', $client->clientid)->where('status', '!=', 'paid')->sum('grand_total') - Payment::where('clientid', $client->clientid)->sum('amount');
            return [
'record_id' => $client->clientid,
                'name' => $client->business_name ?? $client->contact_name,
                'contact' => $client->contact_name,
                'email' => $client->email,
                'status' => $client->status ?? 'Active',
                'balance' => number_format($outstanding, 0),
            ];
        });

        return view('clients.index', [
            'title' => 'Clients',
            'clients' => $clients,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function services(): View
    {
        $query = Service::with(['category', 'costings', 'addons'])
            ->join('ps_categories', 'services.ps_catid', '=', 'ps_categories.ps_catid', 'left')
            ->select('services.*', 'ps_categories.name as cat_name')
            ->orderBy('cat_name', 'asc')
            ->orderBy('services.sequence', 'asc')
            ->orderBy('services.name', 'asc');
        
        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('services.name', 'like', '%' . $searchTerm . '%');
        }
        $resultCount = $query->count();
        $services = $query->take(20)->get()->map(function ($service) {
            $costings = $service->costings->map(function (ServiceCosting $costing) {
                return [
                    'currency_code' => $costing->currency_code,
                    'cost_price' => $costing->cost_price,
                    'selling_price' => $costing->selling_price,
                    'sac_code' => $costing->sac_code,
                    'tax_rate' => $costing->tax_rate,
                    'tax_included' => $costing->tax_included,
                ];
            });

            $addons = $service->addons->map(function ($addon) {
                return [
                    'name' => $addon->name,
                    'costings' => $addon->costings->map(function ($ac) {
                        return [
                            'currency_code' => $ac->currency_code,
                            'selling_price' => $ac->selling_price,
                        ];
                    }),
                ];
            });

            return [
                'record_id' => $service->serviceid,
                'name' => $service->name,
                'sequence' => (int) ($service->sequence ?? 0),
                'category_name' => $service->category->name ?? 'No Category',
                'costings' => $costings,
                'addon_count' => $service->addons->count(),
                'addons' => $addons,
                'status' => $service->is_active ? 'Active' : 'Inactive',
            ];
        });

        $catQuery = ProductCategory::query()->orderBy('sequence')->orderBy('name');
        $catSearch = request('cat_search', '');
        if ($catSearch) {
            $catQuery->where('name', 'like', '%' . $catSearch . '%');
        }
        $catResultCount = $catQuery->count();
        $productCategories = $catQuery->take(20)->get()->map(function ($pc) {
            return [
                'record_id' => $pc->ps_catid,
                'name' => $pc->name,
                'sequence' => (int) ($pc->sequence ?? 0),
                'description' => $pc->description ?? '',
                'status' => strtolower($pc->status ?? 'active'),
            ];
        });

        return view('services.index', [
            'title' => 'Services',
            'services' => $services,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'productCategories' => $productCategories,
            'catSearch' => $catSearch,
            'catResultCount' => $catResultCount,
        ]);
    }

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

        // Check for edit mode
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
            ]
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

    public function termsConditionsStore(Request $request)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';

        $validated = $request->validate([
            'tc_id' => 'nullable|string|size:8|exists:terms_conditions,tc_id',
            'type' => 'required|in:billing,quotation',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $validated['accountid'] = $accountid;
        $validated['title'] = $validated['title'] ?? Str::limit(strip_tags($validated['content']), 50); // Auto title if missing

        if (!empty($validated['tc_id'])) {
            $term = TermsCondition::where('tc_id', $validated['tc_id'])->where('accountid', $accountid)->firstOrFail();
            $term->update($validated);
            return redirect()->to(route('settings.index') . '#terms-conditions')->with('success', 'T&C updated.');
        } else {
            TermsCondition::create($validated);
            return redirect()->to(route('settings.index') . '#terms-conditions')->with('success', 'T&C created.');
        }
    }

    public function termsConditionsToggle(TermsCondition $term)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        if ($term->accountid !== $accountid) {
            abort(403);
        }
        $term->update(['is_active' => !$term->is_active]);
        return back()->with('success', 'Term status toggled.');
    }

    public function termsConditionsDestroy(TermsCondition $term)
    {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        if ($term->accountid !== $accountid) {
            abort(403);
        }
        $term->delete();
        return back()->with('success', 'Term deleted.');
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
            // 'terms_conditions' => 'nullable|string',  // unused - managed in terms tab
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
                'prefix_value' => null,
                'number_value' => null,
                'suffix_value' => null,
            ]);
        }

        // Reset quotation serial
        $quotationDetail = AccountQuotationDetail::where('accountid', $accountid)->first();
        if ($quotationDetail && $quotationDetail->reset_on_fy) {
            $quotationDetail->update([
                'prefix_value' => null,
                'number_value' => null,
                'suffix_value' => null,
            ]);
        }
    }

    public function financialYearUpdate(Request $request)
        {
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = Account::find($accountid);
        if (!$account) {
            return redirect()->back()->with('error', 'Account not found.');
        }

        $validated = $request->validate([
            'year_start' => 'required|integer|min:2000|max:2100',
            'year_end' => 'required|integer|min:2000|max:2100',
        ]);

        $fyString = $validated['year_start'] . '-' . $validated['year_end'];

        // Find or create FY.
        $fy = FinancialYear::updateOrCreate(
            [
                'accountid' => $account->accountid,
                'financial_year' => $fyString,
            ],
            [
                'default' => true,
            ]
        );

        // Set as default
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

        // Set all others to false
        FinancialYear::where('accountid', $accountid)->update(['default' => false]);

        // Set this one to true
        $financialYear->update(['default' => true]);

        // Reset serials if default FY changed
        if (!$previousDefault || $previousDefault->fy_id !== $financialYear->fy_id) {
            $this->resetSerialNumbersIfRequired($accountid);
        }

        return redirect()->to(route('settings.index') . '#financial-year')->with('success', 'Financial Year "' . $financialYear->financial_year . '" set as default.');
        }

        public function invoices(): View
    {
        $query = Invoice::with('client');
        $searchTerm = request('search', '');
        
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('pi_number', 'like', '%' . $searchTerm . '%')
                  ->orWhere('ti_number', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('client', function ($clientQuery) use ($searchTerm) {
                      $clientQuery->where('business_name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }
        $resultCount = $query->count();
        
        $invoices = $query->latest()->take(20)->get()->map(function ($invoice) {
            return [
'record_id' => $invoice->invoiceid,
                'number' => $invoice->invoice_number ?? 'INV-' . str_pad($invoice->invoiceid, 4, '0', STR_PAD_LEFT),
                'client' => $invoice->client->business_name ?? 'Client',
                'issued' => $invoice->created_at->format('d M Y'),
                'due' => $invoice->due_date?->format('d M Y') ?? 'N/A',
                'amount' => 'Rs ' . number_format($invoice->grand_total ?? 0),
                'status' => $invoice->status ?? 'Draft',
            ];
        });

        return view('invoices.index', [
            'title' => 'Invoices',
            'invoices' => $invoices,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function payments(): View
    {
        $query = Payment::with('client');
        $searchTerm = request('search', '');
        
        if ($searchTerm) {
            $query->where('reference', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('client', function ($q) use ($searchTerm) {
                      $q->where('business_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                  });
        }
        $resultCount = $query->count();
        
        $payments = $query->latest()->take(20)->get()->map(function ($payment) {
            return [
                'record_id' => $payment->paymentid,
                'number' => $payment->payment_number,
                'client' => $payment->client->business_name ?? 'Client',
                'date' => $payment->payment_date?->format('d M Y'),
                'method' => $payment->payment_method ?? 'Bank Transfer',
                'amount' => 'Rs ' . number_format($payment->amount ?? 0),
                'status' => $payment->status ?? 'Completed',
            ];
        });

        return view('payments.index', [
            'title' => 'Payments',
            'payments' => $payments,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

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

    public function quotations(): View
    {
        $query = Quotation::with('client');
        $searchTerm = request('search', '');
        
        if ($searchTerm) {
            $query->where('quotation_number', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('client', function ($q) use ($searchTerm) {
                      $q->where('business_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                  });
        }
        $resultCount = $query->count();
        
        $quotations = $query->latest()->take(20)->get()->map(function ($quotation) {
            return [
'record_id' => $quotation->quotationid,
                'number' => $quotation->quotation_number ?? 'QUO-' . str_pad($quotation->quotationid, 4, '0', STR_PAD_LEFT),
                'client' => $quotation->client->business_name ?? 'Client',
                'amount' => 'Rs ' . number_format($quotation->total ?? 0),
                'expiry' => $quotation->expiry_date?->format('d M Y') ?? 'N/A',
                'status' => $quotation->status ?? 'Draft',
            ];
        });

        return view('quotations.index', [
            'title' => 'Quotations',
            'quotations' => $quotations,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    // Services CRUD
    public function servicesCreate(): View
    {
        $categories = ProductCategory::where('status', 'active')->orderBy('sequence')->orderBy('name')->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);
        $accountCurrency = auth()->check()
            ? (auth()->user()->account->currency_code ?? 'INR')
            : 'INR';
        return view('services.create', [
            'title' => 'New Service',
            'categories' => $categories,
            'defaultCurrency' => $accountCurrency,
            'currencies' => $currencies,
            'nextServiceSequence' => (Service::max('sequence') ?? 0) + 1,
        ]);
    }

    public function servicesStore(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'sync' => 'required|in:yes,no',
            'name' => 'required|string|max:255',
            'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
            'description' => 'nullable|string',
            'sequence' => 'nullable|integer|min:0',
            'accountid' => 'nullable|size:6',
            'costings' => 'required|array|min:1',
            'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso|distinct',
            'costings.*.cost_price' => 'required|numeric|min:0',
            'costings.*.selling_price' => 'required|numeric|min:0',
            'costings.*.sac_code' => 'nullable|string|max:20',
            'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'costings.*.tax_included' => 'required|in:yes,no',
            'addons' => 'nullable|array',
            'addons.*.name' => 'required|string|max:150',
            'addons.*.description' => 'nullable|string',
            'addons.*.status' => 'required|in:active,inactive',
            'addons.*.sequence' => 'nullable|integer|min:0',
            'addons.*.costings' => 'required|array|min:1',
            'addons.*.costings.*.currency_code' => 'required|string|size:3|exists:currency,iso',
            'addons.*.costings.*.cost_price' => 'required|numeric|min:0',
            'addons.*.costings.*.selling_price' => 'required|numeric|min:0',
            'addons.*.costings.*.sac_code' => 'nullable|string|max:20',
            'addons.*.costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'addons.*.costings.*.tax_included' => 'required|in:yes,no',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;
        $validated['is_active'] = true;

        DB::transaction(function () use ($validated) {
            $costings = collect($validated['costings'])->map(function (array $costing) {
                return [
                    'currency_code' => strtoupper($costing['currency_code']),
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'] ?? null,
                    'tax_rate' => $costing['tax_rate'] ?? 0,
                    'tax_included' => $costing['tax_included'],
                ];
            });

$service = Service::create([
                'type' => $validated['type'],
                'sync' => $validated['sync'],
                'name' => $validated['name'],
                'ps_catid' => $validated['ps_catid'] ?? null,
                'description' => $validated['description'] ?? null,
                'accountid' => $validated['accountid'],
                'is_active' => $validated['is_active'],
                'sequence' => $validated['sequence'] ?? ((Service::where('accountid', $validated['accountid'])->where('ps_catid', $validated['ps_catid'] ?? null)->max('sequence') ?? 0) + 1),
            ]);

            $costings->each(function ($costing) use ($service, $validated) {
                $service->costings()->create([
                    'accountid' => $validated['accountid'],
                    'currency_code' => $costing['currency_code'],
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'],
                    'tax_rate' => $costing['tax_rate'],
                    'tax_included' => $costing['tax_included'],
                ]);
            });

            $addons = collect($validated['addons'] ?? [])
                ->filter(fn (array $addon) => ! empty(trim((string) ($addon['name'] ?? ''))))
                ->values();

            $addons->each(function (array $addon, int $index) use ($service, $validated) {
                $addonModel = $service->addons()->create([
                    'accountid' => $validated['accountid'],
                    'name' => trim($addon['name']),
                    'description' => $addon['description'] ?? null,
                    'sequence' => $addon['sequence'] ?? ($index + 1),
                    'is_active' => true,
                ]);

                collect($addon['costings'] ?? [])->each(function (array $costing) use ($addonModel, $validated) {
                    $addonModel->costings()->create([
                        'accountid' => $validated['accountid'],
                        'currency_code' => strtoupper($costing['currency_code']),
                        'cost_price' => $costing['cost_price'],
                        'selling_price' => $costing['selling_price'],
                        'sac_code' => $costing['sac_code'] ?? null,
                        'tax_rate' => $costing['tax_rate'] ?? 0,
                        'tax_included' => $costing['tax_included'],
                    ]);
                });
            });
        });

        return redirect()->route('services.index')->with('success', 'Service created successfully.');
    }

    public function servicesShow(Service $service): View
    {
        $service->load(['subscriptions', 'category', 'costings', 'addons.costings']);
        return view('services.show', [
            'title' => 'Service Details',
            'service' => $service,
        ]);
    }

    public function servicesEdit(Service $service): View
    {
        $categories = ProductCategory::where('status', 'active')->orderBy('sequence')->orderBy('name')->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);
        $service->load(['costings', 'addons.costings']);
        $accountCurrency = auth()->check()
            ? (auth()->user()->account->currency_code ?? 'INR')
            : 'INR';
        return view('services.edit', [
            'title' => 'Edit Service', 
            'service' => $service,
            'categories' => $categories,
            'defaultCurrency' => $accountCurrency,
            'currencies' => $currencies,
        ]);
    }

    public function servicesUpdate(Request $request, Service $service)
    {
        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'sync' => 'required|in:yes,no',
            'name' => 'required|string|max:255',
            'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
            'description' => 'nullable|string',
            'sequence' => 'nullable|integer|min:0',
            'costings' => 'required|array|min:1',
            'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso|distinct',
            'costings.*.cost_price' => 'required|numeric|min:0',
            'costings.*.selling_price' => 'required|numeric|min:0',
            'costings.*.sac_code' => 'nullable|string|max:20',
            'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'costings.*.tax_included' => 'required|in:yes,no',
            'addons' => 'nullable|array',
            'addons.*.name' => 'required|string|max:150',
            'addons.*.description' => 'nullable|string',
            'addons.*.status' => 'required|in:active,inactive',
            'addons.*.sequence' => 'nullable|integer|min:0',
            'addons.*.costings' => 'required|array|min:1',
            'addons.*.costings.*.currency_code' => 'required|string|size:3|exists:currency,iso',
            'addons.*.costings.*.cost_price' => 'required|numeric|min:0',
            'addons.*.costings.*.selling_price' => 'required|numeric|min:0',
            'addons.*.costings.*.sac_code' => 'nullable|string|max:20',
            'addons.*.costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'addons.*.costings.*.tax_included' => 'required|in:yes,no',
        ]);

        $validated['is_active'] = true;

        DB::transaction(function () use ($validated, $service) {
            $costings = collect($validated['costings'])->map(function (array $costing) {
                return [
                    'currency_code' => strtoupper($costing['currency_code']),
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'] ?? null,
                    'tax_rate' => $costing['tax_rate'] ?? 0,
                    'tax_included' => $costing['tax_included'],
                ];
            });

$service->update([
                'type' => $validated['type'],
                'sync' => $validated['sync'],
                'name' => $validated['name'],
                'ps_catid' => $validated['ps_catid'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'],
                'sequence' => $validated['sequence'] ?? ($service->sequence ?? 0),
            ]);

            $service->costings()->delete();

            $costings->each(function ($costing) use ($service) {
                $service->costings()->create([
                    'accountid' => $service->accountid,
                    'currency_code' => $costing['currency_code'],
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'],
                    'tax_rate' => $costing['tax_rate'],
                    'tax_included' => $costing['tax_included'],
                ]);
            });

            $service->addons()->delete();

            $addons = collect($validated['addons'] ?? [])
                ->filter(fn (array $addon) => ! empty(trim((string) ($addon['name'] ?? ''))))
                ->values();

            $addons->each(function (array $addon, int $index) use ($service) {
                $addonModel = $service->addons()->create([
                    'accountid' => $service->accountid,
                    'name' => trim($addon['name']),
                    'description' => $addon['description'] ?? null,
                    'sequence' => $addon['sequence'] ?? ($index + 1),
                    'is_active' => ($addon['status'] ?? 'active') === 'active',
                ]);

                collect($addon['costings'] ?? [])->each(function (array $costing) use ($addonModel, $service) {
                    $addonModel->costings()->create([
                        'accountid' => $service->accountid,
                        'currency_code' => strtoupper($costing['currency_code']),
                        'cost_price' => $costing['cost_price'],
                        'selling_price' => $costing['selling_price'],
                        'sac_code' => $costing['sac_code'] ?? null,
                        'tax_rate' => $costing['tax_rate'] ?? 0,
                        'tax_included' => $costing['tax_included'],
                    ]);
                });
            });
        });

        return redirect()->route('services.index')->with('success', 'Service updated successfully.');
    }

    public function servicesDestroy(Service $service)
    {
        $service->delete();

        return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
    }

    public function servicesReorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'required|string|exists:services,serviceid',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['order'] as $index => $serviceId) {
                Service::where('serviceid', $serviceId)->update([
                    'sequence' => $index + 1,
                ]);
            }
        });

        return response()->json(['success' => true]);
    }

    public function servicesSaveAjax(Request $request)
    {
$validated = $request->validate([
            'serviceid' => 'nullable|string|exists:services,serviceid',
            'type' => 'required|in:product,service',
            'sync' => 'required|in:yes,no',
            'name' => 'required|string|max:255',
            'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
            'description' => 'nullable|string',
            'costings' => 'required|array|min:1',
            'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso',
            'costings.*.cost_price' => 'required|numeric|min:0',
            'costings.*.selling_price' => 'required|numeric|min:0',
            'costings.*.sac_code' => 'nullable|string|max:20',
            'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'costings.*.tax_included' => 'required|in:yes,no',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        
        $service = DB::transaction(function () use ($validated, $userAccountId) {
$serviceData = [
                'type' => $validated['type'],
                'sync' => $validated['sync'],
                'name' => $validated['name'],
                'ps_catid' => $validated['ps_catid'] ?? null,
                'description' => $validated['description'] ?? null,
                'accountid' => $userAccountId,
                'is_active' => true,
            ];

            if (!empty($validated['serviceid'])) {
                $service = Service::where('serviceid', $validated['serviceid'])->firstOrFail();
                $service->update($serviceData);
            } else {
                $serviceData['sequence'] = (Service::where('accountid', $userAccountId)->max('sequence') ?? 0) + 1;
                $service = Service::create($serviceData);
            }

            // Sync costings
            $service->costings()->delete();
            foreach ($validated['costings'] as $costing) {
                $service->costings()->create([
                    'accountid' => $userAccountId,
                    'currency_code' => strtoupper($costing['currency_code']),
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'] ?? null,
                    'tax_rate' => $costing['tax_rate'] ?? 0,
                    'tax_included' => $costing['tax_included'],
                ]);
            }

            return $service;
        });

        return response()->json([
            'success' => true,
            'message' => 'Service saved successfully.',
            'serviceid' => $service->serviceid,
        ]);
    }

    public function addonsSaveAjax(Request $request)
    {
        $validated = $request->validate([
            'serviceid' => 'required|string|exists:services,serviceid',
            'addonid' => 'nullable|string|exists:service_addons,addonid',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'costings' => 'required|array|min:1',
            'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso',
            'costings.*.cost_price' => 'required|numeric|min:0',
            'costings.*.selling_price' => 'required|numeric|min:0',
            'costings.*.sac_code' => 'nullable|string|max:20',
            'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'costings.*.tax_included' => 'required|in:yes,no',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        $addon = DB::transaction(function () use ($validated, $userAccountId) {
            $addonData = [
                'accountid' => $userAccountId,
                'serviceid' => $validated['serviceid'],
                'name' => trim($validated['name']),
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ];

            if (!empty($validated['addonid'])) {
                $addon = ServiceAddon::where('addonid', $validated['addonid'])->firstOrFail();
                $addon->update($addonData);
            } else {
                $addonData['sequence'] = (ServiceAddon::where('serviceid', $validated['serviceid'])->max('sequence') ?? 0) + 1;
                $addon = ServiceAddon::create($addonData);
            }

            // Sync costings
            $addon->costings()->delete();
            foreach ($validated['costings'] as $costing) {
                $addon->costings()->create([
                    'accountid' => $userAccountId,
                    'currency_code' => strtoupper($costing['currency_code']),
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'] ?? null,
                    'tax_rate' => $costing['tax_rate'] ?? 0,
                    'tax_included' => $costing['tax_included'],
                ]);
            }

            return $addon;
        });

        return response()->json([
            'success' => true,
            'message' => 'Add-on item saved successfully.',
            'addonid' => $addon->addonid,
        ]);
    }

    // Product Categories CRUD
    public function productCategories(): View
    {
        $query = ProductCategory::query()->orderBy('sequence')->orderBy('name');
        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }
        $resultCount = $query->count();
        $productCategories = $query->take(20)->get()->map(function ($pc) {
            return [
                'record_id' => $pc->ps_catid,
                'name' => $pc->name,
                'sequence' => (int) ($pc->sequence ?? 0),
                'description' => Str::limit($pc->description ?? '', 50),
                'status' => ucfirst($pc->status ?? 'Active'),
            ];
        });

        return view('product-categories.index', [
            'title' => 'Product Categories',
            'productCategories' => $productCategories,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function productCategoriesCreate(): View
    {
        return view('product-categories.create', ['title' => 'New Product Category']);
    }

    public function productCategoriesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sequence' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $userAccountId;
        $validated['sequence'] = $validated['sequence'] ?? ((ProductCategory::max('sequence') ?? 0) + 1);

        ProductCategory::create($validated);

        return redirect()->back()->with('success', 'Product category created successfully.')->with('open_cat_modal', true);
    }

    public function productCategoriesShow(ProductCategory $productCategory): View
    {
        return view('product-categories.show', [
            'title' => 'Product Category Details',
            'productCategory' => $productCategory,
        ]);
    }

    public function productCategoriesEdit(ProductCategory $productCategory): View
    {
        return view('product-categories.edit', ['title' => 'Edit Product Category', 'productCategory' => $productCategory]);
    }

    public function productCategoriesUpdate(Request $request, $id)
    {
        $category = ProductCategory::where('ps_catid', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sequence' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        $validated['sequence'] = $validated['sequence'] ?? ($category->sequence ?? 0);
        $category->update($validated);

        return redirect()->back()->with('success', 'Product category updated successfully.')->with('open_cat_modal', true);
    }

    public function productCategoriesDestroy(ProductCategory $productCategory)
    {
        $productCategory->delete();

        return redirect()->back()->with('success', 'Product category deleted successfully.')->with('open_cat_modal', true);
    }

    // Groups CRUD
    public function groups(): View
    {
        $query = Group::query();
        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('group_name', 'like', '%' . $searchTerm . '%');
        }
        $resultCount = $query->count();
        $groups = $query->latest()->take(20)->get()->map(function ($g) {
            return [
                'record_id' => $g->groupid,
                'group_name' => $g->group_name,
                'email' => $g->email ?? '-',
                'city' => $g->city ?? '-',
                'state' => $g->state ?? '-',
                'address_line_1' => $g->address_line_1 ?? '',
                'address_line_2' => $g->address_line_2 ?? '',
                'postal_code' => $g->postal_code ?? '',
                'country' => $g->country ?? 'India',
                'gstin' => $g->gstin ?? '',
            ];
        });

        return view('groups.index', [
            'title' => 'Groups',
            'groups' => $groups,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function groupsCreate(): View
    {
        return view('groups.create', ['title' => 'New Group']);
    }

    public function groupsStore(Request $request)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:150',
            'email' => 'nullable|email',
            'address_line_1' => 'nullable|string|max:150',
            'address_line_2' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'gstin' => 'nullable|string|max:20',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $userAccountId;

        Group::create($validated);

        return redirect()->back()->with('success', 'Group created successfully.')->with('open_group_modal', true);
    }

    public function groupsShow(Group $group): View
    {
        return view('groups.show', [
            'title' => 'Group Details',
            'group' => $group,
        ]);
    }

    public function groupsEdit(Group $group): View
    {
        return view('groups.edit', ['title' => 'Edit Group', 'group' => $group]);
    }

    public function groupsUpdate(Request $request, $id)
    {
        $group = Group::where('groupid', $id)->firstOrFail();

        $validated = $request->validate([
            'group_name' => 'required|string|max:150',
            'email' => 'nullable|email',
            'address_line_1' => 'nullable|string|max:150',
            'address_line_2' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'gstin' => 'nullable|string|max:20',
        ]);

        $group->update($validated);

        return redirect()->back()->with('success', 'Group updated successfully.')->with('open_group_modal', true);
    }

    public function groupsDestroy(Group $group)
    {
        $group->delete();

        return redirect()->back()->with('success', 'Group deleted successfully.')->with('open_group_modal', true);
    }

    // Settings CRUD
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

    // Clients CRUD
    public function clientsCreate(): View
    {
        $accountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $billingProfiles = ClientBillingDetail::query()
            ->where('accountid', $accountId)
            ->orderBy('business_name')
            ->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        return view('clients.create', [
            'title' => 'New Client',
            'accounts' => Account::where('status', 'active')->get(),
            'groups' => Group::all(),
            'billingProfiles' => $billingProfiles,
            'currencies' => $currencies,
        ]);
    }

    public function clientsStore(Request $request)
    {
        $userAccountId = auth()->check() ? auth()->user()->accountid ?? 'ACC0000001' : 'ACC0000001';

        $validated = $request->validate([
            'accountid' => 'required|exists:accounts,accountid|size:10',
            'business_name' => 'required|string|max:255',
            'groupid' => 'nullable|exists:groups,groupid',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'in:active,review,inactive',
            'currency' => 'required|string|size:3|exists:currency,iso',
            // Client Details (address)
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string|max:150',
            // Billing Details (shared profile)
            'existing_bd_id' => 'nullable|string|size:6|exists:client_billing_details,bd_id',
            'billing_business_name' => 'required|string|max:150',
            'billing_gstin' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_address_line_1' => 'nullable|string|max:150',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            // Add /public/ before /storage/ to match subdirectory setup
            $baseUrl = rtrim(config('app.url'), '/');
            $validated['logo_path'] = $baseUrl . '/public/storage/' . $path;
        }

        // Override with user context if not provided
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

        $selectedBillingDetail = null;

        if (! empty($validated['existing_bd_id'])) {
            $selectedBillingDetail = ClientBillingDetail::query()
                ->where('bd_id', $validated['existing_bd_id'] ?? '')
                ->where('accountid', $validated['accountid'])
                ->first();

            if (! $selectedBillingDetail) {
                return back()->withInput()->withErrors([
                    'existing_bd_id' => 'Selected billing details are invalid for this account.',
                ]);
            }

            $selectedBillingDetail->update([
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ]);
        } else {
            $selectedBillingDetail = ClientBillingDetail::create([
                'bd_id' => Group::generateUniqueAlphaId(new Group()),
                'accountid' => $validated['accountid'],
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ]);
        }

        // Create client with selected billing profile bd_id
        $validated['bd_id'] = $selectedBillingDetail->bd_id;
        $clientData = collect($validated)->except([
            'existing_bd_id',
            'billing_business_name',
            'billing_gstin',
            'billing_email',
            'billing_city',
            'billing_state',
            'billing_country',
            'billing_postal_code',
            'billing_address_line_1',
        ])->all();
        Client::create($clientData);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function clientsShow(Client $client): View
    {
        $client->load(['invoices', 'payments', 'subscriptions']);
        $outstanding = ($client->invoices->sum('total') ?? 0) - ($client->payments->sum('amount') ?? 0);

        return view('clients.show', [
            'title' => 'Client Details',
            'client' => $client,
            'outstanding' => $outstanding,
        ]);
    }

    public function clientsEdit(Client $client): View
    {
        $accountId = $client->accountid ?: (auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001');
        $billingProfiles = ClientBillingDetail::query()
            ->where('accountid', $accountId)
            ->orderBy('business_name')
            ->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        return view('clients.edit', [
            'title' => 'Edit Client', 
            'client' => $client,
            'accounts' => Account::where('status', 'active')->get(),
            'groups' => Group::all(),
            'billingProfiles' => $billingProfiles,
            'currencies' => $currencies,
        ]);
    }

    public function clientsUpdate(Request $request, Client $client)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'groupid' => 'nullable|exists:groups,groupid',
            'contact_name' => 'nullable|string|max:255',
'email' => 'required|email|unique:clients,email,' . $client->getKey() . ',clientid',
            'phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'in:active,review,inactive',
            'currency' => 'required|string|size:3|exists:currency,iso',
            // Client Details (address)
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string|max:150',
            // Billing Details (shared profile)
            'existing_bd_id' => 'nullable|string|size:6|exists:client_billing_details,bd_id',
            'billing_business_name' => 'required|string|max:150',
            'billing_gstin' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_address_line_1' => 'nullable|string|max:150',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if it exists
            if ($client->logo_path) {
                // Since we store the full URL, we need to extract the relative path
                // URL structure: base_url/public/storage/logos/filename.jpg
                $storageBase = rtrim(config('app.url'), '/') . '/public/storage/';
                $oldPath = str_replace($storageBase, '', $client->logo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $baseUrl = rtrim(config('app.url'), '/');
            $validated['logo_path'] = $baseUrl . '/public/storage/' . $path;
        }

        $selectedBdId = $client->bd_id;

        if (! empty($validated['existing_bd_id'])) {
            $existingBillingDetail = ClientBillingDetail::query()
                ->where('bd_id', $validated['existing_bd_id'] ?? '')
                ->where('accountid', $client->accountid)
                ->first();

            if (! $existingBillingDetail) {
                return back()->withInput()->withErrors([
                    'existing_bd_id' => 'Selected billing details are invalid for this account.',
                ]);
            }

            $existingBillingDetail->update([
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ]);
            $selectedBdId = $existingBillingDetail->bd_id;
        } else {
            $billingData = [
                'accountid' => $client->accountid,
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ];

            $currentUsageCount = Client::where('bd_id', $client->bd_id)->count();
            if ($client->billingDetail && $currentUsageCount <= 1) {
                $client->billingDetail->update($billingData);
                $selectedBdId = $client->billingDetail->bd_id;
            } else {
                $newBillingDetail = ClientBillingDetail::create(array_merge($billingData, [
                    'bd_id' => Group::generateUniqueAlphaId(new Group()),
                ]));
                $selectedBdId = $newBillingDetail->bd_id;
            }
        }

        $validated['bd_id'] = $selectedBdId;
        $clientData = collect($validated)->except([
            'existing_bd_id',
            'billing_business_name',
            'billing_gstin',
            'billing_email',
            'billing_city',
            'billing_state',
            'billing_country',
            'billing_postal_code',
            'billing_address_line_1',
        ])->all();
        $client->update($clientData);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function clientsDestroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    // Subscriptions CRUD
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

    // quotations CRUD
    public function quotationsCreate(): View
    {
        return view('quotations.create', [
            'title' => 'New Quotation',
            'clients' => Client::all(),
        ]);
    }

    public function quotationsStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'quotation_number' => 'required|string|unique:quotations,quotation_number',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'accountid' => 'nullable|size:10',
            'status' => 'required|in:draft,sent,accepted,declined,expired',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

        Quotation::create($validated);

        return redirect()->route('quotations.index')->with('success', 'Quotation created successfully.');
    }

    // Invoices CRUD
    public function invoicesCreate(): View
    {
        return view('invoices.create', [
            'title' => 'Create Invoice',
            'clients' => Client::all(),
            'services' => Service::orderBy('sequence')->orderBy('name')->get(),
        ]);
    }

    public function invoicesStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:invoices,pi_number',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax_total' => 'required|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'items_data' => 'required|json',
            'accountid' => 'nullable|size:10',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;
        unset($validated['items_data']);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($itemsData as $itemData) {
            $subtotal += $itemData['line_total'];
            $taxTotal += $itemData['line_total'] * ($itemData['tax_rate'] / 100);
        }
        $grandTotal = $subtotal + $taxTotal;
        $validated['subtotal'] = $subtotal;
        $validated['tax_total'] = $taxTotal;
        $validated['grand_total'] = $grandTotal;
        $validated['balance_due'] = $grandTotal;

        $invoice = Invoice::create($validated);

        foreach ($itemsData as $index => $itemData) {
            $service = Service::find($itemData['serviceid']);
            InvoiceItem::create([
                'invoiceid' => $invoice->invoiceid,
                'serviceid' => $itemData['serviceid'],
                'item_name' => $service?->name ?? 'Custom Service Item',
                'item_description' => null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'],
                'taxid' => $itemData['taxid'] ?? null,
                'line_total' => $itemData['line_total'],
                'sort_order' => $index + 1,
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully with items.');
    }

    public function invoicesShow(Invoice $invoice): View
    {
        $invoice->load(['client', 'items.service', 'payments']);
        return view('invoices.show', ['title' => 'Invoice Details', 'invoice' => $invoice]);
    }

public function invoicesEdit(Invoice $invoice): View
    {
        $invoice->load(['items.service']);
        return view('invoices.edit', [
            'title' => 'Edit Invoice',
            'invoice' => $invoice,
            'clients' => Client::all(),
            'services' => Service::orderBy('sequence')->orderBy('name')->get(),
            'items' => $invoice->items
        ]);
    }

    public function quotationsEdit(Quotation $quotation): View
    {
        return view('quotations.edit', [
            'title' => 'Edit Quotation',
            'quotation' => $quotation,
            'clients' => Client::all()
        ]);
    }

    public function subscriptionsEdit(Subscription $subscription): View
    {
        return view('subscriptions.edit', [
            'title' => 'Edit Subscription',
            'subscription' => $subscription,
            'clients' => Client::all(),
            'services' => Service::where('billing_type', 'recurring')->orderBy('sequence')->orderBy('name')->get()
        ]);
    }

    // Payments CRUD
    public function paymentsCreate(): View
    {
        return view('payments.create', [
            'title' => 'New Payment',
            'clients' => Client::all(),
            'invoices' => Invoice::where('status', '!=', 'paid')->get(),
        ]);
    }

    public function paymentsStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        
        $paymentData = [
            'accountid' => $userAccountId,
            'clientid' => $validated['clientid'],
            'invoiceid' => $validated['invoiceid'],
            'payment_number' => 'PAY-' . strtoupper(bin2hex(random_bytes(3))),
            'payment_date' => $validated['paid_at'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['method'],
            'reference_number' => $validated['reference'],
            'notes' => $validated['notes'],
            'status' => 'completed',
            'received_by' => (auth()->user() instanceof \App\Models\User) ? auth()->id() : null,
        ];

        $payment = Payment::create($paymentData);

        // Update Invoice balance if applicable
        if ($payment->invoiceid) {
            $invoice = Invoice::find($payment->invoiceid);
            if ($invoice) {
                $invoice->balance_due -= $payment->amount;
                $invoice->amount_paid += $payment->amount;
                if ($invoice->balance_due <= 0) {
                    $invoice->status = 'paid';
                    $invoice->balance_due = 0;
                    $invoice->paid_at = now();
                }
                $invoice->save();
            }
        }

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }

    public function paymentsShow(Payment $payment): View
    {
        $payment->load(['client', 'invoice']);
        return view('payments.show', [
            'title' => 'Payment Details',
            'payment' => $payment,
        ]);
    }

    public function paymentsEdit(Payment $payment): View
    {
        return view('payments.edit', [
            'title' => 'Edit Payment',
            'payment' => $payment,
            'clients' => Client::all(),
            'invoices' => Invoice::all(),
        ]);
    }

    public function paymentsUpdate(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $payment->update([
            'clientid' => $validated['clientid'],
            'invoiceid' => $validated['invoiceid'],
            'payment_date' => $validated['paid_at'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['method'],
            'reference_number' => $validated['reference'],
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('payments.index')->with('success', 'Payment updated successfully.');
    }

    public function paymentsDestroy(Payment $payment)
    {
        // If it was linked to an invoice, we should ideally revert the balance_due.
        if ($payment->invoiceid) {
            $invoice = Invoice::find($payment->invoiceid);
            if ($invoice) {
                $invoice->balance_due += $payment->amount;
                if ($invoice->balance_due > 0 && $invoice->status == 'paid') {
                    $invoice->status = 'sent'; // Or whatever was previous
                }
                $invoice->save();
            }
        }

        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }

    public function subscriptionsShow(Subscription $subscription): View
    {
        $subscription->load('client', 'service');
        return view('subscriptions.show', [
            'title' => 'Subscription Details',
            'subscription' => $subscription,
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

    // Complete quotations CRUD
    public function quotationsShow(Quotation $quotation): View
    {
        $quotation->load('client');
        return view('quotations.show', [
            'title' => 'Quotation Details',
            'quotation' => $quotation,
        ]);
    }

    public function quotationsUpdate(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'quotation_number' => 'required|string|unique:quotations,quotation_number,' . $quotation->getKey() . ',quotationid',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'required|in:draft,sent,accepted,declined,expired',
        ]);

        $quotation->update($validated);

        return redirect()->route('quotations.index')->with('success', 'Quotation updated successfully.');
    }

    public function quotationsDestroy(Quotation $quotation)
    {
        $quotation->delete();

        return redirect()->route('quotations.index')->with('success', 'Quotation deleted successfully.');
    }

    // Complete Invoices CRUD
    public function invoicesUpdate(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:invoices,pi_number,' . $invoice->invoiceid . ',invoiceid',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($itemsData as $itemData) {
            $subtotal += $itemData['line_total'];
            $taxTotal += $itemData['line_total'] * ($itemData['tax_rate'] / 100);
        }
        $grandTotal = $subtotal + $taxTotal;

        $invoice->update([
            'clientid' => $validated['clientid'],
            'invoice_number' => $validated['invoice_number'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'],
            'status' => $validated['status'],
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'balance_due' => $grandTotal, // Simple logic: reset balance due on update. In a real app, you might want to subtract payments already made.
        ]);

        // Delete old items and recreate
        $invoice->items()->delete();

        foreach ($itemsData as $index => $itemData) {
            $service = Service::find($itemData['serviceid']);
            InvoiceItem::create([
                'invoiceid' => $invoice->invoiceid,
                'serviceid' => $itemData['serviceid'],
                'item_name' => $service?->name ?? 'Custom Service Item',
                'item_description' => null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'],
                'taxid' => $itemData['taxid'] ?? null,
                'line_total' => $itemData['line_total'],
                'sort_order' => $index + 1,
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function invoicesDestroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }
}
