<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela de Turnos / Sessoes de Caixa
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            
            $table->string('status', 20)->default('open'); // open, closed
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            
            $table->decimal('opening_balance', 12, 2)->default(0.00); // Fundo de troco inicial
            $table->decimal('closing_balance', 12, 2)->nullable(); // Valor contado no fechamento
            $table->decimal('expected_balance', 12, 2)->default(0.00); // Saldo calculado pelo sistema
            $table->decimal('difference_amount', 12, 2)->default(0.00); // Sobra ou quebra de caixa
            
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });

        // 2. Tabela de Movimentacoes de Gaveta (Sangria e Suprimento)
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('cash_shift_id')->constrained('cash_shifts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            
            $table->string('type', 20); // inflow (suprimento), outflow (sangria)
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->string('authorized_by')->nullable();
            
            $table->timestamps();

            $table->index(['tenant_id', 'cash_shift_id']);
        });

        // 3. Vinculo da Venda com a Sessao de Caixa
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cash_shift_id')->nullable()->after('user_id')->constrained('cash_shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cash_shift_id']);
            $table->dropColumn('cash_shift_id');
        });

        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_shifts');
    }
};