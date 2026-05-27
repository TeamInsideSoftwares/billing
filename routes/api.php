<?php

use App\Http\Controllers\ClientsController;
use Illuminate\Support\Facades\Route;

Route::post('/add-client', [ClientsController::class, 'clientsStoreApi'])->name('api.clients.store');
Route::post('/clients', [ClientsController::class, 'clientsStoreApi'])->name('api.clients.store.v2');
