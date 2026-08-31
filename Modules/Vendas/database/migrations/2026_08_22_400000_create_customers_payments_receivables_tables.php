<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela de Clientes
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('document', 20)->nullable(); // CPF ou CNPJ
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'document']);
        });

        // 2. Tabela de Pagamentos Fracionados (Split Payment)
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('payment_method', 30); // money, pix, credit_card, debit_card, on_account
            $table->decimal('amount', 12, 2);
            $table->integer('installments')->default(1);
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'sale_id']);
        });

        // 3. Tabela de Titulos a Prazo / Crediario do Cliente
        Schema::create('customer_receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->integer('installment_number')->default(1);
            $table->integer('total_installments')->default(1);
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending, paid, overdue, canceled
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'due_date']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_receivables');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('customers');
    }
};