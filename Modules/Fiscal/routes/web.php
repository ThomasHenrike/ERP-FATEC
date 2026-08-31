<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Middleware\IdentifyTenant;
use Modules\Fiscal\App\Http\Controllers\FiscalDocumentController;

/*
|--------------------------------------------------------------------------
| Rotas Web do Modulo Fiscal (Emissao de NF-e/NFC-e)
|--------------------------------------------------------------------------
|
| Area de retaguarda fiscal - exige gerente+ pra tudo, mesmo padrao ja
| usado em Fornecedores e Produtos (Modules/Cadastros, Modules/Estoque).
|
*/

Route::middleware(['web', 'auth', IdentifyTenant::class, 'module.enabled:fiscal', 'role:manager'])
    ->prefix('fiscal')
    ->name('fiscal.')
    ->group(function () {
        Route::get('notas', [FiscalDocumentController::class, 'index'])->name('documents.index');
        Route::get('notas/{fiscalDocument}', [FiscalDocumentController::class, 'show'])->name('documents.show');
        Route::get('notas/{fiscalDocument}/editar', [FiscalDocumentController::class, 'edit'])->name('documents.edit');
        Route::put('notas/{fiscalDocument}', [FiscalDocumentController::class, 'update'])->name('documents.update');
        Route::delete('notas/{fiscalDocument}', [FiscalDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('notas/{fiscalDocument}/xml', [FiscalDocumentController::class, 'downloadXml'])->name('documents.xml');
        // Nome de rota mantido (fiscal.documents.emit) pra nao precisar mexer
        // nos botoes ja existentes em Historico de Vendas/Crediario - so'
        // cria o rascunho de revisao agora, nao emite mais direto.
        Route::post('vendas/{sale}/emitir', [FiscalDocumentController::class, 'start'])->name('documents.emit');
    });
