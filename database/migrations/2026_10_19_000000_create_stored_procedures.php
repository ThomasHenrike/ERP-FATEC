<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Stored procedures do banco MySQL (trabalho da faculdade, depois das views
 * da Aula 12). Todas recebem a empresa (p_tenant_id): o sistema e'
 * multi-empresa e nenhuma consulta pode misturar dados de empresas.
 *
 * So' roda no MySQL: o SQLite (ambiente local e testes) nao tem stored
 * procedures. Escritas pra MySQL 5.7 (versao da HostGator): sem CTE nem
 * funcoes de janela.
 */
return new class extends Migration
{
    private const PROCEDURES = [
        'sp_vendas_por_periodo',
        'sp_ajustar_estoque',
        'sp_clientes_inadimplentes',
        'sp_produtos_mais_vendidos',
    ];

    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $this->dropProcedures();

        // 1) Resumo de vendas concluidas num periodo (datas inclusivas).
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_vendas_por_periodo(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_inicio DATE,
                IN p_fim DATE
            )
            BEGIN
                SELECT
                    COUNT(*) AS quantidade_vendas,
                    COALESCE(SUM(v.total_amount), 0) AS total_vendido,
                    COALESCE(ROUND(AVG(v.total_amount), 2), 0) AS ticket_medio,
                    COALESCE(SUM(v.discount_amount), 0) AS total_descontos
                FROM sales v
                WHERE v.tenant_id = p_tenant_id
                  AND v.status = 'completed'
                  AND v.created_at >= p_inicio
                  AND v.created_at < DATE_ADD(p_fim, INTERVAL 1 DAY);
            END
            SQL);

        // 2) Ajuste de estoque com kardex, numa transacao. p_quantidade e' a
        // variacao: positiva entra, negativa sai. Grava o movimento como
        // 'in' ou 'out' (mesmo padrao do sistema) e devolve o novo saldo.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_ajustar_estoque(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_produto_id BIGINT UNSIGNED,
                IN p_quantidade DECIMAL(12,3),
                IN p_motivo VARCHAR(255),
                IN p_usuario_id BIGINT UNSIGNED,
                OUT p_novo_saldo DECIMAL(12,3)
            )
            BEGIN
                DECLARE v_saldo_atual DECIMAL(12,3);
                DECLARE v_custo DECIMAL(12,2);

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                IF p_quantidade IS NULL OR p_quantidade = 0 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Informe uma quantidade diferente de zero.';
                END IF;

                START TRANSACTION;

                -- Trava a linha do produto: dois ajustes ao mesmo tempo nao se atropelam.
                SELECT current_stock, cost_price INTO v_saldo_atual, v_custo
                FROM products
                WHERE id = p_produto_id AND tenant_id = p_tenant_id AND deleted_at IS NULL
                FOR UPDATE;

                IF v_saldo_atual IS NULL THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produto não encontrado nesta empresa.';
                END IF;

                IF v_saldo_atual + p_quantidade < 0 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Estoque insuficiente para essa saída.';
                END IF;

                SET p_novo_saldo = v_saldo_atual + p_quantidade;

                UPDATE products
                SET current_stock = p_novo_saldo, updated_at = NOW()
                WHERE id = p_produto_id;

                INSERT INTO stock_movements
                    (tenant_id, product_id, user_id, type, quantity, unit_price,
                     previous_stock, current_stock, notes, created_at, updated_at)
                VALUES
                    (p_tenant_id, p_produto_id, p_usuario_id,
                     IF(p_quantidade > 0, 'in', 'out'), ABS(p_quantidade), COALESCE(v_custo, 0),
                     v_saldo_atual, p_novo_saldo, p_motivo, NOW(), NOW());

                COMMIT;
            END
            SQL);

        // 3) Clientes com titulos vencidos ha mais de p_dias dias, do maior saldo pro menor.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_clientes_inadimplentes(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_dias INT
            )
            BEGIN
                SELECT
                    c.id AS cliente_id,
                    c.name AS cliente,
                    c.phone AS telefone,
                    COUNT(r.id) AS titulos_vencidos,
                    SUM(r.amount - r.paid_amount) AS total_em_aberto,
                    MIN(r.due_date) AS vencimento_mais_antigo,
                    DATEDIFF(CURRENT_DATE, MIN(r.due_date)) AS dias_de_atraso
                FROM customer_receivables r
                JOIN customers c ON c.id = r.customer_id
                WHERE r.tenant_id = p_tenant_id
                  AND r.status IN ('pending', 'overdue')
                  AND r.amount > r.paid_amount
                  AND r.due_date < DATE_SUB(CURRENT_DATE, INTERVAL GREATEST(COALESCE(p_dias, 0), 0) DAY)
                GROUP BY c.id, c.name, c.phone
                ORDER BY total_em_aberto DESC;
            END
            SQL);

        // 4) Ranking dos produtos mais vendidos no periodo (por quantidade).
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_produtos_mais_vendidos(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_inicio DATE,
                IN p_fim DATE,
                IN p_limite INT
            )
            BEGIN
                DECLARE v_limite INT DEFAULT 10;
                IF p_limite IS NOT NULL AND p_limite > 0 THEN
                    SET v_limite = p_limite;
                END IF;

                SELECT
                    p.code AS codigo,
                    p.name AS produto,
                    SUM(i.quantity) AS quantidade_vendida,
                    SUM(i.total_price) AS faturamento,
                    COUNT(DISTINCT i.sale_id) AS numero_vendas
                FROM sale_items i
                JOIN sales v ON v.id = i.sale_id
                JOIN products p ON p.id = i.product_id
                WHERE v.tenant_id = p_tenant_id
                  AND v.status = 'completed'
                  AND v.created_at >= p_inicio
                  AND v.created_at < DATE_ADD(p_fim, INTERVAL 1 DAY)
                GROUP BY p.id, p.code, p.name
                ORDER BY quantidade_vendida DESC, faturamento DESC
                LIMIT v_limite;
            END
            SQL);
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->dropProcedures();
        }
    }

    private function dropProcedures(): void
    {
        foreach (self::PROCEDURES as $procedure) {
            DB::unprepared("DROP PROCEDURE IF EXISTS {$procedure}");
        }
    }
};
