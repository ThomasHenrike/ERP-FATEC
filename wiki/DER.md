

# Modelo de Dados — ERP-FATEC

Documentação do modelo entidade-relacionamento do sistema, cobrindo os módulos **Core**,
**Cadastros**, **Estoque** e **Vendas**. Reconstruído a partir das migrations do projeto
(Laravel + nwidart-modules).

## Diagrama

```mermaid
erDiagram
    TENANTS ||--o{ TENANT_USER : possui
    USERS ||--o{ TENANT_USER : vincula
    TENANTS ||--o{ TENANT_MODULES : configura
    TENANTS ||--o{ EMPLOYEES : emprega
    USERS |o--o| EMPLOYEES : "e (opcional)"
    TENANTS ||--o{ SUPPLIERS : cadastra
    TENANTS ||--o{ CATEGORIES : organiza
    CATEGORIES |o--o{ CATEGORIES : "subcategoria de"
    TENANTS ||--o{ PRODUCTS : possui
    CATEGORIES |o--o{ PRODUCTS : classifica
    SUPPLIERS |o--o{ PRODUCTS : fornece
    TENANTS ||--o{ STOCK_MOVEMENTS : registra
    PRODUCTS ||--o{ STOCK_MOVEMENTS : movimenta
    USERS |o--o{ STOCK_MOVEMENTS : realiza
    TENANTS ||--o{ SUPPLIER_PRODUCTS : mapeia
    PRODUCTS ||--o{ SUPPLIER_PRODUCTS : corresponde
    TENANTS ||--o{ CUSTOMERS : cadastra
    TENANTS ||--o{ SALES : realiza
    USERS ||--o{ SALES : opera
    EMPLOYEES |o--o{ SALES : vende
    CASH_SHIFTS |o--o{ SALES : "ocorre em"
    CUSTOMERS |o--o{ SALES : compra
    SALES ||--o{ SALE_ITEMS : contem
    PRODUCTS ||--o{ SALE_ITEMS : "e vendido em"
    SALES ||--o{ SALE_PAYMENTS : "e pago por"
    TENANTS ||--o{ CASH_SHIFTS : abre
    USERS ||--o{ CASH_SHIFTS : opera
    CASH_SHIFTS ||--o{ CASH_MOVEMENTS : registra
    USERS ||--o{ CASH_MOVEMENTS : autoriza
    TENANTS ||--o{ CUSTOMER_RECEIVABLES : controla
    CUSTOMERS ||--o{ CUSTOMER_RECEIVABLES : deve
    SALES ||--o{ CUSTOMER_RECEIVABLES : gera

    TENANTS {
        bigint id PK
        uuid uuid UK
        string name
        string trade_name
        string document UK
        string state_registration
        string email
        string phone
        string street
        string city
        string state
        string ibge_city_code
        string logo_path
        string primary_color
        boolean is_active
        tinyint crt
        string sefaz_environment
        decimal max_discount_percentage
        tinyint billing_due_day
        decimal billing_amount
        int max_users
        text config_extra "+25 campos de config"
    }

    USERS {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
    }

    TENANT_USER {
        bigint id PK
        bigint tenant_id FK
        bigint user_id FK
        string role
        boolean is_active
    }

    TENANT_MODULES {
        bigint id PK
        bigint tenant_id FK
        string module
        boolean is_enabled
    }

    EMPLOYEES {
        bigint id PK
        bigint tenant_id FK
        bigint user_id FK
        string name
        string document
        string role
        decimal commission_percentage
        date hire_date
        boolean is_active
    }

    SUPPLIERS {
        bigint id PK
        bigint tenant_id FK
        string name
        string trade_name
        string document
        string contact_name
        string city
        string state
        boolean is_active
    }

    CATEGORIES {
        bigint id PK
        bigint tenant_id FK
        bigint parent_id FK
        string name
        string slug
        boolean is_active
    }

    PRODUCTS {
        bigint id PK
        bigint tenant_id FK
        bigint category_id FK
        bigint supplier_id FK
        bigint code
        string name
        string brand
        string sku
        string barcode
        string unit
        decimal cost_price
        decimal price_retail
        decimal price_resale
        decimal price_wholesale
        decimal min_stock
        decimal current_stock
        string location
        int warranty_months
        string ncm
        string cfop_default
        string csosn
        decimal icms_rate
        boolean is_active
    }

    STOCK_MOVEMENTS {
        bigint id PK
        bigint tenant_id FK
        bigint product_id FK
        bigint user_id FK
        string type
        decimal quantity
        decimal unit_price
        decimal previous_stock
        decimal current_stock
        string reference_type
        bigint reference_id
    }

    SUPPLIER_PRODUCTS {
        bigint id PK
        bigint tenant_id FK
        bigint product_id FK
        string supplier_cnpj
        string supplier_product_code
    }

    CUSTOMERS {
        bigint id PK
        bigint tenant_id FK
        string name
        string trade_name
        string person_type
        string document
        string email
        string phone
        decimal credit_limit
        string default_price_table
        string city
        string state
        string ibge_city_code
        boolean is_active
    }

    SALES {
        bigint id PK
        bigint tenant_id FK
        bigint code UK
        bigint user_id FK
        bigint seller_id FK
        bigint cash_shift_id FK
        bigint customer_id
        string status
        string price_table
        decimal subtotal
        decimal discount_amount
        decimal total_amount
        string payment_method
        text notes
    }

    SALE_ITEMS {
        bigint id PK
        bigint tenant_id FK
        bigint sale_id FK
        bigint product_id FK
        decimal quantity
        decimal unit_price
        decimal discount_amount
        decimal total_price
    }

    SALE_PAYMENTS {
        bigint id PK
        bigint tenant_id FK
        bigint sale_id FK
        string payment_method
        decimal amount
        int installments
        date due_date
    }

    CASH_SHIFTS {
        bigint id PK
        bigint tenant_id FK
        bigint user_id FK
        string status
        datetime opened_at
        datetime closed_at
        decimal opening_balance
        decimal closing_balance
        decimal difference_amount
    }

    CASH_MOVEMENTS {
        bigint id PK
        bigint tenant_id FK
        bigint cash_shift_id FK
        bigint user_id FK
        string type
        decimal amount
        string reason
    }

    CUSTOMER_RECEIVABLES {
        bigint id PK
        bigint tenant_id FK
        bigint customer_id FK
        bigint sale_id FK
        int installment_number
        int total_installments
        decimal amount
        decimal paid_amount
        date due_date
        string status
    }
```

