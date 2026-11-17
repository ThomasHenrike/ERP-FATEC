# Functions e Triggers

Documentação das functions (funções armazenadas) e triggers (gatilhos) do banco de dados do
ERP-FATEC. O sistema possui **2 functions** e **2 triggers**, todas no banco MySQL/MariaDB,
criadas pelas migrations:

- `Modules/Estoque/database/migrations/2026_11_17_100000_create_estoque_functions_triggers.php`
- `Modules/Vendas/database/migrations/2026_11_17_100001_create_vendas_functions_triggers.php`

| Tipo | Nome | Módulo | Finalidade |
|---|---|---|---|
| Function | `fn_margem_lucro` | Estoque | Markup (%) de um preço sobre o custo |
| Function | `fn_saldo_devedor_cliente` | Vendas | Quanto o cliente ainda deve no crediário |
| Trigger | `trg_produto_historico_preco` | Estoque | Grava o histórico de mudanças de custo e preço |
| Trigger | `trg_venda_cancelada_log` | Vendas | Grava um registro de cada venda cancelada |

## O que é uma function

Uma function é um bloco de SQL salvo no banco com um nome, que recebe parâmetros e **devolve um
único valor**. Diferente da stored procedure (chamada com `CALL`), a function é usada **dentro de
um comando SQL**, como qualquer função nativa (`ROUND`, `SUM`, `NOW`):

```sql
SELECT name, fn_margem_lucro(cost_price, price_retail) FROM products;
```

| | Function | Stored procedure |
|---|---|---|
| Como chama | Dentro de um `SELECT`, `WHERE`, `ORDER BY`... | `CALL nome(...)` |
| Retorno | Um único valor (`RETURNS`) | Nenhum, um ou vários conjuntos de linhas |
| Uso típico | Cálculo reaproveitável em várias consultas | Rotina completa (consulta, inserção, transação) |

## O que é uma trigger

Uma trigger é um bloco de SQL que o banco **executa sozinho** quando acontece um evento numa
tabela: `INSERT`, `UPDATE` ou `DELETE`, antes (`BEFORE`) ou depois (`AFTER`) da alteração. Ninguém
chama a trigger: ela dispara a cada linha alterada. Dentro dela, `OLD` é a linha como era antes e
`NEW` é a linha como ficou.

Como a trigger fica no banco, ela vale para **qualquer origem** da alteração: a tela do sistema, o
phpMyAdmin, uma stored procedure ou um script. Por isso é o lugar certo para auditoria (registrar o
que mudou).

## Vantagens

| Vantagem | Como aparece no ERP-FATEC |
|---|---|
| **Regra num lugar só** | A conta da margem é a mesma da tela de produto; quem consulta o banco não precisa reescrevê-la. |
| **Reaproveitamento** | `fn_saldo_devedor_cliente` serve a qualquer relatório, view ou procedure que precise do saldo do cliente. |
| **Auditoria automática** | O histórico de preços e o registro de cancelamentos são gravados pelo próprio banco, mesmo se a alteração não vier do sistema. |
| **Nada a esquecer** | Nenhuma tela precisa lembrar de gravar o histórico: a trigger faz isso a cada `UPDATE`. |

## Regras seguidas

| Regra | Como aparece no código |
|---|---|
| **Triggers só registram** | As duas triggers apenas inserem numa tabela própria (`product_price_history` e `sale_cancellation_logs`). Elas **nunca bloqueiam nem alteram** o produto ou a venda, para não interferir no cadastro nem no PDV. |
| **Disparam só quando importa** | A trigger de preço só grava se custo ou algum preço mudou (a baixa de estoque de cada venda também faz `UPDATE` em `products`, mas não gera histórico). A de cancelamento só grava na passagem para `canceled`. |
| **Multi-empresa** | Os registros guardam a empresa (`tenant_id`); `fn_saldo_devedor_cliente` recebe a empresa como primeiro parâmetro, igual às procedures. |
| **Tipos reais das colunas** | IDs `BIGINT UNSIGNED` e valores `DECIMAL(12,2)`, iguais às tabelas. |
| **Horário de Brasília** | As triggers usam `NOW()`. O sistema abre a conexão com o MySQL no fuso `-03:00`, então a hora gravada pela trigger é a mesma gravada pelo sistema. |
| **Comparação segura com nulo** | A trigger de preço usa `<=>` (igualdade que trata `NULL`), então trocar um preço vazio por um valor também é registrado. |
| **Reversível** | A migration tem `down()`: remove as triggers, as functions e as tabelas de histórico. |

