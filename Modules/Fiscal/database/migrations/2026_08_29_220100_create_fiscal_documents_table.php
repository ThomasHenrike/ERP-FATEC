<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            // Direto, nao so' via sale_id - usado pro path do arquivo (empresa/
            // cliente/mes) e da suporte a documentos futuros (ex: boleto) que
            // podem nao ter uma Sale por tras.
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->unsignedTinyInteger('model'); // 55 = NF-e, 65 = NFC-e (fase 2+)
            $table->unsignedTinyInteger('series');
            $table->unsignedBigInteger('number');

            // draft -> signed -> transmitted -> authorized | rejected | denied | error | cancelled
            $table->string('status', 20)->default('draft');

            $table->char('chave_acesso', 44)->nullable()->unique();
            $table->string('protocol', 20)->nullable();

            $table->string('signed_xml_path')->nullable();
            $table->string('authorized_xml_path')->nullable();
            $table->string('danfe_path')->nullable();

            $table->text('rejection_reason')->nullable();

            // Foto do ambiente no momento da emissao - nao le direto do tenant,
            // porque o tenant pode trocar homologacao/producao entre emissoes
            // e o historico precisa dizer pra qual ambiente aquele documento
            // especifico foi.
            $table->string('environment', 20)->default('homologation');

            $table->timestamp('emitted_at')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'model', 'series', 'number']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_documents');
    }
};
