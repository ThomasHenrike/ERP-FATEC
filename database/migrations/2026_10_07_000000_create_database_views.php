<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const VIEWS = [
        'vw_estoque_abaixo_minimo',
        'vw_vendas_detalhadas',
        'vw_contas_receber_abertas',
        'vw_kardex',
        'vw_usuarios_seguro',
        'vw_empresas_publico',
    ];

    public function up(): void
    {
        $this->dropViews();

        // Simplificacao de consulta: produtos ativos com estoque abaixo do minimo.
        DB::statement(<<<'SQL'
            CREATE VIEW vw_estoque_abaixo_minimo AS
            SELECT p.tenant_id, p.code, p.name AS produto, c.name AS categoria,
                   s.name AS fornecedor, p.current_stock, p.min_stock,
                   (p.min_stock - p.current_stock) AS falta
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            WHERE p.deleted_at IS NULL AND p.is_active = 1
              AND p.current_stock < p.min_stock
            SQL);

        // Simplificacao de consulta: venda ja com cliente, vendedor e operador.
        DB::statement(<<<'SQL'
            CREATE VIEW vw_vendas_detalhadas AS
            SELECT v.tenant_id, v.code AS numero_venda, v.created_at AS data_venda,
                   cl.name AS cliente, e.name AS vendedor, u.name AS operador,
                   v.payment_method, v.subtotal, v.discount_amount, v.total_amount, v.status
            FROM sales v
            JOIN users u ON u.id = v.user_id
            LEFT JOIN customers cl ON cl.id = v.customer_id
            LEFT JOIN employees e ON e.id = v.seller_id
            SQL);

        // Crediario em aberto, com saldo e dias de atraso. O calculo de dias
        // muda por banco: DATEDIFF no MySQL, julianday no SQLite (local/testes).
        $diasAtraso = DB::getDriverName() === 'sqlite'
            ? "MAX(CAST(julianday('now', 'localtime') - julianday(r.due_date) AS INTEGER), 0)"
            : 'GREATEST(DATEDIFF(CURRENT_DATE, r.due_date), 0)';

        DB::statement(<<<SQL
            CREATE VIEW vw_contas_receber_abertas AS
            SELECT r.tenant_id, cl.name AS cliente, v.code AS numero_venda,
                   r.installment_number, r.total_installments, r.due_date,
                   (r.amount - r.paid_amount) AS saldo,
                   {$diasAtraso} AS dias_atraso
            FROM customer_receivables r
            JOIN customers cl ON cl.id = r.customer_id
            JOIN sales v ON v.id = r.sale_id
            WHERE r.status IN ('pending', 'overdue')
            SQL);

        // Kardex legivel: movimentacao com nome do produto e de quem registrou.
        DB::statement(<<<'SQL'
            CREATE VIEW vw_kardex AS
            SELECT m.tenant_id, m.created_at AS data, p.code, p.name AS produto,
                   m.type, m.quantity, m.previous_stock, m.current_stock, u.name AS usuario
            FROM stock_movements m
            JOIN products p ON p.id = m.product_id
            LEFT JOIN users u ON u.id = m.user_id
            SQL);

        // Seguranca: usuarios sem expor senha nem remember_token.
        DB::statement(<<<'SQL'
            CREATE VIEW vw_usuarios_seguro AS
            SELECT u.id, u.name, u.email, t.name AS empresa, tu.role, tu.is_active
            FROM users u
            JOIN tenant_user tu ON tu.user_id = u.id
            JOIN tenants t ON t.id = tu.tenant_id
            SQL);

        // Seguranca: empresas sem certificado digital nem dados de cobranca.
        DB::statement(<<<'SQL'
            CREATE VIEW vw_empresas_publico AS
            SELECT id, name, trade_name, document, email, phone, city, state, is_active
            FROM tenants
            WHERE deleted_at IS NULL
            SQL);
    }

    public function down(): void
    {
        $this->dropViews();
    }

    private function dropViews(): void
    {
        foreach (self::VIEWS as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view}");
        }
    }
};
