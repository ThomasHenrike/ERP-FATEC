<?php

use Illuminate\Support\Facades\Route;
use Modules\Vendas\Http\Controllers\VendasController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('vendas', VendasController::class)->names('vendas');
});
