<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('operator'); // admin, manager, operator
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Chave única para impedir que o mesmo usuário seja vinculado duas vezes à mesma empresa
            $table->unique(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};