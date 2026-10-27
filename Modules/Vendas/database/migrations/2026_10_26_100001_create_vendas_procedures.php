<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Stored Procedures de consulta do modulo de Vendas (documentadas em wiki/StoredProcedures.md).
 *
 * Todas sao somente leitura, recebem p_tenant_id, consideram so' vendas concluidas
 * (status = 'completed') e tratam a data final do periodo como inclusiva.
 * sp_vendas_por_periodo e sp_produtos_mais_vendidos ficam na migration
 * database/migrations/2026_10_19_000000_create_stored_procedures.php.
 * So' existem no MySQL/MariaDB; no SQLite a migration e' ignorada.
 */
return new class extends Migration
{
    private const PROCEDURES = [
        'sp_comissao_vendedores',
        'sp_resumo_caixa',
        'sp_contas_receber_cliente',
    ];

    public function up(): void
    {
        if (!$this->supportsProcedures()) {
            return;
        }

        $this->dropProcedures();

        // Comissao de cada vendedor no periodo: total das vendas x percentual do cadastro.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_comissao_vendedores(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_data_inicio DATE,
                IN p_data_fim DATE
            )
            BEGIN
                IF p_data_inicio IS NULL OR p_data_fim IS NULL OR p_data_inicio > p_data_fim THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Periodo invalido: informe data inicial menor ou igual a final.';
                END IF;

                SELECT e.id, e.name AS vendedor, e.commission_percentage,
                       COUNT(s.id) AS quantidade_vendas,
                       SUM(s.total_amount) AS total_vendido,
                       ROUND(SUM(s.total_amount) * e.commission_percentage / 100, 2) AS comissao
                FROM employees e
                JOIN sales s ON s.seller_id = e.id AND s.tenant_id = e.tenant_id
                WHERE e.tenant_id = p_tenant_id
                  AND e.deleted_at IS NULL
                  AND s.status = 'completed'
                  AND s.created_at >= p_data_inicio
                  AND s.created_at < p_data_fim + INTERVAL 1 DAY
                GROUP BY e.id, e.name, e.commission_percentage
                ORDER BY total_vendido DESC;
            END
            SQL);

        // Resumo de um turno de caixa, com o mesmo calculo de CashShiftService::getSummary.
        // troco_devolvido aparece a parte porque o saldo esperado do sistema nao o desconta.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_resumo_caixa(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_cash_shift_id BIGINT UNSIGNED
            )
            BEGIN
                SELECT r.id, r.status, r.opened_at, r.closed_at, r.opening_balance,
                       r.vendas_dinheiro, r.vendas_pix, r.vendas_cartao, r.vendas_prazo,
                       r.suprimentos, r.sangrias, r.troco_devolvido,
                       r.opening_balance + r.vendas_dinheiro + r.suprimentos - r.sangrias AS saldo_esperado_gaveta,
                       r.vendas_dinheiro + r.vendas_pix + r.vendas_cartao + r.vendas_prazo AS total_vendas
                FROM (
                    SELECT cs.id, cs.status, cs.opened_at, cs.closed_at, cs.opening_balance,
                           (SELECT COALESCE(SUM(pg.amount), 0) FROM sale_payments pg
                              JOIN sales s ON s.id = pg.sale_id
                             WHERE pg.tenant_id = cs.tenant_id AND s.cash_shift_id = cs.id
                               AND s.status = 'completed' AND pg.payment_method = 'money') AS vendas_dinheiro,
                           (SELECT COALESCE(SUM(pg.amount), 0) FROM sale_payments pg
                              JOIN sales s ON s.id = pg.sale_id
                             WHERE pg.tenant_id = cs.tenant_id AND s.cash_shift_id = cs.id
                               AND s.status = 'completed' AND pg.payment_method = 'pix') AS vendas_pix,
                           (SELECT COALESCE(SUM(pg.amount), 0) FROM sale_payments pg
                              JOIN sales s ON s.id = pg.sale_id
                             WHERE pg.tenant_id = cs.tenant_id AND s.cash_shift_id = cs.id
                               AND s.status = 'completed'
                               AND pg.payment_method IN ('credit_card', 'debit_card')) AS vendas_cartao,
                           (SELECT COALESCE(SUM(pg.amount), 0) FROM sale_payments pg
                              JOIN sales s ON s.id = pg.sale_id
                             WHERE pg.tenant_id = cs.tenant_id AND s.cash_shift_id = cs.id
                               AND s.status = 'completed' AND pg.payment_method = 'on_account') AS vendas_prazo,
                           (SELECT COALESCE(SUM(m.amount), 0) FROM cash_movements m
                             WHERE m.tenant_id = cs.tenant_id AND m.cash_shift_id = cs.id
                               AND m.type = 'inflow') AS suprimentos,
                           (SELECT COALESCE(SUM(m.amount), 0) FROM cash_movements m
                             WHERE m.tenant_id = cs.tenant_id AND m.cash_shift_id = cs.id
                               AND m.type = 'outflow') AS sangrias,
                           (SELECT COALESCE(SUM(s.change_amount), 0) FROM sales s
                             WHERE s.tenant_id = cs.tenant_id AND s.cash_shift_id = cs.id
                               AND s.status = 'completed') AS troco_devolvido
                    FROM cash_shifts cs
                    WHERE cs.tenant_id = p_tenant_id
                      AND cs.id = p_cash_shift_id
                ) AS r;
            END
            SQL);

        // Parcelas em aberto do crediario de um cliente (exercicio 13: dois parametros no WHERE).
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_contas_receber_cliente(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_customer_id BIGINT UNSIGNED
            )
            BEGIN
                SELECT r.id, c.name AS cliente, v.code AS numero_venda,
                       r.installment_number, r.total_installments, r.due_date,
                       r.amount, r.paid_amount, (r.amount - r.paid_amount) AS saldo,
                       GREATEST(DATEDIFF(UTC_DATE(), r.due_date), 0) AS dias_atraso
                FROM customer_receivables r
                JOIN customers c ON c.id = r.customer_id AND c.tenant_id = r.tenant_id
                JOIN sales v ON v.id = r.sale_id AND v.tenant_id = r.tenant_id
                WHERE r.tenant_id = p_tenant_id
                  AND r.customer_id = p_customer_id
                  AND c.deleted_at IS NULL
                  AND r.status IN ('pending', 'overdue')
                ORDER BY r.due_date, r.installment_number;
            END
            SQL);
    }

    public function down(): void
    {
        if (!$this->supportsProcedures()) {
            return;
        }

        $this->dropProcedures();
    }

    private function supportsProcedures(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function dropProcedures(): void
    {
        foreach (self::PROCEDURES as $procedure) {
            DB::unprepared("DROP PROCEDURE IF EXISTS {$procedure}");
        }
    }
};
