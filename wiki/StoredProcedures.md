# Stored Procedures

Documentação das stored procedures (procedimentos armazenados) do banco de dados do ERP-FATEC. O
sistema possui **4 stored procedures**, todas no banco MySQL, criadas pela migration
`database/migrations/2026_10_19_000000_create_stored_procedures.php`.

## O que é uma stored procedure

Uma stored procedure é um bloco de comandos SQL salvo no banco com um nome, que pode receber
**parâmetros de entrada** (`IN`), devolver **parâmetros de saída** (`OUT`) e retornar o resultado
de consultas. É executada com `CALL nome_da_procedure(...)`.

Diferente de uma view, que é só um `SELECT` salvo, a procedure pode ter **lógica**: variáveis,
condições (`IF`), validações com mensagem de erro (`SIGNAL`), alterações de dados (`UPDATE`,
`INSERT`) e **transações** (`START TRANSACTION`, `COMMIT`, `ROLLBACK`).

## Vantagens

| Vantagem | Como aparece no ERP-FATEC |
|---|---|
| **Regra num lugar só** | O ajuste de estoque e o kardex ficam juntos numa procedure; quem chama não esquece de gravar o movimento. |
| **Parâmetros** | Os relatórios recebem empresa, período e limite, em vez de reescrever a consulta para cada caso. |
| **Integridade** | O ajuste roda numa transação e trava a linha do produto: dois ajustes ao mesmo tempo não se atropelam, e um erro desfaz tudo. |
| **Validação no banco** | Saída maior que o saldo ou produto de outra empresa é recusado pelo próprio banco, com mensagem clara. |

## Procedures do sistema

| Procedure | Parâmetros | Finalidade | Tipo |
|---|---|---|---|
| `sp_vendas_por_periodo` | empresa, início, fim | Quantidade de vendas, total vendido, ticket médio e descontos | Consulta |
| `sp_produtos_mais_vendidos` | empresa, início, fim, limite | Ranking dos produtos por quantidade vendida | Consulta |
| `sp_clientes_inadimplentes` | empresa, dias | Clientes com títulos vencidos há mais de N dias | Consulta |
| `sp_ajustar_estoque` | empresa, produto, quantidade, motivo, usuário, **saída: novo saldo** | Entrada ou saída de estoque com registro no kardex | Alteração com transação |

Todas recebem a empresa (`p_tenant_id`) como primeiro parâmetro, porque o sistema é multi-empresa:
nenhuma procedure mistura dados de empresas diferentes.

Consideram só vendas **concluídas** (`status = 'completed'`); vendas canceladas ficam de fora. Nos
períodos, as datas de início e fim são **inclusivas** (o dia final inteiro entra).

## Consultas

### sp_vendas_por_periodo

Resumo das vendas concluídas de uma empresa num período.

```sql
CALL sp_vendas_por_periodo(1, '2026-10-01', '2026-10-31');
```

| quantidade_vendas | total_vendido | ticket_medio | total_descontos |
|---|---|---|---|
| 3 | 180.00 | 60.00 | 5.00 |

### sp_produtos_mais_vendidos

Ranking dos produtos mais vendidos no período, do maior para o menor (por quantidade e, no empate,
por faturamento). O último parâmetro é quantos produtos mostrar; se vier vazio ou zero, mostra 10.

```sql
CALL sp_produtos_mais_vendidos(1, '2026-10-01', '2026-10-31', 5);
```

| codigo | produto | quantidade_vendida | faturamento | numero_vendas |
|---|---|---|---|---|
| 1 | Cabo Flexível 2,5mm² 750V (Rolo 100m) | 5.000 | 130.00 | 2 |
| 2 | Cabo Flexível 4,0mm² 750V (Rolo 100m) | 5.000 | 50.00 | 1 |

### sp_clientes_inadimplentes

Clientes com títulos do crediário vencidos há mais de N dias e ainda com saldo em aberto,
ordenados do maior valor devido para o menor. Títulos pagos ou cancelados não entram.

```sql
CALL sp_clientes_inadimplentes(1, 30);
```

| cliente_id | cliente | telefone | titulos_vencidos | total_em_aberto | vencimento_mais_antigo | dias_de_atraso |
|---|---|---|---|---|---|---|
| 13 | CLIENTE ATRASADO | 19999990001 | 1 | 60.00 | 2026-09-09 | 40 |

## Alteração com transação

### sp_ajustar_estoque

Faz uma entrada (quantidade positiva) ou saída (quantidade negativa) de estoque e grava o
movimento no kardex (`stock_movements`), tudo numa transação. Devolve o novo saldo no parâmetro de
saída `p_novo_saldo`.

```sql
-- Entrada de 10 unidades do produto 5, feita pelo usuário 1
CALL sp_ajustar_estoque(1, 5, 10, 'Contagem de inventário', 1, @saldo);
SELECT @saldo;   -- novo saldo

-- Saída de 4 unidades (avaria)
CALL sp_ajustar_estoque(1, 5, -4, 'Avaria', 1, @saldo);
```

Passo a passo dentro da procedure:

1. Recusa quantidade zero ou vazia.
2. Abre a transação e lê o saldo do produto **travando a linha** (`SELECT ... FOR UPDATE`).
3. Recusa se o produto não existir **nesta empresa** ou se a saída deixar o saldo negativo.
4. Atualiza o saldo em `products`.
5. Grava o movimento no kardex: tipo `in` (entrada) ou `out` (saída), quantidade, saldo anterior,
   saldo novo e o motivo. É o mesmo formato que o sistema usa quando a movimentação é feita pela tela.
6. Confirma a transação (`COMMIT`).

Se qualquer passo falhar, o `EXIT HANDLER` desfaz tudo (`ROLLBACK`) e devolve o erro (`RESIGNAL`):
o saldo e o kardex ficam como estavam.

| Situação | Mensagem do banco |
|---|---|
| Quantidade zero | `Informe uma quantidade diferente de zero.` |
| Produto de outra empresa ou inexistente | `Produto não encontrado nesta empresa.` |
| Saída maior que o saldo | `Estoque insuficiente para essa saída.` |

## Ambientes

- **Produção (MySQL 5.7, HostGator):** as procedures são criadas pela migration
  (`php artisan migrate`). Foram escritas sem recursos do MySQL 8 (sem CTE nem funções de janela),
  para rodar na versão do servidor.
- **Local e testes (SQLite):** o SQLite não tem stored procedures, então a migration não faz nada
  nesse banco e o sistema continua funcionando normalmente.

## Testes feitos

As 4 procedures foram testadas num banco MySQL/MariaDB temporário, com as tabelas reais do sistema
(todas as migrations e os dados de exemplo) e um cenário com vendas, uma venda cancelada, uma venda
fora do período, uma venda de outra empresa e títulos pagos, em dia e atrasados:

- os totais, o ranking e a lista de inadimplentes bateram com o esperado;
- a venda cancelada, a de fora do período e a de outra empresa não entraram nos resultados;
- no ajuste de estoque, a entrada e a saída atualizaram o saldo e o kardex; os três casos de erro
  foram recusados com a mensagem acima, sem alterar saldo nem kardex.
