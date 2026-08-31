<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamento_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('orcamento_id')->constrained('orcamentos')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');

            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);

            $table->decimal('icms_percent', 5, 2)->default(0.00);
            $table->decimal('icms_st_value', 12, 2)->default(0.00);
            $table->unsignedSmallInteger('lead_time_days')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'orcamento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_items');
    }
};
