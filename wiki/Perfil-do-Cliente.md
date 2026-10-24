# Planejamento Estratégico: Perfil do Cliente e Área de Atuação

Este documento detalha o planejamento estratégico do nosso ERP modular desenvolvido em PHP para Pequenas e Médias Empresas (PMEs). Ele abrange a análise completa dos perfis de clientes que interagem com o ecossistema do software e define a nossa estratégia de expansão geográfica e de mercado.

---

## 1. Perfil do Cliente

Para construir um ERP eficiente, escalável e alinhado com as necessidades reais do mercado, mapeamos diferentes perfis que impactam ou são impactados pelo nosso ecossistema de software.

### 1.1. Cliente Interno (Funcionários e Equipe de Desenvolvimento)
* **O que gostam:** 
  * Ferramentas de desenvolvimento modernas (Frameworks PHP robustos como Laravel ou Symfony, controle de versão limpo no Git).
  * Autonomia na tomada de decisões técnicas e clareza nos requisitos dos módulos (Estoque, Fiscal, Financeiro, PDV, etc.).
  * Ambiente de trabalho colaborativo e adoção de metodologias ágeis (Scrum/Kanban).
* **Onde gastam:** 
  * Tempo e esforço intelectual na resolução de bugs complexos, refatoração de código legado e construção de testes unitários e de integração.
* **O que não gostam:** 
  * Mudanças repentinas de escopo sem alinhamento prévio, falta de documentação técnica clara e código sem padronização.
* **Onde frequentam:** 
  * Laboratórios da faculdade, repositórios no GitHub, plataformas de estudo (Alura, Udemy) e comunidades de programação (Stack Overflow, Discord/Slack da equipe).

### 1.2. Cliente Externo (Fornecedores de Infraestrutura e Serviços)
* **O que gostam:** 
  * APIs estáveis e bem documentadas, contratos transparentes de prestação de serviço em nuvem.
* **Onde gastam:** 
  * Recursos financeiros em servidores de hospedagem (VPS/Cloud em provedores como AWS, DigitalOcean ou Hostinger), domínios, certificados SSL e serviços de e-mail transacional.
* **O que não gostam:** 
  * Instabilidades de servidores de terceiros, lentidão na resposta de APIs governamentais (como a SEFAZ para o módulo Fiscal) e reajustes abusivos de preços de infraestrutura.
* **Onde frequentam:** 
  * Provedores de nuvem, fóruns de DevOps e portais de documentação técnica.

### 1.3. Cliente Ativo (PMEs que consomem o ERP por conveniência)
* **O que gostam:** 
  * Praticidade de acessar o sistema via web (sem instalação local complexa), integração fluida entre os módulos (ex: uma venda no **PDV** baixa automaticamente o **Estoque** e lança no **Financeiro**).
  * Relatórios gerenciais intuitivos que auxiliam na tomada de decisão rápida.
* **Onde gastam:** 
  * Tempo operacional reduzido, mensalidades da assinatura do SaaS (Software as a Service) e treinamento inicial da equipe.
* **O que não gostam:** 
  * Telas poluídas, lentidão no carregamento das páginas PHP, falhas em cálculos de impostos ou divergências no fechamento de caixa.
* **Onde frequentam:** 
  * Seus próprios comércios e escritórios, redes sociais profissionais (LinkedIn), portais de notícias de empreendedorismo e feiras de negócios locais.
* **Como manter ativo e fidelizado:**
  * **Atualizações constantes:** Entregar melhorias contínuas baseadas no uso real.
  * **Suporte ágil:** Canais de atendimento rápidos (WhatsApp/Chat integrado).
  * **Programas de indicação:** Descontos na mensalidade para quem indicar novos lojistas.

### 1.4. Cliente Inativo (Churn - Quem deixou de consumir)
* **Motivos da saída:** 
  * Encerramento das atividades ou transição da empresa cliente.
  * Migração para concorrentes consolidados de mercado com mais recursos legados.
  * Complexidade excessiva de uso ou falta de uma funcionalidade específica (ex: integração com e-commerce ou maquininha de cartão específica).
* **Estratégia de recuperação (Win-Back):** 
  * Realizar uma *Exit Survey* (pesquisa de saída) para entender o motivo exato da descontinuidade.
  * Criar campanhas de reativação oferecendo migração assistida gratuita, novos módulos solicitados e condições promocionais (ex: 2 meses de teste com 50% de desconto).

### 1.5. Cliente Satisfeito (Promotores)
* **Perfil:** 
  * Gestores de PMEs que conseguiram organizar o fluxo de caixa, zerar furos no estoque e agilizar a emissão de notas fiscais através do nosso ERP.
