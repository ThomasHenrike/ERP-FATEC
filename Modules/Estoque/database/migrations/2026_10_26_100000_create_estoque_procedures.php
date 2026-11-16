<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Stored Procedures do modulo de Estoque (documentadas em wiki/Stored-Procedures.md).
 *
 * Regras seguidas por todas:
 * - recebem p_tenant_id e filtram por tenant_id (uma empresa nunca ve a outra);
 * - ignoram produtos com deleted_at preenchido;
 * - "exclusao" de produto e' logica (deleted_at = UTC_TIMESTAMP()), nunca DELETE;
 * - sem SQL dinamico: ordenacao escolhida usa CASE com lista fechada;
 * - nao abrem transacao propria, pra poderem rodar dentro de uma transacao do Laravel.
 *
 * So' existem no MySQL/MariaDB; no SQLite (ambiente local e testes) a migration e' ignorada.
 * Nenhuma tela do sistema chama estas procedures - elas servem a relatorios e consultas diretas.
 */
return new class extends Migration
{
    private const PROCEDURES = [
        'sp_produto_por_id',
        'sp_pdv_buscar_produto',
        'sp_produtos_por_categoria',
        'sp_produtos_por_faixa_preco',
        'sp_listar_produtos_ordenados',
        'sp_produtos_abaixo_estoque_minimo',
        'sp_kardex_produto',
        'sp_inserir_produto',
        'sp_atualizar_preco_produto',
        'sp_excluir_produto',
    ];

    public function up(): void
    {
        if (!$this->supportsProcedures()) {
            return;
        }

        $this->dropProcedures();

        // Busca um produto pelo ID (exercicio 7).
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_produto_por_id(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_product_id BIGINT UNSIGNED
            )
            BEGIN
                SELECT p.id, p.code, p.name, p.brand, p.sku, p.barcode, p.unit,
                       c.name AS categoria, p.cost_price, p.price_retail, p.price_resale,
                       p.price_wholesale, p.current_stock, p.min_stock, p.is_active
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id AND c.tenant_id = p.tenant_id
                WHERE p.tenant_id = p_tenant_id
                  AND p.id = p_product_id
                  AND p.deleted_at IS NULL;
            END
            SQL);

        // Busca do PDV: codigo, codigo de barras ou SKU exatos, ou parte do nome.
        // As correspondencias exatas vem primeiro, como o leitor de codigo de barras espera.
        // Os curingas do LIKE (% e _) digitados pelo usuario sao tratados como texto.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_pdv_buscar_produto(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_termo VARCHAR(255) CHARACTER SET utf8mb4
            )
            BEGIN
                DECLARE v_termo VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT TRIM(COALESCE(p_termo, ''));
                DECLARE v_padrao VARCHAR(520) CHARACTER SET utf8mb4 DEFAULT CONCAT('%',
                    REPLACE(REPLACE(REPLACE(v_termo, '\\', '\\\\'), '%', '\\%'), '_', '\\_'), '%');

                SELECT p.id, p.code, p.name, p.sku, p.barcode, p.unit, p.cost_price,
                       p.price_retail, p.price_resale, p.price_wholesale,
                       p.current_stock, p.min_stock
                FROM products p
                WHERE p.tenant_id = p_tenant_id
                  AND p.deleted_at IS NULL
                  AND p.is_active = 1
                  AND v_termo <> ''
                  AND (
                      CAST(p.code AS CHAR) = v_termo COLLATE utf8mb4_unicode_ci
                      OR p.barcode = v_termo COLLATE utf8mb4_unicode_ci
                      OR p.sku = v_termo COLLATE utf8mb4_unicode_ci
                      OR p.name LIKE v_padrao COLLATE utf8mb4_unicode_ci
                  )
                ORDER BY
                    CASE
                        WHEN CAST(p.code AS CHAR) = v_termo COLLATE utf8mb4_unicode_ci
                          OR p.barcode = v_termo COLLATE utf8mb4_unicode_ci
                          OR p.sku = v_termo COLLATE utf8mb4_unicode_ci THEN 0
                        ELSE 1
                    END,
                    p.name
                LIMIT 50;
            END
            SQL);

        // Filtra produtos ativos de uma categoria (exercicio 9).
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_produtos_por_categoria(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_category_id BIGINT UNSIGNED
            )
            BEGIN
                SELECT p.id, p.code, p.name, c.name AS categoria, p.unit,
                       p.price_retail, p.price_resale, p.price_wholesale, p.current_stock
                FROM products p
                JOIN categories c ON c.id = p.category_id AND c.tenant_id = p.tenant_id
                WHERE p.tenant_id = p_tenant_id
                  AND p.category_id = p_category_id
                  AND p.deleted_at IS NULL
                  AND p.is_active = 1
                ORDER BY p.name;
            END
            SQL);

        // Produtos com preco dentro de uma faixa (exercicios 13 e 14). A tabela de preco
        // segue Product::getPriceForTable: revenda/atacado vazios ou zero usam o varejo.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_produtos_por_faixa_preco(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_preco_min DECIMAL(12,2),
                IN p_preco_max DECIMAL(12,2),
                IN p_tabela VARCHAR(10) CHARACTER SET utf8mb4
            )
            BEGIN
                DECLARE v_tabela VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT LOWER(COALESCE(p_tabela, 'retail'));

                IF v_tabela NOT IN ('retail', 'resale', 'wholesale') THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Tabela de preco invalida. Use retail, resale ou wholesale.';
                END IF;

                IF p_preco_min IS NULL OR p_preco_max IS NULL OR p_preco_min > p_preco_max THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Faixa de preco invalida: informe minimo menor ou igual ao maximo.';
                END IF;

                SELECT id, code, name, unit, preco, current_stock
                FROM (
                    SELECT p.id, p.code, p.name, p.unit, p.current_stock,
                           CASE v_tabela
                               WHEN 'resale' THEN IF(COALESCE(p.price_resale, 0) > 0, p.price_resale, p.price_retail)
                               WHEN 'wholesale' THEN IF(COALESCE(p.price_wholesale, 0) > 0, p.price_wholesale, p.price_retail)
                               ELSE p.price_retail
                           END AS preco
                    FROM products p
                    WHERE p.tenant_id = p_tenant_id
                      AND p.deleted_at IS NULL
                      AND p.is_active = 1
                ) AS precos
                WHERE preco BETWEEN p_preco_min AND p_preco_max
                ORDER BY preco, name;
            END
            SQL);

        // Lista os produtos ordenados por um campo escolhido (exercicio 16). Um CASE por
        // campo e direcao, pra que numeros sejam ordenados como numeros e nao como texto.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_listar_produtos_ordenados(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_campo VARCHAR(10) CHARACTER SET utf8mb4,
                IN p_direcao VARCHAR(4) CHARACTER SET utf8mb4
            )
            BEGIN
                DECLARE v_campo VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT LOWER(COALESCE(p_campo, 'nome'));
                DECLARE v_direcao VARCHAR(4) CHARACTER SET utf8mb4 DEFAULT UPPER(COALESCE(p_direcao, 'ASC'));

                IF v_campo NOT IN ('nome', 'preco', 'estoque', 'codigo') THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Campo de ordenacao invalido. Use nome, preco, estoque ou codigo.';
                END IF;

                IF v_direcao NOT IN ('ASC', 'DESC') THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Direcao invalida. Use ASC ou DESC.';
                END IF;

                SELECT p.id, p.code, p.name, p.unit, p.price_retail, p.current_stock
                FROM products p
                WHERE p.tenant_id = p_tenant_id
                  AND p.deleted_at IS NULL
                  AND p.is_active = 1
                ORDER BY
                    CASE WHEN v_campo = 'nome' AND v_direcao = 'ASC' THEN p.name END ASC,
                    CASE WHEN v_campo = 'nome' AND v_direcao = 'DESC' THEN p.name END DESC,
                    CASE WHEN v_campo = 'preco' AND v_direcao = 'ASC' THEN p.price_retail END ASC,
                    CASE WHEN v_campo = 'preco' AND v_direcao = 'DESC' THEN p.price_retail END DESC,
                    CASE WHEN v_campo = 'estoque' AND v_direcao = 'ASC' THEN p.current_stock END ASC,
                    CASE WHEN v_campo = 'estoque' AND v_direcao = 'DESC' THEN p.current_stock END DESC,
                    CASE WHEN v_campo = 'codigo' AND v_direcao = 'ASC' THEN p.code END ASC,
                    CASE WHEN v_campo = 'codigo' AND v_direcao = 'DESC' THEN p.code END DESC,
                    p.name;
            END
            SQL);

        // Reposicao: mesmo criterio de Product::isLowStock (minimo definido e saldo <= minimo).
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_produtos_abaixo_estoque_minimo(
                IN p_tenant_id BIGINT UNSIGNED
            )
            BEGIN
                SELECT p.id, p.code, p.name, c.name AS categoria, p.unit,
                       p.current_stock, p.min_stock,
                       (p.min_stock - p.current_stock) AS falta
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id AND c.tenant_id = p.tenant_id
                WHERE p.tenant_id = p_tenant_id
                  AND p.deleted_at IS NULL
                  AND p.is_active = 1
                  AND p.min_stock > 0
                  AND p.current_stock <= p.min_stock
                ORDER BY falta DESC, p.name;
            END
            SQL);

        // Extrato (Kardex) de um produto num periodo (exercicio 15). A data final e' inclusiva.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_kardex_produto(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_product_id BIGINT UNSIGNED,
                IN p_data_inicio DATE,
                IN p_data_fim DATE
            )
            BEGIN
                IF p_data_inicio IS NULL OR p_data_fim IS NULL OR p_data_inicio > p_data_fim THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Periodo invalido: informe data inicial menor ou igual a final.';
                END IF;

                SELECT m.id, m.created_at AS data, p.code, p.name AS produto, m.type,
                       m.quantity, m.previous_stock, m.current_stock, u.name AS usuario, m.notes
                FROM stock_movements m
                JOIN products p ON p.id = m.product_id AND p.tenant_id = m.tenant_id
                LEFT JOIN users u ON u.id = m.user_id
                WHERE m.tenant_id = p_tenant_id
                  AND m.product_id = p_product_id
                  AND p.deleted_at IS NULL
                  AND m.created_at >= p_data_inicio
                  AND m.created_at < p_data_fim + INTERVAL 1 DAY
                ORDER BY m.created_at, m.id;
            END
            SQL);

        // Cadastra um produto (exercicio 10). O codigo interno segue o model Product:
        // maior codigo da empresa + 1, contando inclusive os excluidos (o indice unico
        // tenant_id + code tambem os inclui e barra codigo repetido). O MAX fica num SELECT
        // proprio: dentro de INSERT ... SELECT junto com variaveis locais, o MariaDB acusa
        // erro 1140 (ONLY_FULL_GROUP_BY) a partir da segunda chamada na mesma conexao.
        // A margem de varejo e' o markup sobre o custo, igual a tela de produto.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_inserir_produto(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_category_id BIGINT UNSIGNED,
                IN p_name VARCHAR(255) CHARACTER SET utf8mb4,
                IN p_unit VARCHAR(6) CHARACTER SET utf8mb4,
                IN p_ncm VARCHAR(8) CHARACTER SET utf8mb4,
                IN p_cost_price DECIMAL(12,2),
                IN p_price_retail DECIMAL(12,2),
                IN p_min_stock DECIMAL(12,3)
            )
            BEGIN
                DECLARE v_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT TRIM(COALESCE(p_name, ''));
                DECLARE v_cost DECIMAL(12,2) DEFAULT COALESCE(p_cost_price, 0);
                DECLARE v_code BIGINT UNSIGNED;

                IF NOT EXISTS (SELECT 1 FROM tenants WHERE id = p_tenant_id AND deleted_at IS NULL) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Empresa nao encontrada.';
                END IF;

                IF v_name = '' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Informe o nome do produto.';
                END IF;

                IF p_ncm IS NULL OR CHAR_LENGTH(p_ncm) <> 8 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'O NCM deve ter 8 digitos.';
                END IF;

                IF p_price_retail IS NULL OR p_price_retail < 0 OR v_cost < 0 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Precos nao podem ser negativos.';
                END IF;

                IF p_category_id IS NOT NULL AND NOT EXISTS (
                    SELECT 1 FROM categories WHERE id = p_category_id AND tenant_id = p_tenant_id
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Categoria nao pertence a esta empresa.';
                END IF;

                SELECT COALESCE(MAX(code), 0) + 1 INTO v_code
                FROM products
                WHERE tenant_id = p_tenant_id;

                INSERT INTO products (
                    tenant_id, code, category_id, name, unit, ncm,
                    cost_price, margin_retail, price_retail, min_stock,
                    is_active, created_at, updated_at
                ) VALUES (
                    p_tenant_id,
                    v_code,
                    p_category_id,
                    v_name,
                    COALESCE(NULLIF(TRIM(p_unit), ''), 'UN'),
                    p_ncm,
                    v_cost,
                    IF(v_cost > 0, LEAST(9999.99, GREATEST(-9999.99, ROUND((p_price_retail / v_cost - 1) * 100, 2))), 0),
                    p_price_retail,
                    COALESCE(p_min_stock, 0),
                    1, UTC_TIMESTAMP(), UTC_TIMESTAMP()
                );

                SELECT id, code, name, unit, cost_price, margin_retail, price_retail, min_stock
                FROM products
                WHERE id = LAST_INSERT_ID();
            END
            SQL);

        // Atualiza o preco de varejo (exercicio 11) e recalcula a margem, como faz a tela
        // de produto: com custo zero a margem fica como estava.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_atualizar_preco_produto(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_product_id BIGINT UNSIGNED,
                IN p_price_retail DECIMAL(12,2)
            )
            BEGIN
                IF p_price_retail IS NULL OR p_price_retail < 0 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'O preco nao pode ser negativo.';
                END IF;

                IF NOT EXISTS (
                    SELECT 1 FROM products
                    WHERE tenant_id = p_tenant_id AND id = p_product_id AND deleted_at IS NULL
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produto nao encontrado nesta empresa.';
                END IF;

                UPDATE products
                SET margin_retail = IF(cost_price > 0,
                        LEAST(9999.99, GREATEST(-9999.99, ROUND((p_price_retail / cost_price - 1) * 100, 2))),
                        margin_retail),
                    price_retail = p_price_retail,
                    updated_at = UTC_TIMESTAMP()
                WHERE tenant_id = p_tenant_id
                  AND id = p_product_id
                  AND deleted_at IS NULL;

                SELECT id, code, name, cost_price, margin_retail, price_retail, updated_at
                FROM products
                WHERE tenant_id = p_tenant_id AND id = p_product_id;
            END
            SQL);

        // Exclusao logica (exercicio 12): o produto some das consultas, mas o historico de
        // vendas e o Kardex continuam apontando pra ele.
        DB::unprepared(<<<'SQL'
            CREATE PROCEDURE sp_excluir_produto(
                IN p_tenant_id BIGINT UNSIGNED,
                IN p_product_id BIGINT UNSIGNED
            )
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM products
                    WHERE tenant_id = p_tenant_id AND id = p_product_id AND deleted_at IS NULL
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produto nao encontrado nesta empresa.';
                END IF;

                UPDATE products
                SET deleted_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
                WHERE tenant_id = p_tenant_id
                  AND id = p_product_id
                  AND deleted_at IS NULL;

                SELECT id, code, name, deleted_at
                FROM products
                WHERE tenant_id = p_tenant_id AND id = p_product_id;
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
