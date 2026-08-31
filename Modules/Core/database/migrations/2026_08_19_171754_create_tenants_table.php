<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name'); // Razão Social
            $table->string('trade_name')->nullable(); // Nome Fantasia
            $table->string('document', 18)->unique(); // CNPJ ou CPF
            $table->string('state_registration', 20)->nullable(); // Inscrição Estadual
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índice para buscas rápidas de login e validação de empresa
            $table->index(['document', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};