## Dados usados nos exemplos

Os exemplos abaixo foram executados no banco de produção (HostGator, MySQL 5.7) em 17/11/2026, na
empresa **Matriz Materiais Elétricos** (`tenant_id = 1`). As alterações que disparam as triggers
foram feitas pelas telas do sistema: o gerente mudou o preço da Fita Isolante e depois voltou ao
valor original, e uma venda de teste foi cancelada no histórico de vendas.

---

## Functions

### fn_margem_lucro

Devolve o **markup em %** de um preço sobre o custo: `(preço − custo) ÷ custo × 100`, arredondado
em 2 casas. É a mesma conta da tela de cadastro de produto, por isso o resultado bate com a coluna
`margin_retail` gravada pelo sistema. Se o custo for zero, negativo ou nulo (ou o preço for nulo),
devolve `NULL` em vez de dar erro de divisão por zero.

```sql
SELECT code, name, cost_price, price_retail, margin_retail,
       fn_margem_lucro(cost_price, price_retail) AS fn_margem
FROM products
WHERE tenant_id = 1 AND code IN (1, 3, 6, 11, 22)
ORDER BY code;
```

| code | name | cost_price | price_retail | margin_retail | fn_margem |
|---|---|---|---|---|---|
| 1 | Cabo Flexivel 2,5mm² 750V (Rolo 100m) | 145.00 | 210.25 | 45.00 | **45.00** |
| 3 | Disjuntor Bipolar DIN 40A | 22.00 | 34.10 | 55.00 | **55.00** |
| 6 | Tomada 2P+T 10A Branca | 6.50 | 10.40 | 60.00 | **60.00** |
| 11 | Fita Isolante 19mm x 20m | 4.20 | 6.93 | 65.00 | **65.00** |
| 22 | Refletor LED 50W Bivolt IP65 | 45.00 | 65.25 | 45.00 | **45.00** |

Casos de borda:

```sql
SELECT fn_margem_lucro(100, 150) AS a,   -- vende com 50% de markup
       fn_margem_lucro(10, 9)    AS b,   -- vende abaixo do custo
       fn_margem_lucro(0, 10)    AS c;   -- sem custo: não dá para calcular
```

| a | b | c |
|---|---|---|
| 50.00 | -10.00 | NULL |

<details>
<summary>Código SQL</summary>

```sql
DELIMITER $$
CREATE FUNCTION fn_margem_lucro(p_custo DECIMAL(12,2), p_preco DECIMAL(12,2))
RETURNS DECIMAL(12,2)
DETERMINISTIC
NO SQL
BEGIN
    IF p_custo IS NULL OR p_custo <= 0 OR p_preco IS NULL THEN
        RETURN NULL;
    END IF;
    RETURN ROUND((p_preco - p_custo) / p_custo * 100, 2);
END $$
DELIMITER ;
```

`DETERMINISTIC` informa ao banco que os mesmos parâmetros sempre dão o mesmo resultado, e
`NO SQL` que a função não lê tabelas: é só uma conta.

</details>

### fn_saldo_devedor_cliente

Devolve **quanto o cliente ainda deve** no crediário (vendas "A Prazo"): soma, dos títulos ainda
pendentes, o valor menos o que já foi pago. Títulos quitados (`paid`) e cancelados (`canceled`) não
entram. É o mesmo critério da tela **Vendas > Prazos**. Recebe a empresa como primeiro parâmetro:
o mesmo cliente consultado em outra empresa devolve zero.