## Notação

| Símbolo | Significado |
|---|---|
| `PK` | Chave primária |
| `FK` | Chave estrangeira |
| `UK` | Chave única (unique) |
| `\|\|--o{` | Um para muitos (obrigatório → opcional/múltiplo) |
| `\|o--o{` | Zero-ou-um para muitos (FK opcional) |
| `\|o--o\|` | Zero-ou-um para zero-ou-um |

## Relacionamentos

| Entidade origem | Cardinalidade | Entidade destino | Relação |
|---|---|---|---|
| TENANTS | 1:N | TENANT_USER | possui |
| USERS | 1:N | TENANT_USER | vincula |
| TENANTS | 1:N | TENANT_MODULES | configura |
| TENANTS | 1:N | EMPLOYEES | emprega |
| USERS | 0/1:0/1 | EMPLOYEES | é (opcional) |
| TENANTS | 1:N | SUPPLIERS | cadastra |
| TENANTS | 1:N | CATEGORIES | organiza |
| CATEGORIES | 0/1:N | CATEGORIES | subcategoria de |
| TENANTS | 1:N | PRODUCTS | possui |
| CATEGORIES | 0/1:N | PRODUCTS | classifica |
| SUPPLIERS | 0/1:N | PRODUCTS | fornece |
| TENANTS | 1:N | STOCK_MOVEMENTS | registra |
| PRODUCTS | 1:N | STOCK_MOVEMENTS | movimenta |
| USERS | 0/1:N | STOCK_MOVEMENTS | realiza |
| TENANTS | 1:N | SUPPLIER_PRODUCTS | mapeia |
| PRODUCTS | 1:N | SUPPLIER_PRODUCTS | corresponde |
| TENANTS | 1:N | CUSTOMERS | cadastra |
| TENANTS | 1:N | SALES | realiza |
| USERS | 1:N | SALES | opera |
| EMPLOYEES | 0/1:N | SALES | vende |
| CASH_SHIFTS | 0/1:N | SALES | ocorre em |
| CUSTOMERS | 0/1:N | SALES | compra |
| SALES | 1:N | SALE_ITEMS | contém |
| PRODUCTS | 1:N | SALE_ITEMS | é vendido em |
| SALES | 1:N | SALE_PAYMENTS | é pago por |
| TENANTS | 1:N | CASH_SHIFTS | abre |
| USERS | 1:N | CASH_SHIFTS | opera |
| CASH_SHIFTS | 1:N | CASH_MOVEMENTS | registra |
| USERS | 1:N | CASH_MOVEMENTS | autoriza |
| TENANTS | 1:N | CUSTOMER_RECEIVABLES | controla |
| CUSTOMERS | 1:N | CUSTOMER_RECEIVABLES | deve |
| SALES | 1:N | CUSTOMER_RECEIVABLES | gera |

