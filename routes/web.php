<?php

use App\Http\Controllers\AccountDepartmentController;
use App\Http\Controllers\AccountPolicyController;
use App\Http\Controllers\AccountRoleController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingUiController;
use App\Http\Controllers\ClientCategoriesController;
use App\Http\Controllers\ClientContactsController;
use App\Http\Controllers\ClientDocumentsController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\PayrollComponentController;
use App\Http\Controllers\ProductCategoriesController;
use App\Http\Controllers\ProfileApprovalsController;
use App\Http\Controllers\QuotationsController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TeamManagementController;
use App\Http\Controllers\UsersController;
use App\Http\Middleware\EnsureAppAccess;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return redirect(config('app.team_url').'/login');
})->name('login')->middleware('guest');
Route::match(['get', 'post'], '/logout', function () {
    return redirect(config('app.team_url').'/logout');
})->name('logout')->middleware('auth');

Route::get('/invoices/{invoice}/pdf/share', [InvoicesController::class, 'sharePdf'])->name('invoices.pdf.share');
// AJAX routes without auth

Route::middleware(['auth', EnsureAppAccess::class])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard')->middleware('permission:dashboard.view');
    });

    Route::controller(ClientsController::class)->middleware('permission:clients.view')->group(function () {
        Route::get('/client-dashboard/{client?}', 'clientDashboard')->name('clients.dashboard');
        Route::get('/clients', 'clients')->name('clients.index');
        Route::get('/clients/trials', 'trialClients')->name('clients.trials');
        Route::get('/clients/create', 'clientsCreate')->name('clients.create');
        Route::post('/clients', 'clientsStore')->name('clients.store');
        Route::post('/clients/ajax-save-info', 'clientsSaveInfoAjax')->name('clients.ajax-save-info');
        Route::patch('/clients/{client}/convert-to-regular', 'convertTrialToRegular')->name('clients.convert-to-regular');
        Route::controller(ClientDocumentsController::class)->group(function () {
            Route::get('/clients/{client}/documents/create', 'create')->name('clients.documents.create');
            Route::get('/clients/{client}/documents', 'list')->name('clients.documents.list');
            Route::post('/clients/{client}/documents', 'store')->name('clients.documents.store');
            Route::put('/clients/{client}/documents/{document}', 'update')->name('clients.documents.update');
            Route::delete('/clients/{client}/documents/{document}', 'destroy')->name('clients.documents.delete');
            Route::patch('/clients/{client}/documents/{document}/cancel', 'cancel')->name('clients.documents.cancel');
            Route::patch('/clients/{client}/documents/{document}/restore', 'restore')->name('clients.documents.restore');
            Route::get('/clients/{client}/documents/{document}/file', 'file')->name('clients.documents.file');
        });

        Route::controller(ClientContactsController::class)->group(function () {
            Route::post('/clients/{client}/contacts/ajax-save', 'saveAjax')->name('clients.contacts.ajax-save');
            Route::delete('/clients/{client}/contacts/{contact}/ajax-delete', 'deleteAjax')->name('clients.contacts.ajax-delete');
        });
        Route::get('/clients/{client}/edit', 'clientsEdit')->name('clients.edit');
        Route::put('/clients/{client}', 'clientsUpdate')->name('clients.update');
        Route::delete('/clients/{client}', 'clientsDestroy')->name('clients.destroy');
        Route::patch('/clients/{client}/toggle-status', 'toggleClientStatus')->name('clients.toggle-status');
    });

    Route::controller(GroupsController::class)->middleware('permission:clients.view')->group(function () {
        Route::post('/groups', 'groupsStore')->name('groups.store');
        Route::put('/groups/{group}', 'groupsUpdate')->name('groups.update');
        Route::delete('/groups/{group}', 'groupsDestroy')->name('groups.destroy');
    });

    Route::controller(ClientCategoriesController::class)->middleware('permission:clients.view')->group(function () {
        Route::post('/client-categories', 'store')->name('client-categories.store');
        Route::put('/client-categories/{category}', 'update')->name('client-categories.update');
        Route::delete('/client-categories/{category}', 'destroy')->name('client-categories.destroy');
        Route::patch('/client-categories/{category}/sequence', 'updateSequence')->name('client-categories.update-sequence');
    });

    Route::controller(ServicesController::class)->middleware('permission:items.view')->group(function () {
        Route::get('/services', 'services')->name('services.index');
        Route::get('/services/create', 'servicesCreate')->name('services.create');
        Route::post('/services', 'servicesStore')->name('services.store');
        Route::post('/services/reorder', 'servicesReorder')->name('services.reorder');
        Route::get('/services/{service}/edit', 'servicesEdit')->name('services.edit');
        Route::put('/services/{service}', 'servicesUpdate')->name('services.update');
        Route::delete('/services/{service}', 'servicesDestroy')->name('services.destroy');
        Route::post('/services/ajax-save', 'servicesSaveAjax')->name('services.ajax-save');
    });

    Route::controller(InvoicesController::class)->middleware('permission:invoices.view')->group(function () {
        Route::get('/invoices', 'invoices')->name('invoices.index');
        Route::get('/invoices/create', 'invoicesCreate')->name('invoices.create');
        Route::post('/invoices/client-orders', 'getClientOrders')->name('invoices.client-orders');
        Route::get('/invoices/client-order-items', 'getClientOrderItems')->name('invoices.client-order-items');
        Route::post('/invoices/renewal-invoices', 'getRenewalInvoices')->name('invoices.renewal-invoices');
        Route::post('/invoices/terms/billing', 'storeBillingTerm')->name('invoices.terms.billing.store');
        Route::patch('/invoices/{invoice}/terms', 'applyTerms')->name('invoices.apply-terms');
        Route::get('/invoices/order-items/{orderid}', 'getOrderItems')->name('invoices.order-items');
        Route::get('/invoices/renewal-items/{invoiceid}', 'getRenewalItems')->name('invoices.renewal-items');
        Route::post('/invoices/draft', 'invoicesSaveDraft')->name('invoices.save-draft');
        Route::get('/invoices/draft/{clientid}', 'invoicesGetDraft')->name('invoices.get-draft');
        Route::post('/invoices', 'invoicesStore')->name('invoices.store');
        Route::post('/invoices/create-tax-invoice', 'createTaxInvoice')->name('invoices.create-tax-invoice');
        Route::get('/invoices/{invoice}/email-compose', 'emailCompose')->name('invoices.email-compose');
        Route::post('/invoices/{invoice}/email-compose', 'emailComposeStore')->name('invoices.email-compose.store');
        Route::post('/invoices/{invoice}/send-reminder', 'sendReminder')->name('invoices.send-reminder');
        Route::post('/invoices/{invoice}/items/{item}/send-reminder', 'sendItemReminder')->name('invoices.items.send-reminder');
        Route::get('/invoices/items/{item}/renew', 'startRenewalFromItem')->name('invoices.items.renew');
        Route::patch('/invoices/{invoice}/items/{item}/suspend', 'suspendInvoiceItem')->name('invoices.items.suspend');
        Route::patch('/invoices/{invoice}/items/{item}/unsuspend', 'unsuspendInvoiceItem')->name('invoices.items.unsuspend');
        Route::patch('/invoices/orders/{order}/suspend', 'suspendExpiryOrder')->name('invoices.orders.suspend');
        Route::patch('/invoices/orders/{order}/unsuspend', 'unsuspendExpiryOrder')->name('invoices.orders.unsuspend');
        Route::patch('/invoices/orders/{order}/renew', 'renewExpiryOrder')->name('invoices.orders.renew');
        Route::post('/invoices/orders/{order}/send-reminder', 'sendExpiryOrderReminder')->name('invoices.orders.send-reminder');
        Route::get('/invoices/expiry-list', 'invoicesExpiryList')->name('invoices.expiry-list');
        Route::get('/invoices/{invoice}/pdf-versions', 'pdfVersions')->name('invoices.pdf-versions');
        Route::get('/invoices/{invoice}/pdf', 'downloadPdf')->name('invoices.pdf');
        Route::get('/invoices/{invoice}/edit', 'invoicesEdit')->name('invoices.edit');
        Route::patch('/invoices/{invoice}/items/{item}', 'invoicesUpdateItem')->name('invoices.items.update');
        Route::put('/invoices/{invoice}', 'invoicesUpdate')->name('invoices.update');
        Route::patch('/invoices/{invoice}/restore', 'invoicesRestore')->name('invoices.restore');
        Route::delete('/invoices/{invoice}', 'invoicesDestroy')->name('invoices.destroy');
    });

    Route::controller(PaymentsController::class)->middleware('permission:payments.view')->group(function () {
        Route::get('/payments', 'payments')->name('payments.index');
        Route::get('/payments/ledger', 'paymentsLedger')->name('payments.ledger');
        Route::get('/gst-report', 'paymentsGstReport')->name('gst-report.index');
        Route::get('/payments/create', 'paymentsCreate')->name('payments.create');
        Route::post('/payments', 'paymentsStore')->name('payments.store');
        Route::get('/payments/{payment}', 'paymentsShow')->name('payments.show');
        Route::get('/payments/{payment}/edit', 'paymentsEdit')->name('payments.edit');
        Route::put('/payments/{payment}', 'paymentsUpdate')->name('payments.update');
        Route::patch('/payments/{payment}/restore', 'paymentsRestore')->name('payments.restore');
        Route::delete('/payments/{payment}', 'paymentsDestroy')->name('payments.destroy');
    });

    Route::controller(QuotationsController::class)->middleware('permission:quotations.view')->group(function () {
        Route::get('/quotations', 'quotations')->name('quotations.index');
        Route::get('/quotations/create', 'quotationsCreate')->name('quotations.create');
        Route::post('/quotations/step2-draft', 'saveStep2Draft')->name('quotations.step2-draft');
        Route::patch('/quotations/{quotation}/terms', 'applyTerms')->name('quotations.apply-terms');
        Route::post('/quotations', 'quotationsStore')->name('quotations.store');
        Route::get('/quotations/{quotation}/pdf', 'quotationPdf')->name('quotations.pdf');
        Route::get('/quotations/{quotation}/pdf-versions', 'quotationPdfVersions')->name('quotations.pdf-versions');
        Route::get('/quotations/{quotation}/email-compose', 'quotationEmailCompose')->name('quotations.email-compose');
        Route::post('/quotations/{quotation}/email-compose', 'quotationEmailComposeStore')->name('quotations.email-compose.store');
        Route::post('/quotations/{quotation}/copy', 'quotationsCopy')->name('quotations.copy');
        Route::delete('/quotations/{quotation}', 'quotationsDestroy')->name('quotations.destroy');
    });

    Route::controller(ProductCategoriesController::class)->middleware('permission:items.view')->group(function () {
        Route::post('/product-categories', 'productCategoriesStore')->name('product-categories.store');
        Route::put('/product-categories/{productCategory}', 'productCategoriesUpdate')->name('product-categories.update');
        Route::delete('/product-categories/{productCategory}', 'productCategoriesDestroy')->name('product-categories.destroy');
    });

    Route::controller(UsersController::class)->middleware('permission:users.view')->group(function () {
        Route::get('/users', 'users')->name('users.index');
        Route::get('/users/create', 'usersCreate')->name('users.create');
        Route::post('/users', 'usersStore')->name('users.store');
        Route::get('/users/leaves', 'unassignedLeaves')->name('users.leaves.index');
        Route::post('/users/leaves/{leave}/action', 'approveRejectLeave')->name('users.leaves.action');
        Route::get('/users/{user}/edit', 'usersEdit')->name('users.edit');
        Route::put('/users/{user}', 'usersUpdate')->name('users.update');
        Route::patch('/users/{user}/toggle-status', 'usersToggleStatus')->name('users.toggle-status');
        Route::delete('/users/{user}', 'usersDestroy')->name('users.destroy');
        Route::get('/users/{user}/assignments', 'getAssignments')->name('users.assignments.get');
        Route::post('/users/{user}/assignments', 'updateAssignments')->name('users.assignments.update');
    });

    Route::controller(ProfileApprovalsController::class)->middleware('permission:users.view')->group(function () {
        Route::get('/users/approvals', 'index')->name('users.approvals');
        Route::put('/users/approvals/{profileid}/approve', 'approve')->name('users.approvals.approve');
        Route::put('/users/approvals/{profileid}/reject', 'reject')->name('users.approvals.reject');
    });

    // Route::post('team/{employee}/login-as', [TeamManagementController::class, 'loginAs'])->name('team.loginAs');

    Route::get('team', [TeamManagementController::class, 'index'])->name('team.index');

    Route::resource('shifts', ShiftController::class)->except(['create', 'show', 'edit']);
    Route::patch('/shifts/{shift}/toggle', [ShiftController::class, 'toggleStatus'])->name('shifts.toggle-status');

    Route::resource('payroll-components', PayrollComponentController::class)->except(['create', 'show', 'edit']);
    Route::patch('/payroll-components/{component}/toggle', [PayrollComponentController::class, 'toggleStatus'])->name('payroll-components.toggle-status');

    Route::resource('account-policies', AccountPolicyController::class)->except(['create', 'show', 'edit']);
    Route::patch('/account-policies/{policy}/toggle', [AccountPolicyController::class, 'toggleStatus'])->name('account-policies.toggle-status');

    Route::controller(AccountRoleController::class)->middleware('permission:users.view')->group(function () {
        Route::get('/roles', 'index')->name('roles.index');
        Route::post('/roles', 'store')->name('roles.store');
        Route::put('/roles/{role}', 'update')->name('roles.update');
        Route::delete('/roles/{role}', 'destroy')->name('roles.destroy');
        Route::patch('/roles/{role}/toggle', 'toggleStatus')->name('roles.toggle-status');
    });

    Route::controller(AccountDepartmentController::class)->middleware('permission:users.view')->group(function () {
        Route::get('/departments', 'index')->name('departments.index');
        Route::post('/departments', 'store')->name('departments.store');
        Route::put('/departments/{department}', 'update')->name('departments.update');
        Route::delete('/departments/{department}', 'destroy')->name('departments.destroy');
        Route::patch('/departments/{department}/toggle', 'toggleStatus')->name('departments.toggle-status');
    });

    Route::controller(OrdersController::class)->middleware('permission:orders.view')->group(function () {
        // Specific routes first
        Route::get('/orders/json', 'getOrderJsonByNumber')->name('orders.json-by-number');
        Route::get('/orders', 'orders')->name('orders.index');
        Route::get('/orders/create', 'ordersCreate')->name('orders.create');
        Route::get('/orders/{order}/file/{type}', 'ordersFile')->name('orders.file');
        // Parameterized routes
        Route::get('/orders/{order}/json', 'getOrderJson')->name('orders.json');
        Route::get('/orders/{order}/timeline', 'ordersTimelineAjax')->name('orders.timeline');
        Route::get('/orders/{order}/edit', 'ordersEdit')->name('orders.edit');
        Route::post('/orders', 'ordersStore')->name('orders.store');
        Route::put('/orders/{order}', 'ordersUpdate')->name('orders.update');
        Route::patch('/orders/{order}/restore', 'ordersRestore')->name('orders.restore');
        Route::delete('/orders/{order}', 'ordersDestroy')->name('orders.destroy');
        Route::delete('/orders/{order}/force-delete', 'ordersForceDelete')->name('orders.force-delete');
        Route::patch('/orders/{order}/convert-to-regular', 'convertOrderToRegular')->name('orders.convert-to-regular');
    });

    Route::post('/message-templates/refresh', [SettingsController::class, 'refreshTemplate'])->name('message-templates.refresh');

    Route::controller(SettingsController::class)->middleware('permission:settings.view')->group(function () {
        Route::post('/settings/fy-prefix', 'fyPrefixUpdate')->name('settings.fy-prefix.update');
        Route::get('/settings', 'settings')->name('settings.index');
        Route::post('/financial-year/select', 'financialYearSelect')->name('financial-year.select');
        Route::put('/settings/account', 'accountUpdate')->name('account.update');
        Route::post('/settings/fixed-tax', 'fixedTaxUpdate')->name('account.fixed-tax.update');
        Route::post('/settings/serial-config', 'serialConfigUpdate')->name('serial.config.update');
        Route::post('/settings/billing-details', 'accountBillingUpdate')->name('account.billing.update');
        Route::get('/settings/create', 'settingsCreate')->name('settings.create');
        Route::post('/settings', 'settingsStore')->name('settings.store');
        Route::get('/settings/{setting}/edit', 'settingsEdit')->name('settings.edit');
        Route::put('/settings/{setting}', 'settingsUpdate')->name('settings.update');
        Route::delete('/settings/{setting}', 'settingsDestroy')->name('settings.destroy');
        Route::put('/settings/financial-year/{financialYear}/default', 'financialYearSetDefault')->name('financial-year.default');
        Route::post('/settings/financial-year', 'financialYearUpdate')->name('financial-year.update');
        Route::post('/settings/terms-conditions', 'termsConditionsStore')->name('terms-conditions.store');
        Route::patch('/settings/terms-conditions/{term}/sequence', 'termsConditionsUpdateSequence')->name('terms-conditions.update-sequence');
        Route::delete('/settings/terms-conditions/{term}', 'termsConditionsDestroy')->name('terms-conditions.destroy');
        Route::patch('/settings/terms-conditions/{term}/toggle', 'termsConditionsToggle')->name('terms-conditions.toggle');
        Route::post('/settings/serial-preview', 'generateSerialPreview')->name('serial.preview');
        Route::post('/settings/taxes', 'taxStore')->name('taxes.store');
        Route::patch('/settings/taxes/{tax}', 'taxUpdate')->name('taxes.update');
        Route::delete('/settings/taxes/{tax}', 'taxDestroy')->name('taxes.destroy');
        Route::patch('/settings/taxes/{tax}/toggle', 'taxToggle')->name('taxes.toggle');
        Route::post('/settings/message-templates', 'messageTemplateStore')->name('message-templates.store');
        Route::patch('/settings/message-templates/{template}', 'messageTemplateUpdate')->name('message-templates.update');
        Route::patch('/settings/message-templates/{template}/toggle', 'messageTemplateToggle')->name('message-templates.toggle');
        Route::delete('/settings/message-templates/{template}', 'messageTemplateDestroy')->name('message-templates.destroy');
        Route::get('/settings/consolidated-preview', 'consolidatedPreview')->name('settings.consolidated-preview');
        Route::post('/settings/consolidated-days', 'updateConsolidatedDays')->name('settings.consolidated-days.update');
        Route::get('/settings/consolidated-payment-preview', 'consolidatedPaymentPreview')->name('settings.consolidated-payment-preview');
        Route::post('/settings/consolidated-payment-days', 'updateConsolidatedPaymentDays')->name('settings.consolidated-payment-days.update');
        Route::post('/settings/holidays', 'holidayStore')->name('holidays.store');
        Route::post('/settings/holidays/bulk', 'holidayBulkStore')->name('holidays.bulk.store');
        Route::delete('/settings/holidays/{holidayid}', 'holidayDestroy')->name('holidays.destroy');
    });

    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::put('/change-password', [AuthController::class, 'changePassword'])->name('password.change.update');

    // // Keep terms conditions on legacy controller until moved fully.
    // Route::controller(BillingUiController::class)->group(function () {
    // });
    Route::post('/login-as/{user}', [AuthController::class, 'loginAs'])->name('login.as');
    Route::post('/leave-impersonation', [AuthController::class, 'leaveImpersonation'])->name('leave-impersonation');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/app-choice', [AuthController::class, 'appChoice'])->name('app.choice');
});

Route::middleware(['superadmin.auth'])->prefix('superadmin')->name('superadmin.')->controller(AccountsController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/create/step-1', 'storeStepOne')->name('store-step1');
    Route::get('/create/credentials', 'createCredentials')->name('create.credentials');
    Route::post('/', 'store')->name('store');
    Route::get('/{account}/edit', 'edit')->name('edit');
    Route::put('/{account}', 'update')->name('update');
    Route::patch('/{account}/toggle', 'toggleStatus')->name('toggle-status');
});