```sql
SELECT c.id, c.name, fn_saldo_devedor_cliente(1, c.id) AS saldo_devedor
FROM customers c
WHERE c.tenant_id = 1 AND fn_saldo_devedor_cliente(1, c.id) > 0
ORDER BY saldo_devedor DESC;
```

| id | name | saldo_devedor |
|---|---|---|
| 27 | cliente eletrecista | 3750.00 |
| 20 | Cleber | 343.74 |
| 6 | Servicos Eletricos do Vale Ltda | 183.34 |
| 25 | eletrecista teste | 0.40 |

Conferência com os títulos pendentes da tabela `customer_receivables`:

| customer_id | títulos | valor | pago |
|---|---|---|---|
| 27 | 2 | 3750.00 | 0.00 |
| 20 | 3 | 343.74 | 0.00 |
| 6 | 10 | 183.34 | 0.00 |
| 25 | 2 | 0.40 | 0.00 |

E o isolamento por empresa: `SELECT fn_saldo_devedor_cliente(2, 27);` devolve **0.00**, porque o
cliente 27 é da empresa 1.

<details>
<summary>Código SQL</summary>

```sql
DELIMITER $$
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
END $$
DELIMITER ;
```

`READS SQL DATA` informa que a função lê tabelas, e `NOT DETERMINISTIC` que o resultado muda
conforme os títulos mudam.

</details>

---

## Triggers

### trg_produto_historico_preco

Dispara **depois de cada `UPDATE` na tabela `products`**. Se o custo ou algum dos preços de venda
(varejo, revenda, atacado) mudou, grava uma linha em `product_price_history` com o valor antigo e
o novo de cada um e a data/hora. Se nada disso mudou (por exemplo, a baixa de estoque de uma venda
ou a troca do nome), não grava nada.

Exemplo: pela tela **Estoque > Produtos > Editar**, o gerente subiu o preço de varejo da Fita
Isolante de R$ 6,93 para R$ 8,43 e, em seguida, voltou para R$ 6,93. As duas alterações ficaram
registradas:

```sql
SELECT h.id, p.name AS produto, h.old_price_retail AS preco_antigo, h.new_price_retail AS preco_novo,
       h.old_cost_price AS custo_antigo, h.new_cost_price AS custo_novo, h.changed_at
FROM product_price_history h
JOIN products p ON p.id = h.product_id
ORDER BY h.id;
```

| id | produto | preco_antigo | preco_novo | custo_antigo | custo_novo | changed_at |
|---|---|---|---|---|---|---|
| 3 | Fita Isolante 19mm x 20m | 6.93 | 8.43 | 4.20 | 4.20 | 2026-11-17 19:36:24 |
| 4 | Fita Isolante 19mm x 20m | 8.43 | 6.93 | 4.20 | 4.20 | 2026-11-17 19:36:26 |

Tabela de histórico:

| Coluna | Conteúdo |
|---|---|
| `tenant_id`, `product_id` | Empresa e produto |
| `old_cost_price` / `new_cost_price` | Custo antes e depois |
| `old_price_retail` / `new_price_retail` | Preço de varejo antes e depois |
| `old_price_resale` / `new_price_resale` | Preço de revenda antes e depois |
| `old_price_wholesale` / `new_price_wholesale` | Preço de atacado antes e depois |
| `changed_at` | Quando mudou |

<details>
<summary>Código SQL</summary>

```sql
DELIMITER $$
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
END $$
DELIMITER ;
```

</details>

### trg_venda_cancelada_log

Dispara **depois de cada `UPDATE` na tabela `sales`**. Quando a venda passa para o status
`canceled` (e não estava cancelada antes), grava uma linha em `sale_cancellation_logs` com o
número da venda, o valor, o motivo informado e quando aconteceu. Alterar de novo uma venda que já
estava cancelada não duplica o registro, e alterar uma venda concluída não grava nada.

Exemplo: a venda de teste **#36** foi cancelada pela tela **Vendas > Histórico** com o motivo
"Venda de teste pos-deploy":

