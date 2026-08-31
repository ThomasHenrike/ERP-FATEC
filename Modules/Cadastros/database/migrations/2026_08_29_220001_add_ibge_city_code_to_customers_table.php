<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Codigo IBGE do municipio (7 digitos) - exigido no endereco do
            // destinatario da NF-e. Ver 2026_08_29_220000_add_fiscal_fields_to_tenants_table.
            $table->string('ibge_city_code', 7)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('ibge_city_code');
        });
    }
};
