<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NCM deixa de ser obrigatorio no cadastro de produto: so' e' exigido na hora de
 * emitir NF-e (o NFeBuilderService ja barra item sem NCM com mensagem clara).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->withoutSqliteViews(function () {
            Schema::table('products', function (Blueprint $table) {
                $table->string('ncm', 8)->nullable()->change();
            });
        });
    }

    public function down(): void
    {
        // Produtos sem NCM recebem um valor generico antes de voltar a ser NOT NULL.
        DB::table('products')->whereNull('ncm')->update(['ncm' => '00000000']);

        $this->withoutSqliteViews(function () {
            Schema::table('products', function (Blueprint $table) {
                $table->string('ncm', 8)->nullable(false)->change();
            });
        });
    }

    /**
     * No SQLite (testes) o change() recria a tabela, e as views do banco que leem
     * `products` (ex.: vw_estoque_abaixo_minimo) impedem o rename. Guarda as views,
     * altera e recria. No MySQL/MariaDB (producao) o ALTER nao mexe nas views.
     */
    private function withoutSqliteViews(callable $alter): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $alter();

            return;
        }

        $views = DB::select("SELECT name, sql FROM sqlite_master WHERE type = 'view'");
        foreach ($views as $view) {
            DB::statement('DROP VIEW IF EXISTS "'.$view->name.'"');
        }

        $alter();

        foreach ($views as $view) {
            DB::statement($view->sql);
        }
    }
};
