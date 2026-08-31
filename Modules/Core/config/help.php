<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Registro dos artigos da Central de Ajuda
|--------------------------------------------------------------------------
|
| Cada entrada aponta pra um arquivo .md em Modules/Core/resources/help/.
| Ao adicionar ou mudar uma funcionalidade, adicione/atualize o artigo aqui
| e o .md correspondente - e' o padrao combinado pra nao deixar a
| documentacao acumular pro final.
|
*/

return [
    'visao-geral' => [
        'title' => 'Visão Geral do Sistema',
        'category' => 'Primeiros Passos',
        'order' => 1,
        'keywords' => ['inicio', 'comecar', 'dashboard', 'modulos'],
    ],
    'papeis-usuarios' => [
        'title' => 'Papéis de Usuário (Operador, Gerente, Admin)',
        'category' => 'Primeiros Passos',
        'order' => 2,
        'keywords' => ['permissao', 'acesso', 'operator', 'manager', 'admin', 'equipe'],
    ],
    'cadastros-clientes-fornecedores' => [
        'title' => 'Cadastros: Clientes, Fornecedores e Equipe',
        'category' => 'Cadastros',
        'order' => 1,
        'keywords' => ['cliente', 'fornecedor', 'funcionario', 'equipe', 'cadastro'],
    ],
    'estoque-produtos' => [
        'title' => 'Estoque: Produtos, Categorias e Movimentações',
        'category' => 'Estoque',
        'order' => 1,
        'keywords' => ['produto', 'categoria', 'kardex', 'movimentacao', 'estoque'],
    ],
    'vendas-pdv' => [
        'title' => 'Vendas: PDV, Histórico e Crediário',
        'category' => 'Vendas',
        'order' => 1,
        'keywords' => ['venda', 'pdv', 'caixa', 'crediario', 'prazo'],
    ],
    'orcamentos' => [
        'title' => 'Orçamentos',
        'category' => 'Orçamento',
        'order' => 1,
        'keywords' => ['orcamento', 'proposta', 'cotacao'],
    ],
    'financeiro-assinatura' => [
        'title' => 'Financeiro: Faturas da Assinatura',
        'category' => 'Financeiro',
        'order' => 1,
        'keywords' => ['fatura', 'assinatura', 'pagamento', 'mensalidade', 'financeiro'],
    ],
    'fiscal-nfe' => [
        'title' => 'Fiscal: Emissão de Nota Fiscal (NF-e)',
        'category' => 'Fiscal',
        'order' => 1,
        'keywords' => ['nfe', 'nota fiscal', 'sefaz', 'certificado', 'fiscal'],
    ],
    'configuracoes-empresa' => [
        'title' => 'Configurações da Empresa',
        'category' => 'Configurações',
        'order' => 1,
        'keywords' => ['configuracao', 'identidade visual', 'logo', 'dados cadastrais'],
    ],
    'suporte-chamados' => [
        'title' => 'Como Funciona o Chat de Suporte',
        'category' => 'Suporte',
        'order' => 1,
        'keywords' => ['suporte', 'chamado', 'chat', 'ajuda', 'duvida'],
    ],
];
