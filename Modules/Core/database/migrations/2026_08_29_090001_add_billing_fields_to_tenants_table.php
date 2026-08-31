<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'billing_due_day')) {
                $table->unsignedTinyInteger('billing_due_day')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('tenants', 'billing_amount')) {
                $table->decimal('billing_amount', 10, 2)->nullable()->after('billing_due_day');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['billing_due_day', 'billing_amount']);
        });
    }
};
