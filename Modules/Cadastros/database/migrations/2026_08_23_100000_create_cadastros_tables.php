<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela de Funcionarios e Colaboradores
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('name');
            $table->string('document', 20)->nullable(); // CPF
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            
            $table->string('role', 30)->default('vendedor'); // vendedor, caixa, gerente, tecnico, instalador, ajudante
            $table->decimal('commission_percentage', 5, 2)->default(0.00);
            $table->date('hire_date')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'role']);
        });

        // 2. Tabela de Fornecedores
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            $table->string('name'); // Razao Social
            $table->string('trade_name')->nullable(); // Nome Fantasia
            $table->string('document', 20)->nullable(); // CNPJ
            $table->string('state_registration', 20)->nullable();
            
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('contact_name')->nullable();
            
            $table->string('zip_code', 10)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 20)->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'document']);
            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('employees');
    }
};