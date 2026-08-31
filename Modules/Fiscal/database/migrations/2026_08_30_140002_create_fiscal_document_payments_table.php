<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Representacao de pagamento da NF-e, independente do SalePayment real -
     * a venda ja foi paga do jeito que foi; a nota pode precisar refletir
     * boleto parcelado mesmo que o pagamento de verdade tenha sido outro.
     */
    public function up(): void
    {
        Schema::create('fiscal_document_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('fiscal_document_id')->constrained('fiscal_documents')->cascadeOnDelete();

            $table->string('forma_pagamento', 20); // money|pix|credit_card|debit_card|boleto|other
            $table->unsignedTinyInteger('numero_parcela')->nullable(); // so' quando forma_pagamento=boleto
            $table->date('data_vencimento')->nullable(); // so' quando forma_pagamento=boleto
            $table->decimal('valor', 12, 2);

            $table->timestamps();

            $table->index(['tenant_id', 'fiscal_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_document_payments');
    }
};
