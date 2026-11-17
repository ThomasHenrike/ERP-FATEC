<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Function e trigger do modulo Vendas (documentados na wiki, pagina "Functions e Triggers").
 *
 * - fn_saldo_devedor_cliente(tenant, cliente): quanto o cliente ainda deve no crediario
 *   (titulos pendentes: valor - valor ja pago), o mesmo criterio da tela de Prazos.
 * - trg_venda_cancelada_log: quando uma venda passa para "canceled", grava um registro em
 *   sale_cancellation_logs (codigo, valor, motivo e quando).
 *
 * A trigger so' REGISTRA; nunca bloqueia nem altera a venda. A tabela existe em qualquer banco;
 * a function e a trigger so' sao criadas no MySQL/MariaDB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_cancellation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->unsignedBigInteger('sale_code');
            $table->decimal('total_amount', 12, 2);
            $table->text('cancel_reason')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->dateTime('logged_at');

            $table->index(['tenant_id', 'logged_at']);
        });

        if (!$this->supportsRoutines()) {
            return;
        }

        $this->dropRoutines();

        DB::unprepared(<<<'SQL'
            CREATE FUNCTION fn_saldo_devedor_cliente(p_tenant_id BIGINT UNSIGNED, p_customer_id BIGINT UNSIGNED)
            RETURNS DECIMAL(12,2)
            NOT DETERMINISTIC
            READS SQL DATA
            BEGIN
                DECLARE v_saldo DECIMAL(12,2) DEFAULT 0;

                SELECT COALESCE(SUM(amount - paid_amount), 0)
                  INTO v_saldo
                  FROM customer_receivables
                 WHERE tenant_id = p_tenant_id
                   AND customer_id = p_customer_id
                   AND status = 'pending';

                RETURN v_saldo;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_venda_cancelada_log
            AFTER UPDATE ON sales
            FOR EACH ROW
            BEGIN
                IF NEW.status = 'canceled' AND NOT (OLD.status <=> 'canceled') THEN
                    INSERT INTO sale_cancellation_logs (
                        tenant_id, sale_id, sale_code, total_amount, cancel_reason, canceled_at, logged_at
                    ) VALUES (
                        NEW.tenant_id, NEW.id, NEW.code, NEW.total_amount, NEW.cancel_reason, NEW.canceled_at, NOW()
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

        Schema::dropIfExists('sale_cancellation_logs');
    }

    private function supportsRoutines(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function dropRoutines(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_venda_cancelada_log');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_saldo_devedor_cliente');
    }
};