```sql
SELECT id, sale_code, total_amount, cancel_reason, canceled_at, logged_at
FROM sale_cancellation_logs
WHERE sale_code = 36;
```

| id | sale_code | total_amount | cancel_reason | canceled_at | logged_at |
|---|---|---|---|---|---|
| 2 | 36 | 137.75 | Venda de teste pos-deploy | 2026-11-17 19:36:16 | 2026-11-17 19:36:16 |

O `canceled_at` é gravado pelo sistema e o `logged_at` pela trigger: os dois batem, porque o
sistema e o banco usam o mesmo fuso (horário de Brasília).

Tabela de registro:

| Coluna | Conteúdo |
|---|---|
| `tenant_id`, `sale_id` | Empresa e venda |
| `sale_code` | Número da venda mostrado ao usuário |
| `total_amount` | Valor da venda |
| `cancel_reason` | Motivo digitado no cancelamento |
| `canceled_at` | Quando o sistema cancelou |
| `logged_at` | Quando a trigger registrou |

<details>
<summary>Código SQL</summary>

```sql
DELIMITER $$
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
END $$
DELIMITER ;
```

</details>

---

## Como consultar

```sql
-- Functions e procedures do banco
SELECT ROUTINE_TYPE, ROUTINE_NAME
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE();

-- Triggers do banco
SELECT TRIGGER_NAME, ACTION_TIMING, EVENT_MANIPULATION, EVENT_OBJECT_TABLE
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE();
```

| TRIGGER_NAME | ACTION_TIMING | EVENT_MANIPULATION | EVENT_OBJECT_TABLE |
|---|---|---|---|
| trg_produto_historico_preco | AFTER | UPDATE | products |
| trg_venda_cancelada_log | AFTER | UPDATE | sales |

Também funcionam `SHOW FUNCTION STATUS WHERE Db = DATABASE();`, `SHOW TRIGGERS;` e
`SHOW CREATE FUNCTION fn_margem_lucro;` (mostra o código salvo).

## Criação, alteração e remoção

As functions e triggers são criadas pelas migrations do Laravel (`php artisan migrate`), que
primeiro removem a versão anterior (`DROP ... IF EXISTS`) e depois criam de novo. No MySQL não
existe `ALTER` para o corpo de uma function ou trigger: para alterar, remove e cria de novo.

```sql
DROP TRIGGER IF EXISTS trg_venda_cancelada_log;
DROP FUNCTION IF EXISTS fn_saldo_devedor_cliente;
```

Para desfazer tudo: `php artisan migrate:rollback` nas duas migrations remove as triggers, as
functions e as tabelas de histórico.

## Testes

Cada function e trigger tem testes automatizados que rodam no MySQL:

- `Modules/Estoque/tests/Feature/ProductFunctionsTriggersTest.php`: a margem bate com a tela
  (incluindo arredondamento e casos sem custo); mudar o preço pela tela de produto grava o
  histórico com o horário do sistema; baixa de estoque, nome e NCM não geram histórico; cada mudança
  de preço gera uma linha nova.
- `Modules/Vendas/tests/Feature/SaleFunctionsTriggersTest.php`: o saldo acompanha a venda a prazo,
  a quitação de uma parcela e o cancelamento; o saldo é isolado por empresa; cancelar uma venda grava
  um único registro com o horário do sistema; alterar uma venda concluída não grava nada.

## Conclusão

As functions deixam cálculos do negócio (margem e saldo devedor) prontos para usar em qualquer
consulta, com a mesma regra do sistema e sem repetir código. As triggers fazem a auditoria
automaticamente: toda mudança de preço e todo cancelamento ficam registrados pelo próprio banco,
venha a alteração da tela, do phpMyAdmin ou de uma procedure. Como as triggers só registram e nunca
bloqueiam, elas não interferem no uso do sistema: o PDV, o cadastro de produtos e o histórico de
vendas continuam funcionando exatamente como antes.
