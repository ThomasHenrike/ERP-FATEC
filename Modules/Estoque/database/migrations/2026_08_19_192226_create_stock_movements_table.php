<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // in (Entrada/XML), out (Venda PDV), adjustment (Ajuste Manual), loss (Avaria/Perda)
            $table->string('type', 20); 
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('previous_stock', 12, 3); // Saldo anterior
            $table->decimal('current_stock', 12, 3); // Saldo após a movimentação (permite negativo)

            // Rastreabilidade do documento de origem
            $table->string('reference_type')->nullable(); // 'sale_order', 'xml_import', 'manual_adjustment'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable(); // Observações / Motivo

            $table->timestamps();

            // Índices para consultas ultrarrápidas de extrato e relatórios de estoque
            $table->index(['tenant_id', 'product_id', 'created_at']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};