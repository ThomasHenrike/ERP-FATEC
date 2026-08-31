<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('role')->default('support')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
        });

        // Contas ja existentes antes desta migration sao, por definicao, a
        // conta do dono da plataforma - promove todas pra 'owner' pra nao
        // exigir um passo manual (ex.: em producao) so pra nao se auto-rebaixar.
        // Contas criadas depois disso (pela tela de Funcionarios) nascem como
        // 'support' pelo default da coluna.
        DB::table('admins')->update(['role' => 'owner']);
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
