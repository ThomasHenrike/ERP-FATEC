# Views

Documentação das views (visões) de banco de dados do ERP-FATEC. O sistema possui **6 views**,
todas no banco MySQL, criadas pela migration
`database/migrations/2026_10_07_000000_create_database_views.php`.

## O que é uma view

Uma view é uma consulta `SELECT` salva no banco com um nome. Ela se comporta como uma tabela
na hora de consultar (`SELECT * FROM nome_da_view`), mas não guarda dados próprios: toda vez que
é consultada, o banco executa o `SELECT` original sobre as tabelas reais. Por isso o resultado
está sempre atualizado.

## Vantagens

| Vantagem | Como aparece no ERP-FATEC |
|---|---|
| **Simplificação** | Uma consulta com três `JOIN`s vira `SELECT * FROM vw_vendas_detalhadas`. |
| **Segurança** | A view expõe só as colunas permitidas; senhas e certificado digital ficam fora. |
| **Reaproveitamento** | A regra "estoque abaixo do mínimo" fica escrita em um lugar só. |
| **Independência** | Se uma tabela mudar, ajusta-se a view e quem a consulta não precisa mudar. |

## Views do sistema

| View | Finalidade | Tabelas usadas | Objetivo |
|---|---|---|---|
| `vw_estoque_abaixo_minimo` | Produtos ativos com estoque abaixo do mínimo | `products`, `categories`, `suppliers` | Simplificação |
| `vw_vendas_detalhadas` | Venda com cliente, vendedor e operador | `sales`, `users`, `customers`, `employees` | Simplificação |
| `vw_contas_receber_abertas` | Parcelas do crediário em aberto, com saldo e dias de atraso | `customer_receivables`, `customers`, `sales` | Simplificação |
| `vw_kardex` | Movimentações de estoque com nome do produto e do usuário | `stock_movements`, `products`, `users` | Simplificação |
| `vw_usuarios_seguro` | Usuários e suas empresas, sem senha nem token | `users`, `tenant_user`, `tenants` | Segurança |
| `vw_empresas_publico` | Dados cadastrais das empresas, sem certificado nem cobrança | `tenants` | Segurança |

Todas as views de simplificação trazem a coluna `tenant_id`, porque o sistema é multi-empresa:
para ver os dados de uma empresa só, filtra-se por ela (`WHERE tenant_id = 1`).

## Simplificação de consultas complexas

### vw_estoque_abaixo_minimo

Lista o que precisa ser reposto. Ignora produtos inativos e excluídos (soft delete).

```sql
CREATE VIEW vw_estoque_abaixo_minimo AS
SELECT p.tenant_id, p.code, p.name AS produto, c.name AS categoria,
       s.name AS fornecedor, p.current_stock, p.min_stock,
       (p.min_stock - p.current_stock) AS falta
FROM products p
LEFT JOIN categories c ON c.id = p.category_id
LEFT JOIN suppliers s ON s.id = p.supplier_id
WHERE p.deleted_at IS NULL AND p.is_active = 1
  AND p.current_stock < p.min_stock;
```

Exemplo de resultado com os dados de demonstração:

| code | produto | categoria | fornecedor | current_stock | min_stock | falta |
|---|---|---|---|---|---|---|
| 4 | Disjuntor Monopolar DIN 20A | Disjuntores e Quadros de Distribuicao | NULL | 4 | 20 | 16 |
| 8 | Lampada LED Bulbo 9W 6500K | Iluminacao e Lampadas | NULL | 0 | 40 | 40 |

### vw_vendas_detalhadas

Troca os códigos (`user_id`, `customer_id`, `seller_id`) pelos nomes. Cliente e vendedor usam
`LEFT JOIN` porque são opcionais na venda.

```sql
CREATE VIEW vw_vendas_detalhadas AS
SELECT v.tenant_id, v.code AS numero_venda, v.created_at AS data_venda,
       cl.name AS cliente, e.name AS vendedor, u.name AS operador,
       v.payment_method, v.subtotal, v.discount_amount, v.total_amount, v.status
FROM sales v
JOIN users u ON u.id = v.user_id
LEFT JOIN customers cl ON cl.id = v.customer_id
LEFT JOIN employees e ON e.id = v.seller_id;
```

