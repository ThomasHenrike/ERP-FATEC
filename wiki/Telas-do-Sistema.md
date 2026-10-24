# Telas do Sistema

Esta página apresenta as principais telas da primeira versão funcional do ERP: **Frente de Caixa (PDV)**, **Cadastro de Clientes** e **Controle de Estoque**, que formam o MVP do projeto.

## Como os módulos se relacionam

```mermaid
flowchart LR
    subgraph Cadastros[Módulo Cadastros]
        C[Clientes<br/>limite de crédito e tabela de preço]
    end

    subgraph Estoque[Módulo Estoque]
        P[Produtos<br/>preço, SKU e código de barras]
        S[Saldo em estoque<br/>Kardex]
        P --- S
    end

    subgraph Vendas[Módulo Vendas]
        PDV[Frente de Caixa - PDV]
        V[Venda concluída]
        PDV --> V
    end

    C -->|cliente da venda| PDV
    P -->|itens do carrinho| PDV
    V -->|baixa no estoque| S
```

Ao concluir uma venda, o sistema registra automaticamente a saída de cada item no Kardex do Estoque. Se a venda for cancelada, o estoque é estornado.

| Módulo | Tela | Função no sistema |
| :--- | :--- | :--- |
| **Vendas** | Frente de Caixa (PDV) | Registra as vendas do balcão, com leitura de código de barras, descontos e formas de pagamento |
| **Cadastros** | Carteira de Clientes | Gerencia os clientes, seus limites de crédito para o crediário e as tabelas de preço vinculadas |
| **Estoque** | Controle de Estoque | Gerencia o catálogo de produtos, a precificação e os níveis de inventário |

---

## Frente de Caixa (PDV)

![Frente de Caixa (PDV)](https://github.com/user-attachments/assets/ebf0febd-2bbb-46b0-a773-9297397b0fc3)

| Recurso | Descrição |
| :--- | :--- |
| **Controle de turno** | O caixa é aberto por um operador; permite sangria, suprimento e fechamento |
| **Leitura de produtos** | Por código de barras ou código simples (#1, #2...), ou pela busca no estoque com a tecla F2 |
| **Carrinho** | Ajuste de quantidade, remoção de itens e cálculo automático do total |
| **Cliente e tabela de preço** | Venda para consumidor final ou cliente cadastrado, com tabela Varejo, Revenda ou Atacado |
| **Descontos** | Em percentual ou em valor, com o abatimento total exibido no resumo |
| **Pagamento** | Uma ou mais formas de pagamento na mesma venda, com valor sugerido |

---

## Cadastro de Clientes

![Carteira de Clientes](https://github.com/user-attachments/assets/859448b6-621e-421b-8c55-850b46e6b1ca)

| Recurso | Descrição |
| :--- | :--- |
| **Pessoa física e jurídica** | Cadastro de clientes PF (CPF) e PJ (CNPJ) |
| **Tabela de preço padrão** | Cada cliente tem sua tabela (Varejo, Revenda, Atacado), aplicada automaticamente no PDV |
| **Limite de crédito** | Valor máximo liberado para compras no crediário |
| **Busca** | Por nome, CPF/CNPJ, telefone ou cidade |
| **Status** | Filtros de clientes ativos e inativos, com opção de desativar |

---

## Controle de Estoque

![Controle de Estoque](https://github.com/user-attachments/assets/5579c29b-f50e-442c-9381-5855d3804c79)

| Recurso | Descrição |
| :--- | :--- |
| **Indicadores** | Total no catálogo, estoque crítico (abaixo do mínimo), itens zerados e categorias ativas |
| **Identificação do produto** | Código simples, SKU e código de barras (EAN) |
| **Categorias** | Organização por categorias e subcategorias |
| **Saldo atual** | Quantidade disponível com a unidade de medida (UN, PCT, PC...) |
| **Extrato Kardex** | Histórico de entradas e saídas de cada produto |
| **Busca e filtros** | Por nome, código, SKU, código de barras, categoria e status |