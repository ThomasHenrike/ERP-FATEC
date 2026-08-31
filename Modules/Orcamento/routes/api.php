<?php

use Illuminate\Support\Facades\Route;
use Modules\Orcamento\App\Http\Controllers\OrcamentoController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('orcamentos', OrcamentoController::class)->names('orcamento');
});
