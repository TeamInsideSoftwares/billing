<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingUiController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\ProductCategoriesController;
use App\Http\Controllers\QuotationsController;
use App\Http\Controllers\SerialConfigurationsController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionsController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
    });

    Route::controller(ClientsController::class)->group(function () {
        Route::get('/clients', 'clients')->name('clients.index');
        Route::get('/clients/create', 'clientsCreate')->name('clients.create');
        Route::post('/clients', 'clientsStore')->name('clients.store');
        Route::get('/clients/{client}', 'clientsShow')->name('clients.show');
        Route::get('/clients/{client}/edit', 'clientsEdit')->name('clients.edit');
        Route::put('/clients/{client}', 'clientsUpdate')->name('clients.update');
        Route::delete('/clients/{client}', 'clientsDestroy')->name('clients.destroy');
    });

    Route::controller(ServicesController::class)->group(function () {
        Route::get('/services', 'services')->name('services.index');
        Route::get('/services/create', 'servicesCreate')->name('services.create');
        Route::post('/services', 'servicesStore')->name('services.store');
        Route::post('/services/reorder', 'servicesReorder')->name('services.reorder');
        Route::get('/services/{service}', 'servicesShow')->name('services.show');
        Route::get('/services/{service}/edit', 'servicesEdit')->name('services.edit');
        Route::put('/services/{service}', 'servicesUpdate')->name('services.update');
        Route::delete('/services/{service}', 'servicesDestroy')->name('services.destroy');
        Route::post('/services/ajax-save', 'servicesSaveAjax')->name('services.ajax-save');
    });

    Route::controller(InvoicesController::class)->group(function () {
        Route::get('/invoices', 'invoices')->name('invoices.index');
        Route::get('/invoices/create', 'invoicesCreate')->name('invoices.create');
        Route::post('/invoices/client-orders', 'getClientOrders')->name('invoices.client-orders');
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
        Route::get('/invoices/{invoice}/pdf', 'downloadPdf')->name('invoices.pdf');
        Route::get('/invoices/{invoice}', 'invoicesShow')->name('invoices.show');
        Route::get('/invoices/{invoice}/edit', 'invoicesEdit')->name('invoices.edit');
        Route::patch('/invoices/{invoice}/items/{item}', 'invoicesUpdateItem')->name('invoices.items.update');
        Route::put('/invoices/{invoice}', 'invoicesUpdate')->name('invoices.update');
        Route::delete('/invoices/{invoice}', 'invoicesDestroy')->name('invoices.destroy');
    });

    Route::controller(PaymentsController::class)->group(function () {
        Route::get('/payments', 'payments')->name('payments.index');
        Route::get('/payments/create', 'paymentsCreate')->name('payments.create');
        Route::post('/payments', 'paymentsStore')->name('payments.store');
        Route::get('/payments/{payment}', 'paymentsShow')->name('payments.show');
        Route::get('/payments/{payment}/edit', 'paymentsEdit')->name('payments.edit');
        Route::put('/payments/{payment}', 'paymentsUpdate')->name('payments.update');
        Route::delete('/payments/{payment}', 'paymentsDestroy')->name('payments.destroy');
    });

    Route::controller(SubscriptionsController::class)->group(function () {
        Route::get('/subscriptions', 'subscriptions')->name('subscriptions.index');
        Route::get('/subscriptions/create', 'subscriptionsCreate')->name('subscriptions.create');
        Route::post('/subscriptions', 'subscriptionsStore')->name('subscriptions.store');
        Route::get('/subscriptions/{subscription}', 'subscriptionsShow')->name('subscriptions.show');
        Route::get('/subscriptions/{subscription}/edit', 'subscriptionsEdit')->name('subscriptions.edit');
        Route::put('/subscriptions/{subscription}', 'subscriptionsUpdate')->name('subscriptions.update');
        Route::delete('/subscriptions/{subscription}', 'subscriptionsDestroy')->name('subscriptions.destroy');
    });

    Route::controller(QuotationsController::class)->group(function () {
        Route::get('/quotations', 'quotations')->name('quotations.index');
        Route::get('/quotations/create', 'quotationsCreate')->name('quotations.create');
        Route::post('/quotations', 'quotationsStore')->name('quotations.store');
        Route::get('/quotations/{quotation}', 'quotationsShow')->name('quotations.show');
        Route::get('/quotations/{quotation}/edit', 'quotationsEdit')->name('quotations.edit');
        Route::put('/quotations/{quotation}', 'quotationsUpdate')->name('quotations.update');
        Route::delete('/quotations/{quotation}', 'quotationsDestroy')->name('quotations.destroy');
    });

    Route::controller(ProductCategoriesController::class)->group(function () {
        Route::get('/product-categories', 'productCategories')->name('product-categories.index');
        Route::get('/product-categories/create', 'productCategoriesCreate')->name('product-categories.create');
        Route::post('/product-categories', 'productCategoriesStore')->name('product-categories.store');
        Route::get('/product-categories/{productCategory}', 'productCategoriesShow')->name('product-categories.show');
        Route::get('/product-categories/{productCategory}/edit', 'productCategoriesEdit')->name('product-categories.edit');
        Route::put('/product-categories/{productCategory}', 'productCategoriesUpdate')->name('product-categories.update');
        Route::delete('/product-categories/{productCategory}', 'productCategoriesDestroy')->name('product-categories.destroy');
    });

    Route::controller(GroupsController::class)->group(function () {
        Route::get('/groups', 'groups')->name('groups.index');
        Route::get('/groups/create', 'groupsCreate')->name('groups.create');
        Route::post('/groups', 'groupsStore')->name('groups.store');
        Route::get('/groups/{group}', 'groupsShow')->name('groups.show');
        Route::get('/groups/{group}/edit', 'groupsEdit')->name('groups.edit');
        Route::put('/groups/{group}', 'groupsUpdate')->name('groups.update');
        Route::delete('/groups/{group}', 'groupsDestroy')->name('groups.destroy');
    });

    Route::controller(OrdersController::class)->group(function () {
        // Specific routes first
        Route::get('/orders/select-client', 'selectClient')->name('orders.select-client');
        Route::get('/orders/json', 'getOrderJsonByNumber')->name('orders.json-by-number');
        Route::get('/orders', 'orders')->name('orders.index');
        Route::get('/orders/create', 'ordersCreate')->name('orders.create');
        // AJAX routes (before parameterized routes)
        Route::post('/orders/save-order', 'saveOrderAjax')->name('orders.save-ajax');
        Route::post('/orders/{order}/add-item', 'addOrderItemAjax')->name('orders.items.add');
        Route::post('/orders/{order}/update-item/{orderItemId}', 'updateOrderItemAjax')->name('orders.items.update');
        Route::delete('/orders/{order}/remove-item/{orderItemId}', 'deleteOrderItemAjax')->name('orders.items.delete');
        Route::get('/orders/{order}/file/{type}', 'ordersFile')->name('orders.file');
        // Parameterized routes
        Route::get('/orders/{order}', 'ordersShow')->name('orders.show');
        Route::get('/orders/{order}/json', 'getOrderJson')->name('orders.json');
        Route::get('/orders/{order}/edit', 'ordersEdit')->name('orders.edit');
        Route::post('/orders', 'ordersStore')->name('orders.store');
        Route::put('/orders/{order}', 'ordersUpdate')->name('orders.update');
        Route::patch('/orders/{order}/restore', 'ordersRestore')->name('orders.restore');
        Route::delete('/orders/{order}', 'ordersDestroy')->name('orders.destroy');
    });

Route::controller(SettingsController::class)->group(function () {
        Route::post('/settings/fy-prefix', 'fyPrefixUpdate')->name('settings.fy-prefix.update');
        Route::get('/settings', 'settings')->name('settings.index');
        Route::put('/settings/account', 'accountUpdate')->name('account.update');
        Route::post('/settings/fixed-tax', 'fixedTaxUpdate')->name('account.fixed-tax.update');
        Route::post('/settings/serial-config', 'serialConfigUpdate')->name('serial.config.update');
        Route::post('/settings/billing-details', 'accountBillingUpdate')->name('account.billing.update');
        Route::post('/settings/quotation-details', 'accountQuotationUpdate')->name('account.quotation.update');
        Route::get('/settings/create', 'settingsCreate')->name('settings.create');
        Route::post('/settings', 'settingsStore')->name('settings.store');
        Route::get('/settings/{setting}', 'settingsShow')->name('settings.show');
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
        Route::delete('/settings/message-templates/{template}', 'messageTemplateDestroy')->name('message-templates.destroy');
    });

    // // Keep terms conditions on legacy controller until moved fully.
    // Route::controller(BillingUiController::class)->group(function () {
    // });
});
