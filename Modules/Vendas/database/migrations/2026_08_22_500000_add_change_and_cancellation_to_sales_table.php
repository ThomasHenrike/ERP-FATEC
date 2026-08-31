<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('amount_received', 12, 2)->default(0.00)->after('total_amount');
            $table->decimal('change_amount', 12, 2)->default(0.00)->after('amount_received');
            $table->dateTime('canceled_at')->nullable()->after('status');
            $table->text('cancel_reason')->nullable()->after('canceled_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['amount_received', 'change_amount', 'canceled_at', 'cancel_reason']);
        });
    }
};