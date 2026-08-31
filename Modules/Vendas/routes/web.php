<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Middleware\IdentifyTenant;
use Modules\Vendas\App\Http\Controllers\CashShiftController;
use Modules\Vendas\App\Http\Controllers\PosController;
use Modules\Vendas\App\Http\Controllers\ReceivableController;
use Modules\Vendas\App\Http\Controllers\SaleController;

/*
|--------------------------------------------------------------------------
| Rotas Web do Modulo de Vendas, PDV, Turnos e Crediario
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', IdentifyTenant::class, 'module.enabled:vendas'])
    ->prefix('vendas')
    ->name('vendas.')
    ->group(function () {
        // PDV
        Route::get('pdv', [PosController::class, 'index'])->name('pos.index');
        Route::post('pdv/finalizar', [PosController::class, 'store'])->name('pos.store');
        Route::post('clientes/rapido', [PosController::class, 'quickCustomer'])->name('clientes.rapido');
        Route::get('recibo/{sale}', [PosController::class, 'receipt'])->name('pos.receipt');

        // Turnos de Caixa: abrir e' rotina do operador, sangria/suprimento e
        // fechamento exigem autorizacao de gerente+ (ja existia um campo
        // authorized_by no schema sugerindo isso)
        Route::post('caixa/abrir', [CashShiftController::class, 'open'])->name('caixa.abrir');
        Route::get('caixa/comprovante/{shift}', [CashShiftController::class, 'receipt'])->name('caixa.comprovante');
        Route::get('caixa/resumo/{shift}', [CashShiftController::class, 'summary'])->name('caixa.resumo');
        Route::middleware('role:manager')->group(function () {
            Route::post('caixa/movimento', [CashShiftController::class, 'movement'])->name('caixa.movimento');
            Route::post('caixa/fechar/{shift}', [CashShiftController::class, 'close'])->name('caixa.fechar');
        });

        // Historico de Vendas: ver e' liberado, estornar exige gerente+
        Route::get('historico', [SaleController::class, 'index'])->name('sales.index');
        Route::post('historico/{sale}/cancelar', [SaleController::class, 'cancel'])->name('sales.cancel')->middleware('role:manager');

        // Gestao de Prazos (Crediario): ver e' liberado, baixar recebivel
        // exige gerente+. Emitir NF-e mora no modulo Fiscal (fiscal.documents.emit).
        Route::get('prazos', [ReceivableController::class, 'index'])->name('prazos.index');
        Route::middleware('role:manager')->group(function () {
            Route::post('prazos/{receivable}/quitar', [ReceivableController::class, 'settle'])->name('prazos.settle');
        });
    });