Exemplo de resultado (venda a prazo de R$ 600,00 lançada para ilustrar — os dados de
demonstração não trazem vendas):

| numero_venda | data_venda | cliente | vendedor | operador | payment_method | total_amount | status |
|---|---|---|---|---|---|---|---|
| 1 | 2026-10-07 20:40:52 | Instalacoes Eletricas Rio Ltda | Joao Silva | Administrador do Sistema | on_account | 600 | completed |

### vw_contas_receber_abertas

Parcelas pendentes ou vencidas do crediário. Calcula o saldo que falta pagar e há quantos dias
a parcela está atrasada (zero se ainda não venceu).

```sql
CREATE VIEW vw_contas_receber_abertas AS
SELECT r.tenant_id, cl.name AS cliente, v.code AS numero_venda,
       r.installment_number, r.total_installments, r.due_date,
       (r.amount - r.paid_amount) AS saldo,
       GREATEST(DATEDIFF(CURRENT_DATE, r.due_date), 0) AS dias_atraso
FROM customer_receivables r
JOIN customers cl ON cl.id = r.customer_id
JOIN sales v ON v.id = r.sale_id
WHERE r.status IN ('pending', 'overdue');
```

Exemplo de resultado, consultado em 07/10/2026 (a mesma venda de exemplo, em 2 parcelas de
R$ 300,00: a primeira vencida e com R$ 100,00 já pagos, a segunda ainda a vencer):

| cliente | numero_venda | installment_number | total_installments | due_date | saldo | dias_atraso |
|---|---|---|---|---|---|---|
| Instalacoes Eletricas Rio Ltda | 1 | 1 | 2 | 2026-09-22 | 200 | 15 |
| Instalacoes Eletricas Rio Ltda | 1 | 2 | 2 | 2026-10-22 | 300 | 0 |

### vw_kardex

Ficha de movimentação de estoque já legível, com o saldo antes e depois de cada movimento.

```sql
CREATE VIEW vw_kardex AS
SELECT m.tenant_id, m.created_at AS data, p.code, p.name AS produto,
       m.type, m.quantity, m.previous_stock, m.current_stock, u.name AS usuario
FROM stock_movements m
JOIN products p ON p.id = m.product_id
LEFT JOIN users u ON u.id = m.user_id;
```

Exemplo de resultado com os dados de demonstração (3 das 14 linhas):

| data | code | produto | type | quantity | previous_stock | current_stock | usuario |
|---|---|---|---|---|---|---|---|
| 2026-10-07 20:40:44 | 1 | Cabo Flexivel 2,5mm² 750V (Rolo 100m) | in | 35 | 0 | 35 | Administrador do Sistema |
| 2026-10-07 20:40:44 | 2 | Cabo Flexivel 4,0mm² 750V (Rolo 100m) | in | 22 | 0 | 22 | Administrador do Sistema |
| 2026-10-07 20:40:44 | 3 | Disjuntor Bipolar DIN 40A | in | 60 | 0 | 60 | Administrador do Sistema |

## Segurança através de views

Uma view pode servir de "janela" para uma tabela sensível: o usuário do banco recebe permissão
na view, e não na tabela, e assim só enxerga as colunas que a view mostra.

### vw_usuarios_seguro

A tabela `users` guarda `password` e `remember_token`. A view deixa as duas de fora.

```sql
CREATE VIEW vw_usuarios_seguro AS
SELECT u.id, u.name, u.email, t.name AS empresa, tu.role, tu.is_active
FROM users u
JOIN tenant_user tu ON tu.user_id = u.id
JOIN tenants t ON t.id = tu.tenant_id;
```

Exemplo de resultado com os dados de demonstração — repare que não há coluna de senha:

| id | name | email | empresa | role | is_active |
|---|---|---|---|---|---|
| 1 | Administrador do Sistema | admin@erp.local | Empresa Matriz Servicos e Comercio LTDA | admin | 1 |
| 2 | Thiago | thiago@teste.erp | Empresa Teste - Thiago | admin | 1 |
| 3 | Matheus | matheusfelipe@teste.erp | Empresa Teste - Matheus Felipe | admin | 1 |
| 4 | Pedro | pedro@teste.erp | Empresa Teste - Pedro | admin | 1 |
| 5 | Leonardo | leonardo@teste.erp | Empresa Teste - Leonardo | admin | 1 |

