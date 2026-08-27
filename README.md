# Arena Management System (Back-End)

![PHP Version](https://shields.io)
![Composer](https://shields.io)
![Database](https://shields.io)

Este é o sistema de back-end focado no **Gerenciamento de Arenas Esportivas**, desenvolvido como projeto universitário. A API controla de forma automatizada o fluxo cadastral de alunos, faturamento de mensalidades e restrições de acessos baseadas em inadimplência financeira.

---

## Escopo do Projeto

O sistema resolve um dos principais gargalos de gestão de complexos esportivos: a inadimplência. Através de um motor de regras, o back-end bloqueia automaticamente o acesso de alunos com mensalidades significativamente atrasadas e reativa o acesso de forma instantânea assim que o pagamento é identificado.

### Funcionalidades Chave:
* **Autenticação Tradicional:** Cadastro e Login de usuários utilizando e-mail e senhas criptografadas.
* **Controle de Status:** Usuários divididos entre as permissões `ALUNO` e `ADMINISTRADOR`.
* **Motor Financeiro:** Geração recorrente de faturas e acompanhamento de status (`PENDENTE` e `PAGA`).
* **Bloqueio Automático:** Restrição de login ou agendamento para alunos com **mais de 2 meses** de atraso.

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

### 2. Clonar o repositório
```bash
git clone https://github.com
```

### 3. Instalar as dependências do PHP
```bash
composer install
```

## Documentação Detalhada (Wiki)
Para acessar os diagramas de banco de dados, dicionário de tabelas, requisitos de software (RF/RNF) e detalhes do código PHP das regras, consulte a nossa [Wiki do GitHub](../../wiki).
