<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Subcategorias Hierarquicas
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('tenant_id')->constrained('categories')->nullOnDelete();
            }
        });

        // 2. Atributos Comerciais Opcionais do Produto
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand', 100)->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('category_id')->constrained('suppliers')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'location')) {
                $table->string('location', 100)->nullable()->after('min_stock');
            }
            if (!Schema::hasColumn('products', 'warranty_months')) {
                $table->unsignedInteger('warranty_months')->nullable()->after('location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['brand', 'supplier_id', 'location', 'warranty_months']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};