### vw_empresas_publico

A tabela `tenants` guarda o caminho e a senha do certificado digital, além dos dados de
cobrança da plataforma. A view mostra só o cadastro básico.

```sql
CREATE VIEW vw_empresas_publico AS
SELECT id, name, trade_name, document, email, phone, city, state, is_active
FROM tenants
WHERE deleted_at IS NULL;
```

Exemplo de resultado com os dados de demonstração (3 das 5 linhas; as empresas de teste foram
cadastradas só com nome e documento, por isso os demais campos vêm `NULL`):

| id | name | trade_name | document | email | phone | city | state | is_active |
|---|---|---|---|---|---|---|---|---|
| 1 | Empresa Matriz Servicos e Comercio LTDA | Matriz Materiais Eletricos | 12.345.678/0001-90 | contato@empresamatriz.local | 11999998888 | NULL | NULL | 1 |
| 2 | Empresa Teste - Thiago | NULL | 11111111000101 | NULL | NULL | NULL | NULL | 1 |
| 4 | Empresa Teste - Pedro | NULL | 33333333000103 | NULL | NULL | NULL | NULL | 1 |

### Aplicando a restrição

Para a proteção valer, cria-se um usuário de banco que só tem acesso à view:

```sql
CREATE USER 'relatorio'@'localhost' IDENTIFIED BY 'uma-senha-forte';
GRANT SELECT ON thoma512_fatec.vw_usuarios_seguro TO 'relatorio'@'localhost';
```

Com isso, `relatorio` consegue rodar `SELECT * FROM vw_usuarios_seguro`, mas recebe erro de
permissão em `SELECT * FROM users`.

## Criação, alteração e remoção

| Operação | Comando |
|---|---|
| Criar | `CREATE VIEW nome AS SELECT ...` |
| Alterar | `ALTER VIEW nome AS SELECT ...` |
| Criar ou substituir | `CREATE OR REPLACE VIEW nome AS SELECT ...` |
| Remover | `DROP VIEW IF EXISTS nome` |
| Listar as views do banco | `SHOW FULL TABLES WHERE Table_type = 'VIEW'` |
| Ver o comando que criou | `SHOW CREATE VIEW nome` |

Exemplo de alteração — tirar e-mail e telefone da view de empresas:

```sql
ALTER VIEW vw_empresas_publico AS
SELECT id, name, trade_name, document, city, state, is_active
FROM tenants
WHERE deleted_at IS NULL;
```

Exemplo de remoção:

```sql
DROP VIEW IF EXISTS vw_kardex;
```

Remover uma view não apaga nenhum dado: as tabelas de origem continuam intactas.

## Como consultar

No MySQL Workbench ou no phpMyAdmin (aba **SQL**), com o banco do sistema selecionado:

```sql
SELECT * FROM vw_estoque_abaixo_minimo WHERE tenant_id = 1;

SELECT cliente, numero_venda, saldo, dias_atraso
FROM vw_contas_receber_abertas
ORDER BY dias_atraso DESC;

SELECT * FROM vw_kardex ORDER BY data DESC LIMIT 20;
```

No phpMyAdmin as views aparecem no grupo **Visões**, abaixo do grupo **Tabelas**, na árvore do
banco.

## Observações

- **As views nascem com o banco.** Elas são criadas pela migration, então `php artisan migrate`
  já as deixa prontas em qualquer instalação; `php artisan migrate:rollback` as remove.
- **MySQL e SQLite.** O sistema roda em MySQL na hospedagem e em SQLite no ambiente local. A única
  diferença entre os dois está em `dias_atraso`: `DATEDIFF` no MySQL e `julianday` no SQLite. A
  migration escolhe a versão certa sozinha.
- **Views vazias.** `vw_vendas_detalhadas` e `vw_contas_receber_abertas` só trazem linhas depois
  que existir ao menos uma venda (a segunda, uma venda a prazo).
- **A aplicação não depende delas.** As telas do sistema continuam consultando as tabelas pelo
  Eloquent; as views servem para relatórios e consultas diretas no banco.
