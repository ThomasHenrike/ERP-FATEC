# ERP Modular

Sistema de Gestão Empresarial (ERP) modular e multi-tenant (multi-empresa), construído em Laravel. Uma única instalação atende várias empresas isoladas entre si, cada uma com seus próprios dados, configurações e usuários.

## Tecnologias

- **PHP** 8.3+ (produção roda em 8.4)
- **Laravel** 13
- **nwidart/laravel-modules** — cada módulo de negócio é isolado em `Modules/{Nome}`
- **MySQL** — banco padrão local e em produção
- **Blade + Alpine.js + Tailwind CSS** (via CDN, sem etapa de build obrigatória)
- **PHPUnit** para os testes automatizados

## Módulos

| Módulo | Responsabilidade |
|---|---|
| `Core` | Autenticação, isolamento multi-tenant, configurações da empresa, controle de papéis (RBAC) |
| `Cadastros` | Clientes, fornecedores e colaboradores (com acesso opcional ao sistema) |
| `Estoque` | Catálogo de produtos, categorias e Kardex (movimentações) |
| `Vendas` | PDV, turnos de caixa, histórico de vendas e crediário |
| `Orcamento` | Propostas comerciais e conversão em venda — **travado nesta versão, ver abaixo** |
| `Fiscal` | Emissão de Nota Fiscal Eletrônica (NF-e) — **travado nesta versão, ver abaixo** |
| `SuperAdmin` | Painel administrativo da plataforma (dono do sistema): empresas, módulos por empresa e faturamento |

## Sobre Orçamento e Fiscal (NF-e)

Este repositório é um recorte de um projeto maior, focado nos módulos
**Estoque, Vendas (PDV) e Cadastros** para fins de trabalho acadêmico. O
código de **Orçamento** e **Fiscal** continua completo no repositório, mas
as rotas ficam bloqueadas de propósito (`Tenant::hasModule()`) — ao tentar
acessá-las, o sistema volta pro Dashboard com o aviso "Este recurso está
em desenvolvimento nesta versão do sistema."

## Como rodar localmente

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
php artisan serve
```

A aplicação sobe em `http://127.0.0.1:8000`. O seed cria:

- Uma empresa de demonstração com usuário `admin@erp.local` / `admin123`
  (com produtos/clientes/fornecedores de exemplo).
- Uma empresa + login individual pra cada integrante do grupo (ver
  `database/seeders/GroupMembersSeeder.php` pras credenciais).

Para acessar o painel administrativo da plataforma (`/admin/login`), crie
uma conta:

```bash
php artisan admin:create "Seu Nome" seu@email.com "senha-forte"
```

## Testes

```bash
php artisan test
```

Os testes de feature de Fiscal/Orçamento não fazem parte da suíte padrão
(`phpunit.xml`) nesta versão, já que as rotas deles estão travadas — o
código dos dois módulos continua no repositório.

## Equipe

- Thomas Henrique Batista Quaresma
- Thiago Felipe Godoy Ribeiro
- Matheus Felipe Rodrigues de Avelar
- Leonardo Vilela da Silva
- Pedro Mangutchi
