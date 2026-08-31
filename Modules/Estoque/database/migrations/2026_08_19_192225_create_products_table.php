<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            // 1. Identificação Comercial
            $table->string('name');
            $table->string('sku', 50)->nullable();
            $table->string('barcode', 14)->nullable();
            $table->string('unit', 6)->default('UN');

            // 2. Preço de Custo e Tabelas de Preços com Margem % (Markup)
            $table->decimal('cost_price', 12, 2)->default(0.00);
            
            // Varejo (Consumidor Final)
            $table->decimal('margin_retail', 6, 2)->default(0.00); // Margem % Varejo (ex: 50.00%)
            $table->decimal('price_retail', 12, 2); // Preço Final Varejo
            
            // Revenda (Instaladores / Parceiros)
            $table->decimal('margin_resale', 6, 2)->nullable(); // Margem % Revenda (ex: 30.00%)
            $table->decimal('price_resale', 12, 2)->nullable(); // Preço Final Revenda
            
            // Atacado (Volume)
            $table->decimal('margin_wholesale', 6, 2)->nullable(); // Margem % Atacado (ex: 15.00%)
            $table->decimal('price_wholesale', 12, 2)->nullable(); // Preço Final Atacado

            // 3. Controle de Estoque (Permite saldo negativo no PDV)
            $table->decimal('min_stock', 12, 3)->default(0.000);
            $table->decimal('current_stock', 12, 3)->default(0.000);

            // 4. Dados Fiscais Obrigatórios (SEFAZ - NF-e / NFC-e)
            $table->string('ncm', 8);
            $table->string('cest', 7)->nullable();
            $table->unsignedTinyInteger('origin')->default(0);
            $table->string('cfop_default', 4)->default('5102');
            $table->string('csosn', 4)->nullable()->default('102');
            $table->string('cst_icms', 3)->nullable();
            $table->string('cst_pis', 2)->default('07');
            $table->string('cst_cofins', 2)->default('07');
            $table->decimal('icms_rate', 5, 2)->default(0.00);
            $table->decimal('pis_rate', 5, 2)->default(0.00);
            $table->decimal('cofins_rate', 5, 2)->default(0.00);

            // 5. Auditoria e Status
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices de Performance
            $table->index(['tenant_id', 'barcode']);
            $table->index(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'ncm']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};