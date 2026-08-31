<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Controllers\AjudaController;
use Modules\Core\App\Http\Controllers\AuthController;
use Modules\Core\App\Http\Controllers\DashboardController;
use Modules\Core\App\Http\Controllers\FinanceiroController;
use Modules\Core\App\Http\Controllers\SupportTicketController;
use Modules\Core\App\Http\Controllers\TenantRegistrationController;
use Modules\Core\App\Http\Controllers\TenantSettingsController;
use Modules\Core\App\Http\Middleware\IdentifyTenant;

/*
|--------------------------------------------------------------------------
| Rotas Web do Modulo Core
|--------------------------------------------------------------------------
*/

Route::middleware(['web'])->group(function () {
    // Rotas Publicas / Convidados
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

        Route::get('cadastrar-empresa', [TenantRegistrationController::class, 'create'])->name('core.tenant.create');
        Route::post('cadastrar-empresa', [TenantRegistrationController::class, 'store'])->name('core.tenant.store');
    });

    // Rotas Protegidas com Isolamento Multi-Tenant
    Route::middleware(['auth', IdentifyTenant::class])->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('configuracoes', [TenantSettingsController::class, 'edit'])->name('core.settings.edit')->middleware('role:admin');
        Route::put('configuracoes', [TenantSettingsController::class, 'update'])->name('core.settings.update')->middleware('role:admin');

        Route::get('financeiro', [FinanceiroController::class, 'index'])->name('core.financeiro.index')->middleware('role:admin');

        Route::get('ajuda', [AjudaController::class, 'index'])->name('core.ajuda.index');
        Route::get('ajuda/{slug}', [AjudaController::class, 'show'])->name('core.ajuda.show');

        Route::get('suporte', [SupportTicketController::class, 'index'])->name('core.suporte.index');
        Route::post('suporte', [SupportTicketController::class, 'store'])->name('core.suporte.store');
        Route::post('suporte/{ticket}/mensagens', [SupportTicketController::class, 'storeMessage'])->name('core.suporte.messages.store');
        Route::get('suporte/{ticket}/mensagens', [SupportTicketController::class, 'pollMessages'])->name('core.suporte.messages.poll');

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });
});