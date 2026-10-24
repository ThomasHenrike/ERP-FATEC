# 📑 1. Documentação Geral e Memorial Descritivo do Projeto

---

## Identificação do Projeto e Equipe

* **Nome do Projeto:** Sistema Integrado de Gestão Empresarial (ERP Modular)
* **Tema:** Sistema de Gestão Empresarial com Arquitetura Modular e Multi-Tenancy
* **Integrantes da Equipe:**
  * Thomas Henrique Batista Quaresma
  * Thiago Felipe Godoy Ribeiro
  * Matheus Felipe Rodrigues de Avelar
  * Pedro Henrique Mangutche de Souza
  * Leonardo Vilela da Silva

* **Objetivo do Sistema:** Desenvolver um ERP comercial e operacional robusto, escalável e de fácil manutenção, projetado inicialmente para atender operações internas e estruturado com arquitetura *Multi-Tenant* para viabilizar a expansão como software em nuvem (SaaS). O sistema centraliza controle de estoque, ponto de venda (PDV), orçamentos, financeiro e emissão de documentos fiscais.

---

## 2. Stack Tecnológica e Justificativas de Engenharia

| Tecnologia / Ferramenta | Papel no Projeto | Justificativa Técnica (Por que foi escolhida) |
| :--- | :--- | :--- |
| **PHP 8.3+** | Linguagem Principal | Tipagem estrita (*strict types*), alta performance com OPcache/JIT e suporte aos padrões mais recentes de segurança da linguagem. |
| **Laravel 13** | Framework Backend | Padrão MVC estruturado, ORM Eloquent robusto, proteção automática contra SQL Injection, CSRF e XSS, sistema de migrações versionadas e suporte a filas assíncronas. |
| **`nWidart/laravel-modules`** | Modularização | Implementa o padrão de **Monólito Modular**, permitindo alta coesão e baixo acoplamento entre domínios sem a complexidade e os custos de infraestrutura de microsserviços. |
| **`wikimedia/composer-merge-plugin`** | Autoloading Modular | Gerencia e descobre automaticamente as dependências e o autoloading PSR-4 de cada módulo dentro do ecossistema global do Composer. |
| **SQLite (Dev) / PostgreSQL / MySQL (Prod)** | Banco de Dados Relacional | Suporte a transações ACID, integridade referencial por chaves estrangeiras e isolamento eficiente de dados via chaves relacionais. |
| **Blade Templates & Tailwind CSS** | Camada de Apresentação (UI) | O Blade realiza sanitização automática contra ataques XSS e componentização de layout; o Tailwind CSS padroniza o design responsivo via classes utilitárias. |
| **Git & GitHub** | Controle de Versão | Gestão colaborativa por *Feature Branches*, histórico auditável e governança via *Pull Requests* com *Code Review*. |

---

## 3. Decisões Arquiteturais Fundamentais (ADRs)

### 3.1. Multi-Tenancy Nativo desde o Primeiro Dia
* **Contexto:** O sistema atenderá uma empresa no início, mas com plano de negócio para comercialização a múltiplos clientes.
* **Decisão:** Criar a estrutura com isolamento de tenant desde o início em vez de tentar adaptar depois (o que geraria alto custo e risco de vazamento de dados).
* **Implementação:**
  * Criação das tabelas `tenants` e `tenant_user`.
  * Implementação da Trait `BelongsToTenant` e do `TenantScope` (Global Scope do Eloquent), garantindo que qualquer `SELECT` e `INSERT` filtre e valide automaticamente a empresa ativa na sessão.

### 3.2. Blindagem de Performance Anti-N+1 (`Strict Mode`)
* **Contexto:** Em sistemas ERP, o carregamento tardio (*Lazy Loading*) em loops de vendas e relatórios de estoque sobrecarrega o banco com centenas de consultas simultâneas.
* **Decisão:** Ativar `Model::shouldBeStrict(!app()->isProduction())` no `AppServiceProvider.php`. O framework bloqueia consultas N+1 e atribuições silenciosas durante o desenvolvimento.

### 3.3. Isolamento Modular e Comunicação Desacoplada
* **Contexto:** Evitar que alterações em um módulo quebrem funcionalidades de outros módulos (efeito dominó).
* **Decisão:** Proibir a importação e manipulação direta de Models Eloquent entre módulos distintos.
* **Implementação:**
  * Ações reativas/assíncronas utilizam **Eventos de Domínio** (ex.: `OrderCompletedEvent` emitido pelo PDV para disparar baixa no Estoque e contas no Financeiro).
  * Consultas síncronas utilizam **Contratos e Interfaces** injetados via Service Container.

---

## 4. Estrutura e Escopo dos Módulos