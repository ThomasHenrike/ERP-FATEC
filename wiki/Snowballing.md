# Fundamentação Teórica: Arquitetura Laravel para o Sistema ERP

Este documento compila a base teórica e técnica que justifica a escolha do framework **Laravel (PHP)** para o desenvolvimento do ecossistema ERP modular e *multi-tenant*. A validação foi conduzida através da técnica metodológica de **Snowballing** (Amostragem em Bola de Neve).

## O Artigo-Raiz (Ponto de Partida)
Nossa análise partiu do artigo acadêmico **"SISTEMA ERP - GERENCIADOR DE ESTOQUE"** (Lopes & Sousa, 2021 - IFSP Caraguatatuba). A partir deste documento central, rastreamos a literatura para trás (fundamentos que o sustentam) e para frente (evolução da tecnologia no mercado corporativo atual).

---

## 1. Backward Snowballing (As Raízes - Artigos "Pra Trás")
O *Backward Snowballing* consiste em analisar as referências utilizadas pelo artigo-raiz para encontrar os conceitos fundamentais. Identificamos os seguintes trabalhos que validam a arquitetura e a estratégia do nosso ERP:

*   **Desenvolvimento de sistema de gestão de estoque personalizado com o framework Laravel (2019)**
    *   **Autores:** Oliveira, et al. (Universidade Estadual do Centro-Oeste).
    *   **Relevância:** Artigo central do Backward. Valida que o framework Laravel (PHP) é a escolha acadêmica e técnica correta para construir e arquitetar as regras complexas de um sistema de gestão de estoque.
    *   **Acesso:** [Link para o PDF](https://www2.unicentro.br/decomp/files/2019/03/TCC_Rennan_Formatacao-Final-1.pdf)

*   **A importância do uso de software ERP como ferramenta para um eficiente controle de estoque (2021)**
    *   **Autores:** Padula, et al. (FATEC Mogi das Cruzes / FATECLOG).
    *   **Relevância:** Justifica estrategicamente como a integração de um ERP centralizado resolve gargalos de processos, elimina redigitação e garante a integridade dos dados na empresa.
    *   **Acesso:** [Link para o PDF](https://fateclog.com.br/anais/2021/17-15-1-RV.pdf)

---

## 2. Forward Snowballing (Evolução Tecnológica - Artigos "Pra Frente")
O *Forward Snowballing* avalia o impacto e o futuro das tecnologias citadas na base. Projetando esses conceitos para o cenário corporativo moderno, mapeamos publicações e casos de estudo reais que provam a eficácia do Laravel para ERPs com suporte multi-tenant:

*   **The Ultimate Guide to Developing an ERP System with Laravel**
    *   **Fonte:** SlashDev.
    *   **Relevância:** Prova definitiva de que o ecossistema do Laravel (Eloquent ORM, migrações e padrão MVC) é amplamente adotado no mercado para criar ERPs robustos, escaláveis e modulares.
    *   **Acesso:** [Link para a publicação](https://slashdev.io/blog/the-ultimate-guide-to-developing-an-erp-system-with-laravel)

*   **Case de Estudo: Laravel-Powered Shipping ERP Slashes Manual Work by 90%**
    *   **Fonte:** BlackDot.
    *   **Relevância:** Estudo de caso prático da indústria logística. Demonstra um ERP feito em Laravel processando milhões de registros, utilizando filas (*Queues* e *Jobs*) em background para precificação e relatórios pesados sem travar a aplicação.
    *   **Acesso:** [Link para o estudo de caso](https://www.blackdot.co/case-studies/laravel-powered-shipping-erp-slashes-manual-work-by-90-unlocks-5m-in-business-value)

*   **PHP Laravel Development Services | Scalable Web Apps & Multi-Tenancy**
    *   **Fonte:** Encrypted Info Web.
    *   **Relevância:** Valida a arquitetura **Multi-Tenant** (Múltiplos Inquilinos), essencial para o nosso projeto, demonstrando como o Laravel isola bancos de dados e rotas para que uma única instância atenda a diversas empresas com segurança.
    *   **Acesso:** [Link para a documentação técnica](https://encryptedinfoweb.com/web-development/php-laravel-development)

---

## Conclusão
O rastreamento bibliográfico nas direções *Backward* e *Forward* atesta que a adoção do **Laravel** para este projeto ERP é uma decisão estratégica sólida. A stack tecnológica é amparada tanto pela literatura acadêmica rigorosa quanto pela aprovação massiva do mercado corporativo, garantindo um código modular, segurança nativa e flexibilidade para escalonamento *multi-tenant*.