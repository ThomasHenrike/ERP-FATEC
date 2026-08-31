<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('code');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('customer_id')->nullable();
            
            $table->string('status', 20)->default('completed'); // completed, canceled
            $table->string('price_table', 20)->default('retail'); // retail, resale, wholesale
            
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            
            $table->string('payment_method', 30)->default('money'); // money, pix, credit_card, debit_card, on_account
            $table->text('notes')->nullable(); // Campo de observacoes para o cupom e romaneio
            
            $table->timestamps();
            
            $table->unique(['tenant_id', 'code'], 'sales_tenant_code_unique');
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('total_price', 12, 2);
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};