<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingUiController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::controller(BillingUiController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
        
        // Others (Existing CRUDs)
        Route::get('/clients', 'clients')->name('clients.index');
        Route::get('/clients/create', 'clientsCreate')->name('clients.create');
        Route::post('/clients', 'clientsStore')->name('clients.store');
        Route::get('/clients/{client}', 'clientsShow')->name('clients.show');
        Route::get('/clients/{client}/edit', 'clientsEdit')->name('clients.edit');
        Route::put('/clients/{client}', 'clientsUpdate')->name('clients.update');
        Route::delete('/clients/{client}', 'clientsDestroy')->name('clients.destroy');
        
        Route::get('/services', 'services')->name('services.index');
        Route::get('/services/create', 'servicesCreate')->name('services.create');
        Route::post('/services', 'servicesStore')->name('services.store');
        Route::get('/services/{service}', 'servicesShow')->name('services.show');
        Route::get('/services/{service}/edit', 'servicesEdit')->name('services.edit');
        Route::put('/services/{service}', 'servicesUpdate')->name('services.update');
        Route::delete('/services/{service}', 'servicesDestroy')->name('services.destroy');
        
        Route::get('/invoices', 'invoices')->name('invoices.index');
        Route::get('/invoices/create', 'invoicesCreate')->name('invoices.create');
        Route::post('/invoices', 'invoicesStore')->name('invoices.store');
        Route::get('/invoices/{invoice}', 'invoicesShow')->name('invoices.show');
        Route::get('/invoices/{invoice}/edit', 'invoicesEdit')->name('invoices.edit');
        Route::put('/invoices/{invoice}', 'invoicesUpdate')->name('invoices.update');
        Route::delete('/invoices/{invoice}', 'invoicesDestroy')->name('invoices.destroy');
        
        Route::get('/payments', 'payments')->name('payments.index');
        Route::get('/payments/create', 'paymentsCreate')->name('payments.create');
        Route::post('/payments', 'paymentsStore')->name('payments.store');
        Route::get('/payments/{payment}', 'paymentsShow')->name('payments.show');
        Route::get('/payments/{payment}/edit', 'paymentsEdit')->name('payments.edit');
        Route::put('/payments/{payment}', 'paymentsUpdate')->name('payments.update');
        Route::delete('/payments/{payment}', 'paymentsDestroy')->name('payments.destroy');
        
        Route::get('/subscriptions', 'subscriptions')->name('subscriptions.index');
        Route::get('/subscriptions/create', 'subscriptionsCreate')->name('subscriptions.create');
        Route::post('/subscriptions', 'subscriptionsStore')->name('subscriptions.store');
        Route::get('/subscriptions/{subscription}', 'subscriptionsShow')->name('subscriptions.show');
        Route::get('/subscriptions/{subscription}/edit', 'subscriptionsEdit')->name('subscriptions.edit');
        Route::put('/subscriptions/{subscription}', 'subscriptionsUpdate')->name('subscriptions.update');
        Route::delete('/subscriptions/{subscription}', 'subscriptionsDestroy')->name('subscriptions.destroy');
        
        Route::get('/estimates', 'estimates')->name('estimates.index');
        Route::get('/estimates/create', 'estimatesCreate')->name('estimates.create');
        Route::post('/estimates', 'estimatesStore')->name('estimates.store');
        Route::get('/estimates/{estimate}', 'estimatesShow')->name('estimates.show');
        Route::get('/estimates/{estimate}/edit', 'estimatesEdit')->name('estimates.edit');
        Route::put('/estimates/{estimate}', 'estimatesUpdate')->name('estimates.update');
        Route::delete('/estimates/{estimate}', 'estimatesDestroy')->name('estimates.destroy');
        
        Route::get('/settings', 'settings')->name('settings.index');
        Route::put('/settings/agency', 'agencyUpdate')->name('agency.update');
        Route::get('/settings/create', 'settingsCreate')->name('settings.create');
        Route::post('/settings', 'settingsStore')->name('settings.store');
        Route::get('/settings/{setting}', 'settingsShow')->name('settings.show');
        Route::get('/settings/{setting}/edit', 'settingsEdit')->name('settings.edit');
        Route::put('/settings/{setting}', 'settingsUpdate')->name('settings.update');
        Route::delete('/settings/{setting}', 'settingsDestroy')->name('settings.destroy');
    });
});
