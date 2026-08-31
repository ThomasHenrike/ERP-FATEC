<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\SuperAdmin\App\Http\Controllers\ActivityLogController;
use Modules\SuperAdmin\App\Http\Controllers\AdminAuthController;
use Modules\SuperAdmin\App\Http\Controllers\CompanyController;
use Modules\SuperAdmin\App\Http\Controllers\DashboardController;
use Modules\SuperAdmin\App\Http\Controllers\FuncionarioController;
use Modules\SuperAdmin\App\Http\Controllers\ImpersonationController;
use Modules\SuperAdmin\App\Http\Controllers\InvoiceController;
use Modules\SuperAdmin\App\Http\Controllers\TicketController;

/*
|--------------------------------------------------------------------------
| Rotas Web do Modulo SuperAdmin (Admin Supremo da plataforma)
|--------------------------------------------------------------------------
|
| Guard 'admin' totalmente separado do guard 'web' das empresas - nunca
| inclui IdentifyTenant, pois o Admin Supremo nao pertence a nenhum tenant.
|
*/

Route::middleware(['web'])->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
        Route::post('admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');
    });

    Route::middleware(['auth:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Fila de chamados e impersonation - area de trabalho do 'support',
        // por isso ficam FORA do sub-grupo admin.role:owner abaixo.
        Route::get('chamados', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('chamados/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::post('chamados/{ticket}/responder', [TicketController::class, 'reply'])->name('tickets.reply');
        Route::patch('chamados/{ticket}/fechar', [TicketController::class, 'close'])->name('tickets.close');
        Route::post('chamados/{ticket}/impersonar', [ImpersonationController::class, 'start'])->name('tickets.impersonate');
        Route::post('impersonar/sair', [ImpersonationController::class, 'stop'])->name('impersonation.stop');

        Route::middleware('admin.role:owner')->group(function () {
            Route::get('empresas', [CompanyController::class, 'index'])->name('companies.index');
            Route::get('empresas/criar', [CompanyController::class, 'create'])->name('companies.create');
            Route::post('empresas', [CompanyController::class, 'store'])->name('companies.store');
            Route::get('empresas/{tenant}/editar', [CompanyController::class, 'edit'])->name('companies.edit');
            Route::put('empresas/{tenant}', [CompanyController::class, 'update'])->name('companies.update');
            Route::put('empresas/{tenant}/modulos', [CompanyController::class, 'updateModules'])->name('companies.modules');

            Route::get('faturas', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::post('faturas', [InvoiceController::class, 'store'])->name('invoices.store');
            Route::patch('faturas/{invoice}/pagar', [InvoiceController::class, 'markPaid'])->name('invoices.markPaid');

            Route::get('funcionarios', [FuncionarioController::class, 'index'])->name('funcionarios.index');
            Route::get('funcionarios/criar', [FuncionarioController::class, 'create'])->name('funcionarios.create');
            Route::post('funcionarios', [FuncionarioController::class, 'store'])->name('funcionarios.store');
            Route::get('funcionarios/{admin}/editar', [FuncionarioController::class, 'edit'])->name('funcionarios.edit');
            Route::put('funcionarios/{admin}', [FuncionarioController::class, 'update'])->name('funcionarios.update');
            Route::patch('funcionarios/{admin}/alternar-status', [FuncionarioController::class, 'toggleActive'])->name('funcionarios.toggleActive');

            Route::get('logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        });
    });
});
