<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('code');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('user_id')->constrained('users');

            $table->string('status', 20)->default('draft'); // draft, sent, approved, rejected, converted, expired

            $table->string('payment_condition')->nullable();
            $table->string('delivery_term')->nullable();
            $table->string('freight_info')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('total_icms_st', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);

            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('converted_sale_id')->nullable()->constrained('sales')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
