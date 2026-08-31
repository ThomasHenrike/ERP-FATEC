<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copia independente dos itens da Sale, editavel na tela de revisao da
     * NF-e sem tocar no pedido/estoque/caixa originais (decisao explicita
     * de Thomas: edicao fica so' no documento fiscal).
     */
    public function up(): void
    {
        Schema::create('fiscal_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('fiscal_document_id')->constrained('fiscal_documents')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');

            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('total_price', 12, 2);

            $table->string('ncm', 8)->nullable();
            $table->string('cest', 7)->nullable();
            $table->unsignedTinyInteger('origin')->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('csosn', 4)->nullable();
            $table->string('cst_icms', 3)->nullable();
            $table->string('cst_pis', 2)->nullable();
            $table->string('cst_cofins', 2)->nullable();
            $table->decimal('icms_rate', 5, 2)->nullable();
            $table->decimal('pis_rate', 5, 2)->nullable();
            $table->decimal('cofins_rate', 5, 2)->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'fiscal_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_document_items');
    }
};
