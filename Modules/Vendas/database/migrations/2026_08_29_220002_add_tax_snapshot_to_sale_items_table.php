<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retrato fiscal do produto no momento da venda - necessario pra emitir
     * uma NF-e fielmente correta mesmo que o cadastro do produto mude depois
     * (NCM, CFOP, aliquota etc. podem ser corrigidos no Product a qualquer
     * momento, mas a nota de uma venda ja feita tem que refletir o que era
     * verdade quando ela aconteceu). Vendas anteriores a esta migration ficam
     * com esses campos nulos - o servico de emissao de NF-e deve recusar ou
     * avisar claramente nesse caso, nunca emitir calado com dado incompleto.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('ncm', 8)->nullable()->after('product_id');
            $table->string('cest', 7)->nullable()->after('ncm');
            $table->unsignedTinyInteger('origin')->nullable()->after('cest');
            $table->string('cfop', 4)->nullable()->after('origin');
            $table->string('csosn', 4)->nullable()->after('cfop');
            $table->string('cst_icms', 3)->nullable()->after('csosn');
            $table->string('cst_pis', 2)->nullable()->after('cst_icms');
            $table->string('cst_cofins', 2)->nullable()->after('cst_pis');
            $table->decimal('icms_rate', 5, 2)->nullable()->after('cst_cofins');
            $table->decimal('pis_rate', 5, 2)->nullable()->after('icms_rate');
            $table->decimal('cofins_rate', 5, 2)->nullable()->after('pis_rate');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'ncm', 'cest', 'origin', 'cfop', 'csosn', 'cst_icms',
                'cst_pis', 'cst_cofins', 'icms_rate', 'pis_rate', 'cofins_rate',
            ]);
        });
    }
};