## Dicionário de Dados

### Módulo Core

#### TENANTS
Empresa cliente do sistema (multi-tenant — toda tabela abaixo referencia esta).

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| uuid | uuid | UK | Identificador público |
| name | string | | Razão social |
| trade_name | string | | Nome fantasia |
| document | string | UK | CNPJ ou CPF |
| state_registration | string | | Inscrição estadual |
| email | string | | E-mail de contato |
| phone | string | | Telefone |
| street, city, state | string | | Endereço |
| ibge_city_code | string | | Código IBGE do município |
| logo_path | string | | Caminho da logomarca |
| primary_color | string | | Cor de identidade visual |
| is_active | boolean | | Empresa ativa? |
| crt | tinyint | | Regime tributário (Simples/Normal) |
| sefaz_environment | string | | Ambiente fiscal (homologação/produção) |
| max_discount_percentage | decimal | | Desconto máximo permitido no PDV |
| billing_due_day | tinyint | | Dia de vencimento da mensalidade |
| billing_amount | decimal | | Valor da mensalidade |
| max_users | int | | Limite de usuários contratado |
| *(+ ~25 colunas de configuração)* | | | Impressão, orçamento padrão, etc. — omitidas aqui por brevidade |

#### USERS
Login do sistema (tabela padrão do Laravel).

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| name | string | | Nome |
| email | string | UK | E-mail de login |
| email_verified_at | timestamp | | Data de verificação do e-mail |
| password | string | | Senha (hash) |

#### TENANT_USER
Associativa N:N entre `TENANTS` e `USERS` — um usuário pode acessar mais de uma empresa.

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| user_id | bigint | FK | Usuário |
| role | string | | Papel na empresa (admin/manager/operator) |
| is_active | boolean | | Vínculo ativo? |

#### TENANT_MODULES
Módulos habilitados por empresa (feature flags).

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| module | string | | Nome do módulo |
| is_enabled | boolean | | Módulo ativo? |

### Módulo Cadastros

#### EMPLOYEES
Colaboradores/vendedores — independente de ter login no sistema.

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| user_id | bigint | FK (opcional) | Login associado, se houver |
| name | string | | Nome |
| document | string | | CPF |
| role | string | | Função (vendedor, caixa, gerente...) |
| commission_percentage | decimal | | % de comissão |
| hire_date | date | | Data de contratação |
| is_active | boolean | | Colaborador ativo? |

#### SUPPLIERS
Fornecedores da empresa.

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| name | string | | Razão social |
| trade_name | string | | Nome fantasia |
| document | string | | CNPJ |
| contact_name | string | | Contato |
| city, state | string | | Localização |
| is_active | boolean | | Fornecedor ativo? |

### Módulo Estoque

#### CATEGORIES
Categorias de produto — hierárquica (auto-relacionamento).

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| parent_id | bigint | FK (opcional) | Categoria pai (subcategoria) |
| name | string | | Nome |
| slug | string | | Identificador amigável |
| is_active | boolean | | Categoria ativa? |

#### PRODUCTS
Produtos, com três tabelas de preço (varejo/revenda/atacado).

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| category_id | bigint | FK (opcional) | Categoria |
| supplier_id | bigint | FK (opcional) | Fornecedor principal |
| code | bigint | | Código interno sequencial |
| name, brand, sku, barcode, unit | string | | Identificação comercial |
| cost_price | decimal | | Preço de custo |
| price_retail | decimal | | Preço varejo |
| price_resale | decimal | | Preço revenda |
| price_wholesale | decimal | | Preço atacado |
| min_stock, current_stock | decimal | | Controle de estoque |
| location | string | | Localização física |
| warranty_months | int | | Garantia (meses) |
| ncm, cfop_default, csosn, icms_rate | — | | Dados fiscais (SEFAZ) |
| is_active | boolean | | Produto ativo? |

#### STOCK_MOVEMENTS
Ficha (Kardex) de movimentação de estoque.

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| product_id | bigint | FK | Produto |
| user_id | bigint | FK (opcional) | Quem registrou |
| type | string | | Entrada, saída, ajuste, perda |
| quantity | decimal | | Quantidade movimentada |
| unit_price | decimal | | Preço unitário na movimentação |
| previous_stock | decimal | | Saldo antes |
| current_stock | decimal | | Saldo depois |
| reference_type, reference_id | — | | Origem da movimentação (venda, XML, ajuste manual) |