* **Como manter satisfeitos e ativos:**
  * Oferecer atendimento prioritário (VIP).
  * Convidá-los para o programa de *Beta Testers*, dando acesso antecipado a novos módulos em desenvolvimento.
  * Enviar conteúdos educativos (e-books de gestão financeira, dicas de controle de estoque).

### 1.6. Cliente Insatisfeito (Detratores)
* **Onde erramos:** 
  * Erros críticos em cálculos fiscais ou financeiros, lentidão do sistema em horários de pico, suporte técnico demorado ou pouco empático.
* **Como fazer para ele se sentir satisfeito (Plano de Recuperação):**
  * **Ouvir ativamente:** Coletar o feedback detalhado sem defensividade.
  * **Ação Corretiva Rápida:** Priorizar o bug ou gargalo reportado no roadmap de desenvolvimento.
  * **Compensação:** Oferecer um período de gratuidade ou consultoria de implantação gratuita para demonstrar compromisso com a melhoria.

> **Mecanismos Gerais de Relacionamento:** Utilização de métricas como **NPS (Net Promoter Score)** trimestral, caixas de sugestões dentro do próprio ERP, central de ajuda (FAQ/Base de Conhecimento) e campanhas de marketing promocional sazonais.

---

## 2. Área de Atuação

A expansão do ERP acontece em duas etapas: primeiro a **microrregião** (Itapira), para validar o produto com clientes reais, e logo em seguida a **macrorregião**, que é quem traz o retorno do investimento. As metas abaixo foram calculadas a partir da [Viabilidade Econômica](Viabilidades-do-Projeto), que exige um faturamento de **R$ 87.130,95 por mês** para cobrir custos, margem de lucro e impostos.

### Premissas das metas

| Premissa | Valor adotado | Origem |
| :--- | :--- | :--- |
| Empresas em Itapira | ≈ 11.000 | Estimativa da equipe (inclui MEIs) |
| Mensalidade por empresa | R$ 99,00 | Valor de referência para ERPs de pequenas empresas |
| Custo fixo mensal | R$ 61.800,00 | Viabilidade Econômica (sem os notebooks) |
| Investimento inicial | R$ 50.000,00 | Viabilidade Econômica (5 notebooks, mês 1) |
| Imposto | 12,59% sobre o faturamento | Simples Nacional, Anexo III |
| Início das vendas | Janeiro de 2027 | Após a Fase 1 do cronograma |

### 2.1. Microrregião — Itapira (validação)

* **Definição:**
  * A fase inicial (MVP - Produto Mínimo Viável) é focada em Itapira, cidade sede do projeto.
* **Público-alvo local:**
  * Pequenos comércios locais (lojas de roupas, minimercados, prestadores de serviços, lanchonetes) que ainda utilizam planilhas do Excel ou cadernos para controle financeiro e de estoque.
* **Meta:**

| Indicador | Meta |
| :--- | :--- |
| Participação de mercado | **10% das empresas de Itapira** |
| Clientes | **1.100 clientes até dezembro de 2028** |
| Ritmo de entrada | ≈ 46 clientes novos por mês, a partir de janeiro de 2027 |
| Validação (1º semestre de 2027) | 275 clientes até junho de 2027 |
| Faturamento com a meta atingida | 1.100 × R$ 99,00 = **R$ 108.900,00 por mês** |
| Mínimo para cobrir a Viabilidade | 881 clientes (8,0%) |

* **Estratégia de Implementação:**
  * **Abordagem Direta / Porta a Porta:** Visitas presenciais aos lojistas para apresentar o sistema.
  * **Implementação Assistida:** A própria equipe do projeto realiza o setup inicial, cadastro de produtos e treinamento presencial dos funcionários do cliente.
  * **Feedback Direto:** Ciclos curtos de feedback para ajustar bugs e usabilidade do sistema em tempo real.

> Sozinha, a microrregião cobre o custo mensal, mas não devolve o investimento: com Itapira apenas, o retorno ficaria para junho de 2030. Por isso a macrorregião começa logo depois da validação, e Itapira continua crescendo em paralelo, já que ali o cliente custa menos (sem anúncio e sem comissão).

### 2.2. Macrorregião — Expansão a partir de julho de 2027

* **Definição:**
  * Expansão para as cidades vizinhas, o estado e depois o país por canais digitais, começando em **julho de 2027**, quando a Fase 2 do cronograma entrega os módulos Financeiro, Orçamentos e Fiscal.
* **Condição para iniciar:** a expansão só começa em julho de 2027 se, em junho de 2027, a validação em Itapira tiver alcançado:
  * pelo menos **250 clientes ativos**;
  * **cancelamento abaixo de 3% ao mês**;
  * suporte respondendo os chamados **no mesmo dia**.

  Se a condição não for atingida, a expansão é adiada para janeiro de 2028.
