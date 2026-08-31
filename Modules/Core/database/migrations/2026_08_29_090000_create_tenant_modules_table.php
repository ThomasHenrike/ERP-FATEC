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
        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('module');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'module']);
        });

        // Backfill: garante que nenhuma empresa ja existente fique sem
        // acesso a um modulo que ela ja usava antes desta feature existir.
        $modules = ['cadastros', 'estoque', 'vendas', 'orcamento'];
        $now = now();

        $rows = DB::table('tenants')->pluck('id')->flatMap(
            fn ($tenantId) => collect($modules)->map(fn ($module) => [
                'tenant_id' => $tenantId,
                'module' => $module,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
        )->all();

        if ($rows !== []) {
            DB::table('tenant_modules')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
    }
};