#### SUPPLIER_PRODUCTS
De-para entre o código do produto no XML do fornecedor e o produto interno.

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| product_id | bigint | FK | Produto interno |
| supplier_cnpj | string | | CNPJ do fornecedor |
| supplier_product_code | string | | Código do produto no XML do fornecedor |

### Módulo Vendas

#### CUSTOMERS
Clientes da empresa.

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| name, trade_name | string | | Nome / razão social |
| person_type | string | | Física ou jurídica |
| document | string | | CPF/CNPJ |
| email, phone | string | | Contato |
| credit_limit | decimal | | Limite de crédito |
| default_price_table | string | | Tabela de preço padrão |
| city, state, ibge_city_code | — | | Endereço |
| is_active | boolean | | Cliente ativo? |

#### SALES
Venda / cupom do PDV.

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| code | bigint | UK | Número sequencial da venda |
| user_id | bigint | FK | Operador de caixa |
| seller_id | bigint | FK (opcional) | Vendedor (comissão) |
| cash_shift_id | bigint | FK (opcional) | Turno de caixa |
| customer_id | bigint | *(sem FK no banco)* | Cliente — ver observação abaixo |
| status | string | | Concluída / cancelada |
| price_table | string | | Tabela de preço usada |
| subtotal, discount_amount, total_amount | decimal | | Valores |
| payment_method | string | | Forma de pagamento |
| notes | text | | Observações |

#### SALE_ITEMS
Itens da venda — associativa N:N entre `SALES` e `PRODUCTS`.

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| sale_id | bigint | FK | Venda |
| product_id | bigint | FK | Produto |
| quantity, unit_price, discount_amount, total_price | decimal | | Valores do item |

#### SALE_PAYMENTS
Pagamento fracionado da venda (split payment).

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| sale_id | bigint | FK | Venda |
| payment_method | string | | Forma de pagamento |
| amount | decimal | | Valor pago nessa forma |
| installments | int | | Número de parcelas |
| due_date | date | | Vencimento |

#### CASH_SHIFTS
Turno/sessão de caixa.

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| user_id | bigint | FK | Operador |
| status | string | | Aberto / fechado |
| opened_at, closed_at | datetime | | Horários |
| opening_balance | decimal | | Fundo de troco inicial |
| closing_balance | decimal | | Valor contado no fechamento |
| difference_amount | decimal | | Sobra/quebra de caixa |

#### CASH_MOVEMENTS
Sangria e suprimento de caixa.

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| cash_shift_id | bigint | FK | Turno de caixa |
| user_id | bigint | FK | Quem autorizou |
| type | string | | Entrada (suprimento) ou saída (sangria) |
| amount | decimal | | Valor |
| reason | string | | Motivo |

#### CUSTOMER_RECEIVABLES
Títulos a receber (crediário / venda a prazo).

| Campo | Tipo | Chave | Descrição |
|---|---|---|---|
| id | bigint | PK | Identificador |
| tenant_id | bigint | FK | Empresa |
| customer_id | bigint | FK | Cliente devedor |
| sale_id | bigint | FK | Venda de origem |
| installment_number, total_installments | int | | Parcela atual / total |
| amount, paid_amount | decimal | | Valor da parcela / já pago |
| due_date | date | | Vencimento |
| status | string | | Pendente, pago, atrasado, cancelado |

## Observações do esquema

- **Multi-tenant por coluna**: quase toda tabela carrega `tenant_id` — o isolamento entre empresas é feito por linha, não por schema/banco separado.
- **FK lógica, não física**: `sales.customer_id` não tem constraint de chave estrangeira no banco (a tabela `customers` foi criada numa migration posterior à de `sales`) — a relação existe na regra de negócio, mas não é garantida pelo SGBD.
- **Hierarquia**: `categories.parent_id` referencia a própria tabela, permitindo subcategorias.
- **Exclusão lógica (soft delete)**: presente em `tenants`, `employees`, `suppliers`, `products` e `customers`.

## Ver também

- [Views](Views.md) — as 6 views do banco (consultas salvas para relatórios e segurança).
- [Stored Procedures](StoredProcedures.md) — as 17 procedures com parâmetros (consulta, inserção,
  atualização, exclusão e ajuste de estoque), com o código SQL e os prints da execução.
- [Apresentação do DER (Google Slides)](https://docs.google.com/presentation/d/1Cywp6a3Ay53cogIiCrSk2xoR6v3QujazLIj9PnQJ3sE/edit?usp=sharing)
