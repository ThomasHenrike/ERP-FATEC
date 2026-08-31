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
            $table->string('quote_default_payment_condition')->nullable()->after('quote_show_freight');
            $table->string('quote_default_delivery_term')->nullable()->after('quote_default_payment_condition');
            $table->string('quote_default_freight_info')->nullable()->after('quote_default_delivery_term');
            $table->unsignedSmallInteger('quote_default_lead_time_days')->nullable()->after('quote_default_freight_info');
            $table->unsignedSmallInteger('quote_default_validity_days')->default(5)->after('quote_default_lead_time_days');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'quote_default_payment_condition',
                'quote_default_delivery_term',
                'quote_default_freight_info',
                'quote_default_lead_time_days',
                'quote_default_validity_days',
            ]);
        });
    }
};
