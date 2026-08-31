<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // FK simples de proposito - fatura e' um registro da
            // plataforma sobre a empresa, nao um dado da propria empresa,
            // entao nao usa o trait BelongsToTenant/TenantScope.
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->string('status')->default('pending'); // pending|paid|overdue|cancelled
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->dateTime('paid_at')->nullable();
            $table->string('reference_period')->nullable(); // 'YYYY-MM', usado pra geracao idempotente
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
