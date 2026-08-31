<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            
            // Código e Descrição do Produto que vêm na tag <cProd> e <xProd> do XML do Fornecedor
            $table->string('supplier_cnpj', 18);
            $table->string('supplier_name')->nullable();
            $table->string('supplier_product_code', 100); // Código do item no XML do fornecedor
            $table->string('supplier_product_name')->nullable();

            $table->timestamps();

            // Chave única para o De-Para: um código de produto daquele fornecedor só aponta para 1 produto interno
            $table->unique(['tenant_id', 'supplier_cnpj', 'supplier_product_code'], 'uniq_supplier_product');
            $table->index(['tenant_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};