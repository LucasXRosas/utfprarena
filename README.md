# Gerenciamento de Arena de Beach Tennis (Back-End)

> **Tecnologias principais:**  PHP 8.x |  Composer |  SQL (Banco Relacional)

Este é o sistema de back-end focado no **Gerenciamento de Arenas de Beach Tennis**, desenvolvido como projeto universitário. A API controla de forma automatizada o fluxo cadastral de atletas, faturamento de mensalidades e restrições de acessos baseadas em inadimplência financeira.

---

## Escopo do Projeto

O sistema resolve um dos principais gargalos de gestão de complexos esportivos: a inadimplência. Através de um motor de regras, o back-end bloqueia automaticamente o acesso de alunos com mensalidades significativamente atrasadas e reativa o acesso de forma instantânea assim que o pagamento é identificado.

---

## Objetivos do Projeto

O objetivo principal deste projeto é desenvolver uma API de back-end robusta e escalável, utilizando o ecossistema PHP, capaz de centralizar e automatizar a gestão operacional e financeira de complexos esportivos de Beach Tennis. A plataforma atua de forma estratégica através dos seguintes objetivos específicos:

* **Automação do Fluxo de Caixa:** Substituir processos manuais de cobrança por um ciclo automatizado de faturamento recorrente, garantindo previsibilidade financeira para a arena.
* **Mitigação Ativa da Inadimplência:** Implementar um motor de regras estritas que monitora o status de pagamento em tempo real, aplicando restrições lógicas de acesso (bloqueio automático) assim que o teto de tolerância (2 meses de atraso) é atingido.
* **Segurança e Rastreabilidade de Acessos:** Garantir a integridade dos dados e o controle de acesso ao ecossistema através de autenticação tradicional segura e arquitetura baseada em tokens (JWT), impedindo o uso da infraestrutura por usuários irregulares.
* **Reativação Instantânea de Operações:** Eliminar o gargalo operacional do desbloqueio manual, restabelecendo as permissões e o status ativo do aluno de forma imediata assim que a pendência financeira mínima for liquidada.

---

## Diretrizes de Escopo

### Problemas Identificados (Justificativa)
No cenário real de gestão de arenas de Beach Tennis, foram mapeadas as seguintes dores que o sistema visa solucionar:
* **Evasão de Receita por Inadimplência:** Alunos frequentando as quadras de areia e utilizando os espaços mesmo com mensalidades atrasadas por longos períodos.
* **Sobrecarga na Gestão Manual:** Administradores gastando tempo revisando planilhas para descobrir quem pagou, quem está devendo e quem deve ter o acesso barrado.
* **Falhas de Segurança no Acesso:** Falta de um controle digital centralizado, permitindo que usuários não cadastrados ou irregulares utilizem as dependências da arena sem validação prévia.

### Fora de Escopo (O que o sistema NÃO faz)
Para garantir a entrega do projeto dentro do prazo letivo da universidade, as seguintes funcionalidades foram explicitamente definidas como fora de escopo do back-end:
* **Desenvolvimento de Interface Visual (Front-End):** O projeto limita-se estritamente ao desenvolvimento da API (rotas, regras e banco de dados), não incluindo telas, aplicativos mobile ou interfaces web.
* **Integração Real com Gateways de Pagamento:** O sistema não fará chamadas reais para operadoras de cartão ou bancos (ex: API do Stripe, Mercado Pago ou bancos tradicionais). Os pagamentos serão simulados via endpoints de testes ou webhooks mockados.
* **Gateway Físico de Catracas:** O bloqueio é lógico (rejeição de requisições na API). O projeto não contempla a integração com hardware de catracas físicas ou leitores biométricos de portarias.
* **Agendamento Avulso de Quadras por Não-Alunos:** O sistema foca no modelo de mensalistas regulares da arena. Aluguéis avulsos de quadras por usuários externos não serão processados nesta versão.

---

## Funcionalidades Chave

* **Autenticação Segura:** Fluxo tradicional de cadastro e login com criptografia e tokens de sessão.
* **Gestão de Mensalidades:** Geração de faturas mensais e controle do fluxo de caixa dos alunos.
* **Bloqueio Automatizado:** Motor de regras que restringe o acesso de alunos com mais de 2 meses de mensalidades em atraso.

---

## Lógica das Regras de Negócio

* **Regra de Bloqueio (Lote):** Um script automático (Cron Job/CLI) varre o banco de dados diariamente. Se um aluno ativo possuir **2 ou mais faturas vencidas**, seu status é alterado para `BLOQUEADO`.
* **Regra de Reativação (Gatilho):** No momento em que o aluno realiza o pagamento e o número de faturas vencidas cai para **menos de 2** (apenas 1 ou nenhuma), o back-end altera seu status imediatamente para `ATIVO`.

---

## Stack Técnica e Arquitetura

* **Linguagem:** PHP 8.x (Orientado a Objetos)
* **Persistência:** Driver PDO para conexão segura com Banco de Dados Relacional.
* **Gerenciador de Dependências:** Composer

---

## Como Executar o Projeto Localmente

### 1. Pré-requisitos
* PHP 8.x instalado localmente.
* Composer instalado.
* Banco de Dados Relacional configurado (MySQL/PostgreSQL).

### 2. Clonar o repositório
```bash
git clone https://github.com/LucasXRosas/utfprarena
cd utfprarena
```

### 3. Instalar as dependências do PHP
```bash
composer install
```

## Documentação Detalhada (Wiki)
Para acessar os diagramas de banco de dados, dicionário de tabelas, requisitos de software (RF/RNF) e detalhes do código PHP das regras, consulte a nossa [Wiki do GitHub](../../wiki).
