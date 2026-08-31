<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Middleware\IdentifyTenant;
use Modules\Orcamento\App\Http\Controllers\OrcamentoController;

/*
|--------------------------------------------------------------------------
| Rotas Web do Modulo Orcamentos (Propostas Comerciais)
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', IdentifyTenant::class, 'module.enabled:orcamento'])
    ->prefix('orcamentos')
    ->name('orcamentos.')
    ->group(function () {
        Route::get('/', [OrcamentoController::class, 'index'])->name('index');
        Route::get('/create', [OrcamentoController::class, 'create'])->name('create');
        Route::post('/', [OrcamentoController::class, 'store'])->name('store');
        Route::get('/{orcamento}', [OrcamentoController::class, 'show'])->name('show');
        Route::get('/{orcamento}/edit', [OrcamentoController::class, 'edit'])->name('edit');
        Route::put('/{orcamento}', [OrcamentoController::class, 'update'])->name('update');
        Route::delete('/{orcamento}', [OrcamentoController::class, 'destroy'])->name('destroy')->middleware('role:manager');

        Route::post('/{orcamento}/enviar', [OrcamentoController::class, 'send'])->name('send');
        Route::post('/{orcamento}/aprovar', [OrcamentoController::class, 'approve'])->name('approve');
        Route::post('/{orcamento}/rejeitar', [OrcamentoController::class, 'reject'])->name('reject');
        Route::post('/{orcamento}/converter', [OrcamentoController::class, 'convert'])->name('convert');
    });
