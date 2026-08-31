<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // FK simples de proposito - chamado e' um registro da plataforma
            // sobre a empresa, nao um dado da propria empresa, entao nao usa
            // o trait BelongsToTenant/TenantScope (mesmo motivo de Invoice).
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject')->default('Suporte');
            $table->string('status')->default('open'); // open|closed
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
