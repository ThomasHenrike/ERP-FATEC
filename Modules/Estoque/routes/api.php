<?php

use Illuminate\Support\Facades\Route;
use Modules\Estoque\Http\Controllers\EstoqueController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('estoques', EstoqueController::class)->names('estoque');
});
