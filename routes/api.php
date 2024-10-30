<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/transactions', [\App\Http\Controllers\TransactionController::class, 'store']);
    Route::get('/balance', \App\Http\Controllers\GetBalance::class);
});
