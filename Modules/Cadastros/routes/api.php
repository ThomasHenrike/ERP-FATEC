<?php

use Illuminate\Support\Facades\Route;
use Modules\Cadastros\Http\Controllers\CadastrosController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cadastros', CadastrosController::class)->names('cadastros');
});
