<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'trade_name')) {
                $table->string('trade_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('customers', 'state_registration')) {
                $table->string('state_registration', 20)->nullable()->after('document');
            }
            if (!Schema::hasColumn('customers', 'email')) {
                $table->string('email')->nullable()->after('state_registration');
            }
            if (!Schema::hasColumn('customers', 'whatsapp')) {
                $table->string('whatsapp', 20)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('customers', 'default_price_table')) {
                $table->string('default_price_table', 20)->default('retail')->after('credit_limit');
            }
            if (!Schema::hasColumn('customers', 'zip_code')) {
                $table->string('zip_code', 10)->nullable()->after('default_price_table');
                $table->string('street')->nullable()->after('zip_code');
                $table->string('number', 20)->nullable()->after('street');
                $table->string('neighborhood', 100)->nullable()->after('number');
                $table->string('city', 100)->nullable()->after('neighborhood');
                $table->string('state', 2)->nullable()->after('city');
            }
            if (!Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'trade_name',
                'state_registration',
                'email',
                'whatsapp',
                'default_price_table',
                'zip_code',
                'street',
                'number',
                'neighborhood',
                'city',
                'state',
            ]);
            $table->dropSoftDeletes();
        });
    }
};