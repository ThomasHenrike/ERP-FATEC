<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Cadastros\App\Http\Controllers\CustomerController;
use Modules\Cadastros\App\Http\Controllers\EmployeeController;
use Modules\Cadastros\App\Http\Controllers\SupplierController;
use Modules\Core\App\Http\Middleware\IdentifyTenant;

/*
|--------------------------------------------------------------------------
| Rotas Web do Modulo Cadastros (Pessoas & Parceiros)
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', IdentifyTenant::class, 'module.enabled:cadastros'])
    ->prefix('cadastros')
    ->name('cadastros.')
    ->group(function () {
        // Funcionarios: dado de RH/comissao, exclusivo do administrador
        Route::middleware('role:admin')->group(function () {
            Route::resource('funcionarios', EmployeeController::class)->names([
                'index' => 'funcionarios.index',
                'create' => 'funcionarios.create',
                'store' => 'funcionarios.store',
                'edit' => 'funcionarios.edit',
                'update' => 'funcionarios.update',
                'destroy' => 'funcionarios.destroy',
            ])->parameters(['funcionarios' => 'employee']);
            Route::patch('funcionarios/{employee}/revogar-acesso', [EmployeeController::class, 'revokeAccess'])->name('funcionarios.revokeAccess');
            Route::patch('funcionarios/{employee}/restaurar-acesso', [EmployeeController::class, 'restoreAccess'])->name('funcionarios.restoreAccess');
        });

        // Clientes: cadastrar/editar e' rotina de atendimento (liberado pra
        // todos), excluir exige gerente+
        Route::resource('clientes', CustomerController::class)->except('destroy')->names([
            'index' => 'clientes.index',
            'create' => 'clientes.create',
            'store' => 'clientes.store',
            'edit' => 'clientes.edit',
            'update' => 'clientes.update',
        ])->parameters(['clientes' => 'customer']);
        Route::delete('clientes/{customer}', [CustomerController::class, 'destroy'])->name('clientes.destroy')->middleware('role:manager');

        // Fornecedores: cadastro e' area de retaguarda, gerente+
        Route::middleware('role:manager')->group(function () {
            Route::resource('fornecedores', SupplierController::class)->names([
                'index' => 'fornecedores.index',
                'create' => 'fornecedores.create',
                'store' => 'fornecedores.store',
                'edit' => 'fornecedores.edit',
                'update' => 'fornecedores.update',
                'destroy' => 'fornecedores.destroy',
            ])->parameters(['fornecedores' => 'supplier']);
        });
    });