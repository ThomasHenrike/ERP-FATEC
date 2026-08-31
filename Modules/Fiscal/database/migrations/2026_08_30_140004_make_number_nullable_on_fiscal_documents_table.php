<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O numero da NF-e so' e' alocado no momento real de emitir (nao mais
     * na criacao do rascunho de revisao) - pra nao queimar numeracao
     * sequencial em rascunhos que o operador abandona sem emitir.
     */
    public function up(): void
    {
        Schema::table('fiscal_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('number')->nullable(false)->change();
        });
    }
};
