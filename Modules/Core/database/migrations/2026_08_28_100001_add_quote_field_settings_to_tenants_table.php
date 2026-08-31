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
            $table->boolean('quote_show_ncm')->default(true)->after('max_discount_percentage');
            $table->boolean('quote_show_icms')->default(true)->after('quote_show_ncm');
            $table->boolean('quote_show_icms_st')->default(true)->after('quote_show_icms');
            $table->boolean('quote_show_lead_time')->default(true)->after('quote_show_icms_st');
            $table->boolean('quote_show_payment_condition')->default(true)->after('quote_show_lead_time');
            $table->boolean('quote_show_delivery_term')->default(true)->after('quote_show_payment_condition');
            $table->boolean('quote_show_freight')->default(true)->after('quote_show_delivery_term');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'quote_show_ncm',
                'quote_show_icms',
                'quote_show_icms_st',
                'quote_show_lead_time',
                'quote_show_payment_condition',
                'quote_show_delivery_term',
                'quote_show_freight',
            ]);
        });
    }
};
