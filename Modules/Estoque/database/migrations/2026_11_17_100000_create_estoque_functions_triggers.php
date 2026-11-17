<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Function e trigger do modulo Estoque (documentados na wiki, pagina "Functions e Triggers").
 *
 * - fn_margem_lucro(custo, preco): markup em % sobre o custo, a mesma conta da tela de produto.
 * - trg_produto_historico_preco: toda vez que custo ou algum preco de venda muda, grava o valor
 *   antigo e o novo em product_price_history.
 *
 * Horario: NOW() na sessao do MySQL, que o Laravel abre no mesmo fuso do sistema
 * (config/database.php 'timezone' = -03:00, horario de Brasilia).
 *
 * A trigger so' REGISTRA (insere numa tabela propria); nunca bloqueia nem altera o produto,
 * para nao interferir no cadastro nem nas vendas. A tabela existe em qualquer banco; a function
 * e a trigger so' sao criadas no MySQL/MariaDB (o SQLite dos testes nao tem esses recursos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('old_cost_price', 12, 2)->nullable();
            $table->decimal('new_cost_price', 12, 2)->nullable();
            $table->decimal('old_price_retail', 12, 2)->nullable();
            $table->decimal('new_price_retail', 12, 2)->nullable();
            $table->decimal('old_price_resale', 12, 2)->nullable();
            $table->decimal('new_price_resale', 12, 2)->nullable();
            $table->decimal('old_price_wholesale', 12, 2)->nullable();
            $table->decimal('new_price_wholesale', 12, 2)->nullable();
            $table->dateTime('changed_at');

            $table->index(['tenant_id', 'product_id', 'changed_at'], 'pph_tenant_product_changed_idx');
        });

        if (!$this->supportsRoutines()) {
            return;
        }

        $this->dropRoutines();

        DB::unprepared(<<<'SQL'
            CREATE FUNCTION fn_margem_lucro(p_custo DECIMAL(12,2), p_preco DECIMAL(12,2))
            RETURNS DECIMAL(12,2)
            DETERMINISTIC
            NO SQL
            BEGIN
                IF p_custo IS NULL OR p_custo <= 0 OR p_preco IS NULL THEN
                    RETURN NULL;
                END IF;
                RETURN ROUND((p_preco - p_custo) / p_custo * 100, 2);
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_produto_historico_preco
            AFTER UPDATE ON products
            FOR EACH ROW
            BEGIN
                IF NOT (OLD.cost_price <=> NEW.cost_price)
                   OR NOT (OLD.price_retail <=> NEW.price_retail)
                   OR NOT (OLD.price_resale <=> NEW.price_resale)
                   OR NOT (OLD.price_wholesale <=> NEW.price_wholesale) THEN
                    INSERT INTO product_price_history (
                        tenant_id, product_id,
                        old_cost_price, new_cost_price,
                        old_price_retail, new_price_retail,
                        old_price_resale, new_price_resale,
                        old_price_wholesale, new_price_wholesale,
                        changed_at
                    ) VALUES (
                        NEW.tenant_id, NEW.id,
                        OLD.cost_price, NEW.cost_price,
                        OLD.price_retail, NEW.price_retail,
                        OLD.price_resale, NEW.price_resale,
                        OLD.price_wholesale, NEW.price_wholesale,
                        NOW()
                    );
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
        if ($this->supportsRoutines()) {
            $this->dropRoutines();
        }

        Schema::dropIfExists('product_price_history');
    }

    private function supportsRoutines(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function dropRoutines(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_produto_historico_preco');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_margem_lucro');
    }
};
