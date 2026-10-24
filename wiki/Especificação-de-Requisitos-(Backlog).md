# DOCUMENTO DE ESPECIFICAÇÃO DE REQUISITOS DO P.I

## 1.0 Objetivo do documento
Tem como objetivo especificar os requisitos do sistema e planejar o seu desenvolvimento ao longo dos semestres do curso.

## 1.1 Objetivo do sistema
O sistema tem como objetivo centralizar e automatizar os fluxos essenciais de negócio — controle rigoroso de estoque, frente de caixa (PDV), geração de orçamentos, controle financeiro e emissão de documentos fiscais —, garantindo integridade transacional, isolamento entre domínios e alto desempenho de banco de dados.

---

## 2.0 Requisitos funcionais (Product Backlog)

### 2º Semestre (MVP)

| ID | Título | Detalhamento | Pontuação |
| :--- | :--- | :--- | :--- |
| **H01** | PDV (Ponto de Venda) | Registro de pedidos/vendas, seleção de produtos, identificação do cliente e cálculo de valores/pagamento. | 100 |
| **H02** | Estoque | Cadastro e controle de itens, quantidade física/disponível, descrição, características e informações fiscais básicas. | 95 |
| **H11** | Cadastro de produtos | O sistema deve ser capaz de cadastrar novos produtos contendo Nome, código, preço, categoria, estoque etc. | 94 |
| **H03** | Gestão de clientes | Gestão cadastral (CPF/CNPJ, razão social/nome, CEP, contato e histórico de pedidos vinculados). | 90 |
| **H04** | Gestão de fornecedores | Gestão de fornecedores (CPF/CNPJ, razão social/nome, CEP, contato e histórico de pedidos/produtos vinculados). | 85 |
| **H05** | Cadastro de usuário e login | O usuário deve ser capaz de cadastrar um usuário no sistema, com email e senha. Este usuário deve ser aprovado por um administrador. | 80 |
| **H06** | Dashboard | O usuário deve ter acesso a um dashboard com o resumo de suas vendas, estoques, movimentação financeira e produtos disponíveis. | 75 |
| **H07** | Relatórios | O usuário deve ser capaz de acessar relatórios de suas vendas, estoques, compras e financeiro. | 70 |
| **H08** | Histórico | O usuário deve ser capaz de acessar um histórico de movimentações realizadas. | 65 |
| **H09** | Tipos de usuários | O sistema deve ter diferentes tipos de usuários, como administrador e cliente. | 60 |

### 3º Semestre

| ID | Título | Detalhamento | Pontuação |
| :--- | :--- | :--- | :--- |
| **H10** | Exportar relatórios | O sistema deve ser capaz de gerar relatórios em PDF. | 55 |
| **H16** | Módulo de Orçamentos | Elaboração de propostas comerciais e fluxo de conversão automática de orçamentos em pedidos de venda. | 50 |
| **H17** | Módulo Financeiro | Controle de contas a pagar, contas a receber e conciliação de lançamentos originados nas vendas. | 45 |
| **H18** | Módulo Fiscal | Processamento assíncrono para emissão de NFC-e/NF-e e geração de DANFE. | 40 |

### 4º Semestre em diante

| ID | Título | Detalhamento | Pontuação |
| :--- | :--- | :--- | :--- |
| **H12** | Scan de produtos | O sistema deve ser capaz de ler códigos de barras e/ou QR codes de produtos e enviá-los automaticamente ao módulo de estoque. | - |
| **H13** | Scan de notas | O sistema deve ser capaz de escanear notas de vendas/serviço/produto e descontar/enviar apropriadamente nos módulos respectivos. | - |
| **H14** | Aplicação Mobile | O sistema deve ter uma versão para dispositivos móveis (Android e IOS) que funciona de forma integrada com a versão web. | - |
| **H15** | Integração com Bot de Whatsapp | O sistema deve ter algumas de suas funcionalidades simplificadas disponíveis através do whatsapp por um bot que se comunica com o sistema. (Implementar um IA talvez?) | - |

---

## 2.1 Requisitos não funcionais

| ID | Título | Detalhamento | Pontuação |
| :--- | :--- | :--- | :--- |
| **RNF01** | Design | O site deve manter um layout padronizado em todas as páginas, com menus e botões posicionados de forma consistente e identificados por rótulos claros. | 95 |
| **RNF02** | Tempo de resposta | O sistema deve ter um tempo de resposta entre suas operações inferior a 5 segundos. | 90 |
| **RNF03** | Navegação | O usuário deverá ser capaz de acessar qualquer página do sistema em no máximo 3 cliques. | 100 |
| **RNF04** | Segurança | O sistema deverá ter diversas camadas de segurança para impedir invasões e vazamento de dados | 98 |

---

## 2.2 Backlog dividido em módulos (Apenas MVP)

### A. Autenticação
- Cadastro de usuário
- Login
- Logout
- Recuperação de senha
- Controle básico de permissões

### B. Cadastros
- CRUD de clientes
- CRUD de fornecedores
- CRUD de produtos
- CRUD de categorias

### C. Estoque
- Visualizar estoque
- Registrar entrada
- Registrar saída
- Alertar estoque baixo
- Histórico de movimentações

### D. Vendas
- Criar venda
- Adicionar produtos à venda
- Calcular subtotal/desconto/total
- Vincular cliente
- Atualizar estoque automaticamente
- Consultar vendas realizadas

### E. Compras
- Criar compra
- Selecionar fornecedor
- Adicionar produtos
- Calcular total
- Atualizar estoque automaticamente
- Consultar compras

### F. Financeiro
- Registrar conta a pagar
- Registrar conta a receber
- Marcar conta como paga
- Visualizar contas pendentes
- Histórico financeiro

### G. Dashboard
- Total de vendas
- Total de compras
- Valor em estoque
- Contas a pagar
- Contas a receber
- Produtos com estoque baixo

### H. Relatórios
- Relatório de vendas
- Relatório de compras
- Relatório de estoque
- Relatório financeiro