* **Meta:**

| Indicador | Meta |
| :--- | :--- |
| Clientes na macrorregião | **2.000 clientes até junho de 2029** |
| Ritmo de entrada | ≈ 83 clientes novos por mês |
| Total de clientes (micro + macro) | ≈ **3.100 clientes** |
| Faturamento com as metas atingidas | ≈ **R$ 307.000,00 por mês** |

* **Custos adicionais da expansão:**

| Custo | Valor |
| :--- | ---: |
| Anúncios (Google Ads e redes sociais) | R$ 15.000,00 / mês |
| Suporte remoto (2 atendentes) | R$ 10.000,00 / mês |
| Servidores maiores | + R$ 2.500,00 / mês |
| Custo de aquisição por cliente novo | R$ 250,00 por cliente |
| Comissão dos contadores parceiros | 10% da mensalidade |

  Só para pagar esses custos são necessários **359 clientes** na macrorregião.

* **Campanhas de Marketing:**
  * **Inbound Marketing / SEO:** Criação de conteúdos no blog do ERP focados em termos de busca de PMEs (ex: *"como controlar estoque de loja pequena"*, *"sistema PDV web"*).
  * **Tráfego Pago:** Anúncios segmentados no Google Ads e redes sociais para empresários e gerentes comerciais.
  * **Parcerias Estratégicas:** Parcerias com escritórios de contabilidade locais (contadores costumam indicar sistemas de gestão para seus clientes facilitarem a importação de dados fiscais).
* **Criação de Filiais / Suporte Regional:**
  * Transição para uma estrutura de suporte 100% remota e escalável (tickets, chat ao vivo e base de conhecimento automatizada), podendo abrir escritórios comerciais físicos em polos estratégicos de grandes capitais conforme a demanda cresça.
* **Novos Produtos e Serviços (Escala):**
  * Desenvolvimento de pacotes corporativos avançados.
  * Lançamento de módulos adicionais monetizados (ex: Módulo de Business Intelligence/Dashboards avançados, integração nativa com marketplaces e e-commerces, aplicativo mobile de PDV integrado via API REST).

### 2.3. Retorno do Investimento

Com o cenário escolhido (validação curta em Itapira e expansão em julho de 2027):

| Indicador | Resultado |
| :--- | :--- |
| Investimento máximo necessário | **≈ R$ 903 mil** (pior momento do caixa, em fevereiro de 2028) |
| Primeiro mês com lucro | **Março de 2028** |
| Retorno do investimento | **Abril de 2029 — cerca de 2 anos e 7 meses** após o início do projeto |
| Caixa acumulado em dezembro de 2030 | ≈ R$ 3,2 milhões |

#### Cenários comparados

Em todos os cenários Itapira chega a 1.100 clientes em dezembro de 2028 e a macrorregião soma 2.000 clientes em 24 meses; muda apenas quando a expansão começa.

| Cenário | Início da macro | Investimento máximo | Primeiro mês com lucro | Retorno | Caixa em dez/2030 |
| :--- | :---: | ---: | :---: | :---: | ---: |
| A) Itapira primeiro, depois macro | jan/2029 | R$ 748 mil | abr/2028 | abr/2030 (3,6 anos) | R$ 967 mil |
| B) Um ano de Itapira, depois macro | jan/2028 | R$ 887 mil | jul/2028 | ago/2029 (2,9 anos) | R$ 2,5 mi |
| **C) Macro logo após a Fase 2 (escolhido)** | **jul/2027** | **R$ 903 mil** | **mar/2028** | **abr/2029 (2,6 anos)** | **R$ 3,2 mi** |

O cenário C exige cerca de R$ 155 mil a mais de investimento que o A, mas devolve o dinheiro 1 ano antes e gera mais de 3 vezes mais caixa até 2030.

> **Observações:**
> * Os 11 mil estabelecimentos incluem MEIs, e parte deles não precisa de um ERP; 10% é uma meta ambiciosa para uma empresa nova. Com 5% (550 clientes), a mensalidade precisaria ser de R$ 129,00 só para cobrir os custos.
> * O investimento máximo de ≈ R$ 903 mil precisa de uma fonte definida (aporte dos sócios, investidor ou financiamento).
> * O cálculo usa a alíquota de 12,59% o tempo todo; no início, com faturamento menor, o Simples cobra menos, então o resultado real tende a ser um pouco melhor.
> * Cancelamentos de clientes não foram considerados; com eles, as metas de clientes sobem um pouco.
