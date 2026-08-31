<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('code')->nullable()->after('tenant_id');
            $table->unique(['tenant_id', 'code'], 'products_tenant_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_tenant_code_unique');
            $table->dropColumn('code');
        });
    }
};