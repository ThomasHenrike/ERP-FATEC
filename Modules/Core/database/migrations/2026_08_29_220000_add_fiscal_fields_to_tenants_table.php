<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // CRT (Codigo de Regime Tributario): 1=Simples Nacional, 2=Simples
            // excesso sublimite, 3=Regime Normal. Define se o item da nota usa
            // CSOSN (Simples) ou CST (Normal) no grupo de ICMS.
            $table->unsignedTinyInteger('crt')->default(1)->after('state_registration');

            // Codigo IBGE do municipio (7 digitos) - exigido pela NF-e em cMunFG
            // e no endereco do emitente. O CEP-lookup (ViaCEP) ja devolve esse
            // dado, so' precisa passar a capturar.
            $table->string('ibge_city_code', 7)->nullable()->after('city');

            // Numeracao sequencial da NF-e/NFC-e por serie - a SEFAZ exige que
            // nunca se repita nem pule sem justificativa (inutilizacao).
            $table->unsignedTinyInteger('nfe_series')->default(1)->after('sefaz_environment');
            $table->unsignedBigInteger('nfe_next_number')->default(1)->after('nfe_series');
            $table->unsignedTinyInteger('nfce_series')->nullable()->after('nfe_next_number');
            $table->unsignedBigInteger('nfce_next_number')->nullable()->after('nfce_series');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['crt', 'ibge_city_code', 'nfe_series', 'nfe_next_number', 'nfce_series', 'nfce_next_number']);
        });
    }
};
