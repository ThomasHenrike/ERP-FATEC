<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Middleware\IdentifyTenant;
use Modules\Estoque\App\Http\Controllers\CategoryController;
use Modules\Estoque\App\Http\Controllers\ProductController;
use Modules\Estoque\App\Http\Controllers\StockMovementController;

/*
|--------------------------------------------------------------------------
| Rotas Web do Modulo Estoque
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', IdentifyTenant::class, 'module.enabled:estoque'])
    ->prefix('estoque')
    ->name('estoque.')
    ->group(function () {
        // Produtos: navegar/buscar e' liberado pra qualquer papel; criar,
        // editar, desativar e reativar (mexe em preco/custo) exige gerente+
        Route::get('produtos', [ProductController::class, 'index'])->name('produtos.index');

        Route::middleware('role:manager')->group(function () {
            Route::post('produtos/{product}/reativar', [ProductController::class, 'activate'])->name('produtos.reactivate');
            Route::resource('produtos', ProductController::class)->except('index')->names([
                'create' => 'produtos.create',
                'store' => 'produtos.store',
                'edit' => 'produtos.edit',
                'update' => 'produtos.update',
                'destroy' => 'produtos.destroy',
            ])->parameters(['produtos' => 'product']);

            // Categorias e Subcategorias - gestao de catalogo, gerente+
            Route::resource('categorias', CategoryController::class)->names([
                'index' => 'categorias.index',
                'create' => 'categorias.create',
                'store' => 'categorias.store',
                'edit' => 'categorias.edit',
                'update' => 'categorias.update',
                'destroy' => 'categorias.destroy',
            ])->parameters(['categorias' => 'category']);
        });

        // Movimentacoes de Estoque: ver o Kardex e' liberado, ajuste manual
        // de quantidade exige gerente+
        Route::get('movimentacoes', [StockMovementController::class, 'index'])->name('movimentacoes.index');
        Route::middleware('role:manager')->group(function () {
            Route::get('movimentacoes/novo', [StockMovementController::class, 'create'])->name('movimentacoes.create');
            Route::post('movimentacoes', [StockMovementController::class, 'store'])->name('movimentacoes.store');
        });
    });