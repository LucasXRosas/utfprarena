# Roteiro de Gravação — Entrega "Relação NxN + Ajax"

**Sistema:** UTFPR Arena Beach Tennis
**Branch:** `feature/turmas-nxn`
**Funcionalidade entregue:** **Turmas de treino × Alunos (relação NxN)**. Uma turma (`training_classes`) possui N alunos e um aluno (`users`) participa de N turmas. A ligação fica na **tabela pivô** `class_enrollments`. O administrador matricula e remove alunos de uma turma **via Ajax** (sem recarregar a página); o aluno vê as próprias turmas na Área do Aluno.

> **Como usar este roteiro:** as seções seguem a ordem da rubrica. Os blocos **🎬 AÇÃO** dizem o que fazer na tela, os blocos **🗣️ FALA** trazem o texto sugerido para narrar e os blocos **📁 ARQUIVO** dizem qual arquivo abrir no VS Code e em que linha. No fim estão o **inventário de todos os arquivos criados e alterados** (Apêndice A), o checklist da rubrica (Apêndice B), perguntas prováveis (Apêndice C) e as **referências**.

---

## 0. Preparação (antes de apertar o REC)

### 0.1 Ambiente

```bash
git checkout feature/turmas-nxn
cp .env.example .env            # se ainda não existir
./run composer install
./run up -d
./run db:reset                  # recria TODAS as tabelas, incluindo training_classes e class_enrollments
./run db:populate               # usuários, quadras, faturas, 4 turmas e 6 matrículas
```

Abra `http://localhost:8000`.

### 0.2 Dados do populate (decore isto, ajuda na narração)

| Turma (id) | Nível | Vagas | Alunos matriculados |
|---|---|---|---|
| #1 Iniciante - Terça e Quinta 19h | Iniciante | 8 | Aluno Beach Tennis (#2), Beatriz Lima (#4), Carlos Eduardo (#5) |
| #2 Intermediário - Segunda e Quarta 20h | Intermediário | 6 | Aluno Beach Tennis (#2), Eduardo Mendes (#7) |
| #3 Avançado - Sábado 9h | Avançado | 4 | Carlos Eduardo (#5) |
| #4 Kids - Sábado 10h | Iniciante | 10 | **nenhum** (é a turma que pode ser excluída) |

Repare que **Aluno Beach Tennis** está em 2 turmas e a **turma Iniciante** tem 3 alunos: isso já mostra os dois "N".

### 0.3 Janelas abertas

1. **Navegador** em `http://localhost:8000` com o **DevTools aberto (F12)** nas abas **Network** (filtro **Fetch/XHR**) e **Console**. Use uma janela anônima para o aluno.
2. **VS Code** com o projeto aberto.
3. **Terminal** com o MySQL aberto: `./run db:console`.

### 0.4 Credenciais

| Perfil | E-mail | Senha |
|---|---|---|
| Admin | `admin@arena.com` | `admin123` |
| Aluno | `aluno@arena.com` | `aluno123` |

### 0.5 Divisão sugerida entre os integrantes

| Bloco | Tempo aprox. | Quem |
|---|---|---|
| Abertura + Demonstração prática (Seção 1) | 7–8 min | Gabriel |
| Conceitos (Seção 2) | 5–6 min | Lucas |
| Código, framework e banco (Seção 3) | 10–12 min | Lucas |
| Testes (Seção 4) + Encerramento | 6–7 min | Gabriel |

---

## Abertura (≈ 40 s)

🗣️ **FALA**
> "Olá! Somos o grupo do **UTFPR Arena**, um sistema de gerenciamento de arenas de Beach Tennis. Nesta última entrega implementamos uma **relação N para N**: **turmas de treino e alunos**. Uma turma tem vários alunos e um aluno pode estar em várias turmas. Essa ligação fica numa **tabela pivô**, a `class_enrollments`, que é a tabela de matrículas. O administrador matricula e remove alunos **via Ajax**, sem recarregar a página, e o aluno vê as turmas dele na Área do Aluno. Vamos mostrar o sistema funcionando, o DevTools, os conceitos, o código, o banco e os testes."

---

## 1. Demonstração Prática no Sistema (Relação NxN) — Peso 2

### 1.0 Contexto da tela

🎬 **AÇÃO**
1. Faça login como **admin**.
2. Mostre o novo link **"Turmas"** na navbar (e o botão **"Turmas"** no Painel Gerencial).
3. Clique: aparece `/admin/classes` com a tabela de turmas e a coluna **Alunos** no formato `matriculados/vagas` (ex.: `3/8`).

🗣️ **FALA**
> "Esta é a tela de turmas. A coluna Alunos mostra quantos alunos estão matriculados e quantas vagas a turma tem. Esse número vem da tabela pivô."

### 1.1 Registro da relação

🎬 **AÇÃO**
1. Clique em **Alunos** na turma **Avançado - Sábado 9h** (`/admin/classes/3`). Mostre: 1/4 alunos, só o Carlos.
2. No card **"Matricular Aluno"**, abra o select. Repare que o **Carlos não aparece**, porque o select só lista alunos que ainda **não** estão na turma (e só alunos, nunca o admin).
3. Selecione **Beatriz Lima** e clique em **Matricular**.
4. Mostre: a mensagem verde **"Aluno Beatriz Lima matriculado com sucesso na turma Avançado - Sábado 9h!"**, a nova linha na tabela, o contador passando para **2/4** e a Beatriz sumindo do select. **A página não recarregou** (a URL é a mesma e a barra de rolagem não mexeu).
5. Matricule também o **Eduardo Mendes** (3/4).
6. Tente matricular sem escolher aluno: aparece **"Selecione um aluno válido para matricular."**, que é um erro devolvido pelo servidor em JSON.
7. Opcional (regra de negócio): matricule mais um aluno para chegar a **4/4**. Aparece o selo **LOTADA**. Tente mais um: **"Não foi possível matricular ...: a turma está lotada!"**.

🗣️ **FALA**
> "Ao clicar em Matricular, o JavaScript envia um POST para `/admin/classes/3/enrollments` em segundo plano. O servidor grava **uma linha na tabela pivô** com o id da turma e o id do aluno e responde um JSON. Com esse JSON o JavaScript acrescenta a linha, atualiza o contador e refaz o select, sem recarregar a página."

### 1.2 Visualização da relação (pelos dois lados)

🎬 **AÇÃO**
1. **Lado turma → alunos:** volte a `/admin/classes` e abra a turma **Iniciante** (`/admin/classes/1`): Aluno Beach Tennis, Beatriz e Carlos.
2. Clique no nome **Aluno Beach Tennis**: abre a ficha do atleta (`/admin/users/2`). No card **"Turmas do Atleta"** aparecem **Iniciante** e **Intermediário**.
3. **Lado aluno → turmas:** na janela anônima, faça login como **aluno** (`aluno@arena.com`). Na **Área do Aluno**, mostre o card **"Minhas Turmas"** com **2 turma(s)**, o nível, o horário e a lotação de cada uma.

🗣️ **FALA**
> "É a mesma relação vista pelos dois lados. Na turma usamos `$turma->students`, e no aluno usamos `$aluno->trainingClasses`. Os dois métodos usam a classe `BelongsToMany` do framework e a mesma tabela pivô. A diferença é só a ordem das chaves estrangeiras."

### 1.3 Remoção da relação

🎬 **AÇÃO**
1. Na janela do admin, em `/admin/classes/3`, clique em **Remover** na linha do **Eduardo Mendes** e confirme o `confirm()`.
2. Mostre a mensagem **"Aluno Eduardo Mendes removido da turma Avançado - Sábado 9h."**: a linha some, o contador cai e o Eduardo **volta para o select**.
3. Abra `/admin/classes/2` (Intermediário): o Eduardo **continua** lá.

🗣️ **FALA**
> "A remoção apaga **só a linha da tabela pivô**. A turma continua existindo, o aluno continua existindo e continua nas outras turmas. O que deixa de existir é o vínculo entre os dois."

### 1.4 Tentativa de remoção de registros com dependências

🎬 **AÇÃO**
1. Vá para `/admin/classes` e clique em **Excluir** na turma **Iniciante** (3 alunos). Confirme.
2. Mostre a mensagem vermelha: **"Não é possível excluir a turma Iniciante - Terça e Quinta 19h: ela possui 3 aluno(s) matriculado(s). Remova as matrículas antes de excluir."** A turma continua na lista.
3. Agora no **MySQL**, tente passar por cima da aplicação:
   ```sql
   DELETE FROM training_classes WHERE id = 1;
   -- ERROR 1451 (23000): Cannot delete or update a parent row: a foreign key constraint fails
   -- (... CONSTRAINT `fk_class_enrollments_class` FOREIGN KEY (`training_class_id`) REFERENCES `training_classes` (`id`) ON DELETE RESTRICT ...)
   ```
4. Volte ao navegador e exclua a turma **Kids - Sábado 10h** (sem alunos): **"Turma Kids - Sábado 10h excluída com sucesso!"**.

🗣️ **FALA**
> "A turma tem dependentes, que são as matrículas, então não pode ser excluída. A regra está em **duas camadas**. A aplicação confere antes e mostra uma mensagem amigável. O banco garante a mesma regra com **`ON DELETE RESTRICT`**: mesmo se alguém rodar um DELETE direto no MySQL, o erro 1451 bloqueia. A turma Kids não tem dependentes e é excluída normalmente."
>
> "Do lado do aluno escolhemos **CASCADE**: se um atleta é excluído do sistema, as matrículas dele são removidas junto, igual às faturas. Não faz sentido manter matrícula de alguém que não existe, mas faz sentido proteger uma turma que ainda tem alunos."

### 1.5 Demonstração com requisição Ajax no DevTools

🎬 **AÇÃO**
1. Em `/admin/classes/3`, com o **DevTools > Network** aberto e o filtro **Fetch/XHR**, limpe a lista (🚫).
2. Matricule a **Daniela Souza**. Clique na requisição **`enrollments`** que apareceu e mostre:
   - **Headers > General:** `Request Method: POST`, `Status Code: 201 Created`.
   - **Headers > Request Headers:** `Accept: application/json` e `X-Requested-With: XMLHttpRequest`.
   - **Headers > Response Headers:** `Content-Type: application/json; charset=utf-8`.
   - **Payload:** `user_id: 6` (Form Data).
   - **Preview/Response:** o JSON:
     ```json
     {
       "success": true,
       "message": "Aluno Daniela Souza matriculado com sucesso na turma Avançado - Sábado 9h!",
       "errors": {},
       "student": { "id": 6, "name": "Daniela Souza", "email": "daniela@arena.com", "status": "blocked" },
       "training_class": { "id": 3, "name": "Avançado - Sábado 9h", "capacity": 4, "students_count": 3, "is_full": false },
       "available_students": [ { "id": 2, "name": "Aluno Beach Tennis", "email": "aluno@arena.com" }, "..." ]
     }
     ```
3. Remova a Daniela e mostre a nova requisição: **`Request Method: DELETE`**, URL `/admin/classes/3/enrollments/6`, **`Status Code: 200 OK`**.
4. Tente matricular sem escolher aluno: **`422 Unprocessable Content`** com `"success": false`.
5. Troque o filtro para **Doc**: **nenhum documento novo** foi carregado. É a prova de que a página não recarregou.
6. Aba **Console**: mostre os logs `[Ajax] POST ... -> readyState 1 OPENED`, `2 HEADERS_RECEIVED`, `3 LOADING`, `4 DONE` e o JSON recebido.

🗣️ **FALA**
> "Aqui está a requisição Ajax. É um POST comum, mas feito pelo JavaScript, com o cabeçalho `Accept: application/json`. É esse cabeçalho que faz o controller responder **JSON em vez de HTML**. O status também tem significado: **201 Created** quando a matrícula é criada, **422** quando há erro de validação e **200** na remoção. A remoção usa o verbo HTTP **DELETE** de verdade. No console aparecem os estados da requisição, de 0 a 4, que vamos explicar nos conceitos."

---

## 2. Explicação de Conceitos — Peso 1,5

> **Exigência da rubrica:** cada conceito precisa de uma **referência de livro** com nome do livro e capítulo. Cada item abaixo tem:
> 1. a **FALA** explicando o conceito;
> 2. o bloco 📚 com **livro, autor, edição, editora, ano, número e nome do capítulo** (e seção);
> 3. uma **citação pronta para falar no vídeo**, que diz o livro e o capítulo em voz alta.
>
> **Dica para a gravação:** ao citar, mostre na tela a seção **"Referências bibliográficas"** no fim deste roteiro, ou a capa e o sumário do livro. Todas as referências estão reunidas lá.

### 2.1 Definição conceitual de NxN

🗣️ **FALA**
> "Um relacionamento **N para N**, ou **muitos para muitos (M:N)**, acontece quando uma ocorrência de uma entidade se associa a **várias** ocorrências da outra, e vice-versa. Uma turma tem vários alunos e um aluno frequenta várias turmas. No modelo relacional esse relacionamento **não cabe numa chave estrangeira simples**: não dá para pôr `user_id` na turma, porque são vários, nem `training_class_id` no aluno, pelo mesmo motivo. Por isso ele vira uma **nova tabela**, a tabela associativa ou pivô. Ela tem **uma FK para cada lado**, e a chave dela é a combinação das duas FKs. Na prática, um N:N vira **dois relacionamentos 1:N**: turma 1:N matrícula e aluno 1:N matrícula."

📚 **Referência bibliográfica**
- **Livro:** *Sistemas de Banco de Dados*, 7. ed. Autores: Ramez Elmasri e Shamkant B. Navathe. São Paulo: Pearson, 2018.
  - **Capítulo 3 – "Modelagem de dados usando o modelo Entidade-Relacionamento (ER)"**, seção 3.4 (restrições em tipos de relacionamento binários: **razão de cardinalidade 1:1, 1:N e M:N**).
  - **Capítulo 9 – "Projeto de banco de dados relacional por mapeamento ER e EER para relacional"**, seção 9.1, **passo 5: "Mapeamento de tipos de relacionamento binários M:N"**, que cria uma nova relação com as chaves primárias das duas entidades como chaves estrangeiras.
- **Complementar:** *Projeto de Banco de Dados*, 6. ed. Autor: Carlos Alberto Heuser. Porto Alegre: Bookman, 2009. **Capítulo 5 – "Transformações entre modelos"** (relacionamento n:n implementado como tabela própria).

🗣️ **Citação para falar no vídeo:** *"Segundo Elmasri e Navathe, no livro Sistemas de Banco de Dados, capítulo 9, um relacionamento M:N é mapeado criando uma nova relação que contém as chaves primárias das duas entidades como chaves estrangeiras."*

📁 `database/schema.sql`, linhas 85–100 (tabela `class_enrollments`).

### 2.2 `index unique: true` — sua aplicação usa? Por quê?

🗣️ **FALA**
> "`unique: true` é a opção que cria um **índice único**: o banco não aceita duas linhas com o mesmo valor nas colunas indexadas. **Sim, usamos**, e em dois lugares:
> 1. **`UNIQUE KEY uq_class_enrollments_class_user (training_class_id, user_id)`**, um índice único **composto** na pivô. Ele impede que o **mesmo aluno seja matriculado duas vezes na mesma turma**. Cada coluna sozinha pode repetir, porque um aluno aparece em várias turmas e uma turma aparece com vários alunos. O que não pode repetir é **o par**.
> 2. `UNIQUE KEY uq_training_classes_name (name)`: duas turmas não podem ter o mesmo nome.
>
> **Por que no banco, se o model já valida?** Porque a validação da aplicação faz um SELECT e depois um INSERT. Se chegarem duas requisições ao mesmo tempo, por exemplo um duplo clique, as duas podem passar no SELECT. Só o índice único garante a regra de forma **atômica**. Além disso, o índice **acelera** as consultas: como `training_class_id` é a primeira coluna dele, o MySQL o usa para a busca 'alunos da turma X' do `BelongsToMany` e para a FK. Para a busca pelo outro lado criamos o índice `idx_class_enrollments_user_id`."

🎬 Mostre no MySQL:
```sql
SHOW INDEX FROM class_enrollments;
INSERT INTO class_enrollments (training_class_id, user_id) VALUES (1, 2);
-- ERROR 1062 (23000): Duplicate entry '1-2' for key 'class_enrollments.uq_class_enrollments_class_user'
```

📚 **Referência bibliográfica**
- **Livro:** *Sistemas de Banco de Dados*, 7. ed. Autores: Ramez Elmasri e Shamkant B. Navathe. São Paulo: Pearson, 2018.
  - **Capítulo 5 – "O modelo de dados relacional e as restrições em bancos de dados relacionais"**, seção 5.2 (**restrições de chave**: nenhuma tupla pode repetir o valor da chave).
  - **Capítulo 17 – "Estruturas de indexação para arquivos e projeto físico de banco de dados"**, seção 17.4 (**índices em chaves múltiplas**, ou seja, índices compostos).
- **Complementar:** *Sistema de Banco de Dados*, 7. ed. Autores: Abraham Silberschatz, Henry F. Korth e S. Sudarshan. Rio de Janeiro: GEN LTC, 2020. **Capítulo 14 – "Indexação"** (índices ordenados e índices em chaves de busca múltiplas).

🗣️ **Citação para falar no vídeo:** *"Como explicam Elmasri e Navathe no capítulo 5 de Sistemas de Banco de Dados, a restrição de chave garante que duas tuplas não tenham o mesmo valor nos atributos da chave. No capítulo 17 eles mostram que um índice pode ser feito sobre vários atributos, que é o nosso índice único composto."*

📁 `database/schema.sql`, linha 90 · `app/Models/Enrollment.php`, linhas 56–60 (a mesma regra na aplicação, com mensagem amigável).

### 2.3 Definição de Ajax

🗣️ **FALA**
> "**Ajax** (*Asynchronous JavaScript and XML*) é uma técnica, não uma linguagem. O JavaScript da página faz requisições HTTP **em segundo plano**, recebe só os **dados**, hoje quase sempre em JSON e não mais em XML, e atualiza **apenas a parte da página** que mudou, manipulando o DOM. O termo foi criado por Jesse James Garrett em 2005. A vantagem é a experiência do usuário: não há tela branca, a página não perde estado e trafegam menos dados. No nosso sistema, matricular e remover alunos são requisições Ajax."

📚 **Referência bibliográfica**
- **Livro:** *JavaScript: O Guia Definitivo*, 6. ed. Autor: David Flanagan. Porto Alegre: Bookman, 2013.
  - **Capítulo 18 – "HTTP com scripts"**, introdução do capítulo (definição de **Ajax**) e seção 18.1 "Usando XMLHttpRequest".
- **Complementar (artigo, não é livro):** GARRETT, Jesse James. *Ajax: A New Approach to Web Applications*, 2005. É o texto que criou o termo.

🗣️ **Citação para falar no vídeo:** *"Segundo David Flanagan, em JavaScript: O Guia Definitivo, capítulo 18, Ajax é o uso de scripts para fazer requisições HTTP sem recarregar a página, atualizando só a parte necessária do documento."*

### 2.4 Diferença entre requisição síncrona e assíncrona

🗣️ **FALA**
> "Numa requisição **síncrona**, o código **para e espera** a resposta. No navegador isso congela a interface: o usuário não clica, não rola a página, nada acontece até o servidor responder. Numa requisição **assíncrona**, o código **dispara a requisição e continua executando**. Quando a resposta chega, uma função é chamada, que pode ser um callback, um evento ou uma Promise. Toda navegação tradicional de formulário também é 'síncrona' do ponto de vista do usuário, porque ele espera a página nova inteira. No nosso código, o terceiro parâmetro de `xhr.open(method, url, true)` é o **`true` = assíncrona**. A versão síncrona (`false`) é desaconselhada pelos navegadores justamente por travar a página."

📚 **Referência bibliográfica**
- **Livro:** *JavaScript: O Guia Definitivo*, 6. ed. Autor: David Flanagan. Porto Alegre: Bookman, 2013.
  - **Capítulo 18 – "HTTP com scripts"**, seção 18.1.2 "Recuperando a resposta", subseção **"Respostas síncronas"** (o terceiro argumento de `open()` e por que o modo síncrono trava o navegador).
- **Complementar:** *JavaScript: O Guia Definitivo*, 7. ed. Autor: David Flanagan. Porto Alegre: Bookman, 2022. **Capítulo 13 – "JavaScript assíncrono"**, seção 13.1 (programação assíncrona com callbacks).

🗣️ **Citação para falar no vídeo:** *"Flanagan, no capítulo 18 de JavaScript: O Guia Definitivo, mostra que o XMLHttpRequest é assíncrono por padrão e que a forma síncrona bloqueia o navegador até a resposta chegar, por isso deve ser evitada."*

📁 `public/assets/js/enrollments.js`, linha 46.

### 2.5 Definição e estrutura do JSON

🗣️ **FALA**
> "**JSON** (*JavaScript Object Notation*) é um formato **texto** de troca de dados, independente de linguagem, baseado na sintaxe de literais do JavaScript. A estrutura tem **dois tipos compostos**: **objeto**, que são pares `"chave": valor` entre chaves `{ }`, com a chave sempre entre aspas duplas, e **array**, que é uma lista ordenada entre colchetes `[ ]`. E tem **quatro tipos primitivos**: string, número, booleano (`true`/`false`) e `null`. Não aceita comentários, funções nem vírgula sobrando no final. No PHP geramos o JSON com `json_encode()`. No JavaScript lemos com `JSON.parse()`. A nossa resposta é um objeto com `success` (booleano), `message` (string), `student` e `training_class` (objetos) e `available_students` (array de objetos)."

📚 **Referência bibliográfica**
- **Livro:** *O Melhor do JavaScript*. Autor: Douglas Crockford (criador do JSON). Rio de Janeiro: Alta Books, 2008.
  - **Apêndice E – "JSON"**: sintaxe, os tipos de valores (objeto, array, string, número, booleano e `null`) e a estrutura do formato.
- **Complementar:** *JavaScript: O Guia Definitivo*, 7. ed. Autor: David Flanagan. Porto Alegre: Bookman, 2022. **Capítulo 11 – "A biblioteca-padrão de JavaScript"**, seção 11.6 **"Serialização e análise de JSON"** (`JSON.stringify` e `JSON.parse`).
- **Norma (não é livro):** RFC 8259 / ECMA-404.

🗣️ **Citação para falar no vídeo:** *"Douglas Crockford, criador do formato, explica no apêndice E do livro O Melhor do JavaScript que o JSON é um formato texto para troca de dados, formado por objetos, arrays e quatro tipos de valores simples."*

📁 `app/views/admin/enrollments/result.json.php` (monta o array) · `core/Http/Controllers/Controller.php`, linha 44 (`renderJson` faz o `json_encode`) · `public/assets/js/enrollments.js`, linha 37 (`JSON.parse`).

### 2.6 Estados da requisição (readyState)

🗣️ **FALA**
> "O `XMLHttpRequest` tem a propriedade **`readyState`**, que diz em que fase a requisição está. Cada mudança dispara o evento `readystatechange`:
> - **0 – UNSENT:** o objeto foi criado, mas o `open()` ainda não foi chamado;
> - **1 – OPENED:** o `open()` foi chamado; já dá para definir cabeçalhos e chamar o `send()`;
> - **2 – HEADERS_RECEIVED:** o `send()` foi feito e chegaram o **status** e os **cabeçalhos** da resposta;
> - **3 – LOADING:** o corpo da resposta está sendo recebido;
> - **4 – DONE:** a operação terminou, com sucesso ou com erro de rede.
>
> Só no estado 4 o corpo está completo. Por isso o nosso código só faz o `JSON.parse` quando `readyState === XMLHttpRequest.DONE`. Usamos o XMLHttpRequest, e não o `fetch`, justamente para conseguir mostrar esses estados no console."

🎬 Mostre o Console do DevTools com os logs `readyState 1 → 2 → 3 → 4` de uma matrícula.

📚 **Referência bibliográfica**
- **Livro:** *JavaScript: O Guia Definitivo*, 6. ed. Autor: David Flanagan. Porto Alegre: Bookman, 2013.
  - **Capítulo 18 – "HTTP com scripts"**, seção 18.1.2 **"Recuperando a resposta"**, com a **tabela 18-1, que lista os valores de `readyState`** (UNSENT, OPENED, HEADERS_RECEIVED, LOADING e DONE) e o evento `readystatechange`.
- **Norma (não é livro):** WHATWG *XMLHttpRequest Living Standard*.

🗣️ **Citação para falar no vídeo:** *"Flanagan apresenta, no capítulo 18 de JavaScript: O Guia Definitivo, a tabela com os cinco valores de readyState, de 0 a 4. O corpo da resposta só está completo no estado 4."*

📁 `public/assets/js/enrollments.js`, linhas 14 (nomes dos estados) e 25–43 (`onreadystatechange`).

### 2.7 Async, Promise e await

🗣️ **FALA**
> "Uma **Promise** é um objeto que representa um valor que **ainda vai existir**: o resultado de uma operação assíncrona. Ela começa **pendente** (*pending*) e termina **resolvida** (*fulfilled*, quando chamamos `resolve`) ou **rejeitada** (*rejected*, quando chamamos `reject`). Nossa função `requestJson` embrulha o XMLHttpRequest numa Promise. Ela resolve com `{ status, data }` quando chega a resposta e rejeita em caso de falha de rede.
>
> **`async`** marca uma função como assíncrona; ela sempre retorna uma Promise. Dentro dela, **`await`** pausa **apenas aquela função** até a Promise terminar e devolve o valor resolvido. Se a Promise for rejeitada, o `await` lança a exceção, que tratamos com `try/catch`. O código fica com cara de síncrono, mas **não bloqueia o navegador**: enquanto espera, a página continua respondendo. O `finally` reabilita o botão em qualquer caso."

📚 **Referência bibliográfica**
- **Livro:** *JavaScript: O Guia Definitivo*, 7. ed. Autor: David Flanagan. Porto Alegre: Bookman, 2022.
  - **Capítulo 13 – "JavaScript assíncrono"**, seção 13.2 **"Promessas"** (estados pendente, resolvida e rejeitada; `then`/`catch`) e seção 13.3 **"async e await"**.

🗣️ **Citação para falar no vídeo:** *"Segundo Flanagan, no capítulo 13 de JavaScript: O Guia Definitivo, uma Promise representa o resultado futuro de uma operação assíncrona, e as palavras-chave async e await permitem escrever esse código como se fosse síncrono, sem bloquear o navegador."*

📁 `public/assets/js/enrollments.js`, linhas 20–51 (`new Promise`) e 157–182 (`async function` + `await requestJson(...)` + `try/catch/finally`).

---

## 3. Explicação do Código — Peso 2,5

### Visão geral da arquitetura (mostrar por 30 s)

```
Navegador (enrollments.js) --XHR + Accept: application/json--> Nginx --> public/index.php
   --> Router (config/routes.php) --> middlewares 'auth' + 'admin'
   --> Admin\EnrollmentsController --> TrainingClass::enroll()/unenroll() --> Enrollment (pivô) --> MySQL
   <-- renderJson('admin/enrollments/result') <-- JSON
```

| Camada | Arquivo |
|---|---|
| Rotas | `config/routes.php`, linhas 65–77 |
| Controllers | `app/Controllers/Admin/TrainingClassesController.php` (CRUD de turmas) · `app/Controllers/Admin/EnrollmentsController.php` (matrículas / Ajax) |
| Models | `app/Models/TrainingClass.php` · `app/Models/Enrollment.php` (pivô) · `app/Models/User.php` (`trainingClasses()`) |
| Views | `app/views/admin/classes/{index,new,show}.phtml` · `app/views/admin/enrollments/result.json.php` · `app/views/dashboard/index.phtml` · `app/views/admin/users/show.phtml` |
| JavaScript | `public/assets/js/enrollments.js` |
| Framework | `core/Database/ActiveRecord/BelongsToMany.php` · `core/Database/ActiveRecord/Model.php` · `core/Http/Controllers/Controller.php` (`renderJson`) · `core/Http/Request.php` (`acceptJson`) |
| Banco | `database/schema.sql`, linhas 68–100 · `database/Populate/Seeder.php`, linhas 138–156 |

### 3.1 Fluxo de execução do código (Relação NxN)

#### 3.1.1 Registro da relação

📁 Siga nesta ordem:
1. **View** `app/views/admin/classes/show.phtml`, linhas 35–53: um `<form>` comum (`POST /admin/classes/{id}/enrollments`, campo `user_id`) com `data-ajax="enroll"`. As opções do select vêm de `TrainingClass::availableStudents()`.
2. **JS** `public/assets/js/enrollments.js`, linhas 157–182: o `submit` é interceptado com `event.preventDefault()`, que **impede a navegação**. O `FormData(enrollForm)` monta o corpo e o `await requestJson("POST", ...)` envia.
3. **Rota** `config/routes.php`, linha 74: `POST /admin/classes/{class_id}/enrollments`, dentro dos grupos `auth` + `admin`.
4. **Controller** `app/Controllers/Admin/EnrollmentsController.php`, linhas 23–59 (`create`): busca a turma (404 se não existir), busca o aluno (422 se inválido) e chama `$trainingClass->enroll($student)`.
5. **Model** `app/Models/TrainingClass.php`, linhas 126–135 (`enroll`): cria um `Enrollment` com as duas FKs e chama `save()`.
6. **Validação** `app/Models/Enrollment.php`, linhas 43–71 (`validates`): campos obrigatórios → as duas FKs existem (`foreignKeyExists`) → **unicidade do par** (`uniqueness(['training_class_id','user_id'])`) → só alunos → turma não lotada. Se tudo passar, o `Model::save()` faz o `INSERT INTO class_enrollments`.
7. **Resposta** `EnrollmentsController.php`, linhas 100–131 (`respond`): se `$request->acceptJson()` (cabeçalho `Accept: application/json`), define o status (201/422/404) e chama `renderJson('admin/enrollments/result', ...)`. Senão, faz flash message + redirect: **sem JavaScript o sistema continua funcionando**.
8. **JSON** `app/views/admin/enrollments/result.json.php`: monta `$json` com `success`, `message`, `errors`, `student`, `training_class` (contador e lotação) e `available_students`.
9. **De volta ao JS**, linhas 166–177: se `status === 201`, `createStudentRow()` cria a linha via DOM (com `textContent`, que evita XSS), `showFeedback()` mostra a mensagem, `updateCounter()` atualiza `X/Y` e o selo LOTADA, e `updateSelect()` refaz o select.

🗣️ **FALA**
> "O ponto principal é que o mesmo endpoint serve dois clientes. O formulário HTML tradicional recebe redirect com flash, e o Ajax recebe JSON. Quem decide é o cabeçalho `Accept`, lido pelo `Request::acceptJson()` do framework."

#### 3.1.2 Visualização da relação

📁
- **Lado turma → alunos:** `app/Controllers/Admin/TrainingClassesController.php`, linhas 64–77 (`show`): `$trainingClass->students` → `TrainingClass::students()` (`app/Models/TrainingClass.php`, linhas 94–97) → `BelongsToMany(User::class, 'class_enrollments', 'training_class_id', 'user_id')`.
- **Lado aluno → turmas:** `app/Models/User.php`, linhas 201–204 (`trainingClasses()`), com a mesma pivô e **as chaves invertidas** (`'user_id', 'training_class_id'`). É usado em `app/Controllers/DashboardController.php`, linha 24 (card "Minhas Turmas", `app/views/dashboard/index.phtml`, linha 111) e em `app/Controllers/Admin/UsersController.php`, linha 93 (card "Turmas do Atleta").
- **Contagem:** `TrainingClass::studentsCount()` (linha 107) usa `students()->count()`, um `COUNT` no banco, sem carregar os alunos.

#### 3.1.3 Remoção da relação

📁
1. **View** `show.phtml`, linhas 96–103: cada linha tem um form `data-ajax="unenroll"` com `_method=DELETE`.
2. **JS** `enrollments.js`, linhas 185–218: **delegação de eventos**. O listener fica no `<tbody>`, então funciona também para linhas criadas pelo Ajax. Depois do `confirm()`, faz `await requestJson("DELETE", form.action)`, um **DELETE HTTP de verdade**. Com status 200, remove a `<tr>` e atualiza contador e select.
3. **Rota** `config/routes.php`, linha 76: `DELETE /admin/classes/{class_id}/enrollments/{user_id}`.
4. **Controller** `EnrollmentsController.php`, linhas 65–92 (`destroy`): 404 se a turma não existir ou se o aluno não estiver nela (`hasStudent`), senão `unenroll()`.
5. **Model** `TrainingClass.php`, linhas 140–145 (`unenroll`): `Enrollment::findBy([...])->destroy()` → `DELETE FROM class_enrollments WHERE id = ?`. **Só a pivô**.

**Registro com dependências** (Seção 1.4): `TrainingClass::destroy()`, linhas 201–216, sobrescreve o `destroy()` do Model. Se `studentsCount() > 0`, registra o erro e **não executa o DELETE**. O controller (`TrainingClassesController::destroy`, linhas 82–97) mostra esse erro no flash.

#### 3.1.4 Funcionalidade em Ajax

📁 `public/assets/js/enrollments.js`:
- Linhas 20–51, `requestJson()`: `new Promise` + `XMLHttpRequest`, `xhr.open(method, url, true)` (assíncrono), cabeçalhos `Accept: application/json` e `X-Requested-With`, log de cada `readyState`, `JSON.parse` no estado 4, `resolve`/`reject`.
- Linhas 64–81, `showFeedback()`: cria o alerta Bootstrap na `#ajax_feedback`, que tem `aria-live` para leitores de tela.
- Linhas 88–103: `updateCounter()` e `updateSelect()` usam o que o **servidor** devolveu (contagem e alunos disponíveis), então a tela nunca fica diferente do banco.
- Linhas 151–154, `setLoading()`: desabilita o botão durante a requisição, o que evita o duplo clique (e o índice único é a garantia final).

📁 Lado do servidor: `core/Http/Request.php`, linha 89 (`acceptJson`) e `app/Middleware/Authenticate.php`, linhas 15–20. Se a sessão expirar, uma chamada Ajax recebe **401 em JSON** em vez de um redirect para a tela de login.

### 3.2 Explicação do Funcionamento do Framework

#### 3.2.1 BelongsToMany — `core/Database/ActiveRecord/BelongsToMany.php`

🎬 Abra o arquivo inteiro (83 linhas).

🗣️ **FALA**
> "O `BelongsToMany` é a classe do framework que representa um N:N. O construtor, nas linhas 10–16, recebe cinco coisas: o **model de origem**, que é a turma, a **classe relacionada** (`User`), o **nome da tabela pivô**, a **FK que aponta para a origem** (`training_class_id`) e a **FK que aponta para o destino** (`user_id`).
>
> O método **`get()`**, nas linhas 22–57, monta dinamicamente um SELECT:
> - **colunas**: `users.id` mais todas as `User::columns()`, sempre prefixadas com o nome da tabela, nas linhas 27–31;
> - **FROM** as três tabelas: `training_classes, users, class_enrollments`;
> - **WHERE** com as duas condições de junção, `users.id = class_enrollments.user_id` e `training_classes.id = class_enrollments.training_class_id`, mais o filtro `training_classes.id = :id`;
> - executa com **prepared statement**, porque o `:id` é parâmetro e não concatenação, e transforma cada linha num objeto `User`, nas linhas 51–54.
>
> O **`count()`**, nas linhas 59–82, usa o mesmo FROM/WHERE com `COUNT(...)`, contando direto no banco.
>
> Quem cria o `BelongsToMany` é o método `BelongsToMany(...)` do `Model`, na linha 437 de `Model.php`. E é o **`__get` do Model**, nas linhas 46–73, que permite escrever `$turma->students` sem parênteses: ele vê que existe um método `students()` cujo **tipo de retorno** é `BelongsToMany` (a lista `$allowedTypes`, na linha 61) e chama `->get()` automaticamente.
>
> Para o lado inverso, `$aluno->trainingClasses`, a classe é a mesma, só com as FKs trocadas. É o padrão **Association Table Mapping** do Fowler."

**SQL gerado** para `$turma->students` (turma 1), bom para mostrar no MySQL:
```sql
SELECT users.id, users.name, users.email, ... , users.password_reset_expires_at
FROM training_classes, users, class_enrollments
WHERE users.id = class_enrollments.user_id AND
      training_classes.id = class_enrollments.training_class_id AND
      training_classes.id = 1;
```

📚 **Referência bibliográfica**
- **Livro:** *Padrões de Arquitetura de Aplicações Corporativas*. Autor: Martin Fowler. Porto Alegre: Bookman, 2006.
  - **Capítulo 10 – "Padrões de arquitetura de fontes de dados"**: padrão **Active Record** (base do `Model`).
  - **Capítulo 12 – "Padrões estruturais objeto-relacionais"**: padrão **Association Table Mapping** (Mapeamento de Tabela Associativa), que é o que o `BelongsToMany` implementa.

#### 3.2.2 HasMany/BelongsTo na pivô e renderJson

- `TrainingClass::enrollments()` (**HasMany**, linha 102) e `Enrollment::trainingClass()` / `user()` (**BelongsTo**, linhas 73–81 de `Enrollment.php`) mostram que a pivô também pode ser vista como **dois 1:N**.
- `Controller::renderJson()` (`core/Http/Controllers/Controller.php`, linhas 44–55) faz `extract($data)`, inclui a view `*.json.php`, que preenche `$json`, e executa `echo json_encode($json)` com o cabeçalho `Content-Type: application/json`. *(Nesta entrega corrigimos um erro de digitação no cabeçalho: `chartset` → `charset`.)*

### 3.3 Explicação do Banco de Dados

#### 3.3.1 Explicar o banco para esta funcionalidade

📁 `database/schema.sql`, linhas 68–100.

🗣️ **FALA**
> "Criamos duas tabelas:
> - **`training_classes`**: `id`, `name` (UNIQUE), `level`, `schedule`, `capacity` e `created_at`.
> - **`class_enrollments`**, a **pivô**: `id`, `training_class_id`, `user_id` e `created_at`. Ela tem:
>   - **FK `training_class_id` → `training_classes.id` com `ON DELETE RESTRICT`**, que impede excluir uma turma com alunos;
>   - **FK `user_id` → `users.id` com `ON DELETE CASCADE`**, que remove as matrículas quando o atleta é excluído;
>   - **`UNIQUE (training_class_id, user_id)`**, que impede matrícula duplicada;
>   - **índice em `user_id`**, que deixa rápida a consulta 'turmas do aluno'.
>
> Usamos um `id` próprio na pivô, em vez de chave primária composta, para seguir o padrão do framework: o `Model::destroy()` apaga por `id`. A unicidade do par fica garantida pelo índice único. O `created_at` registra **quando** o aluno foi matriculado, ou seja, a pivô também pode guardar dados do próprio vínculo."

🎬 **Demonstração no MySQL** (`./run db:console`):
```sql
-- Estrutura da pivô (FKs, ações referenciais e índices)
SHOW CREATE TABLE class_enrollments\G

-- A relação NxN em SQL: alunos de cada turma
SELECT t.name AS turma, u.name AS aluno, e.created_at AS matriculado_em
FROM class_enrollments e
JOIN training_classes t ON t.id = e.training_class_id
JOIN users u ON u.id = e.user_id
ORDER BY t.name, u.name;

-- Lado inverso: quantas turmas cada aluno faz
SELECT u.name, COUNT(e.id) AS turmas
FROM users u LEFT JOIN class_enrollments e ON e.user_id = u.id
WHERE u.role = 'student'
GROUP BY u.id, u.name;

-- Faça uma matrícula pela tela (Ajax) e rode de novo o primeiro SELECT: a linha nova aparece.

-- Índice único (Seção 2.2)
INSERT INTO class_enrollments (training_class_id, user_id) VALUES (1, 2);   -- ERROR 1062

-- RESTRICT (Seção 1.4)
DELETE FROM training_classes WHERE id = 1;                                  -- ERROR 1451

-- CASCADE: excluir um atleta remove as matrículas dele (rode só no fim, apaga o Carlos!)
SELECT COUNT(*) FROM class_enrollments WHERE user_id = 5;   -- 2
DELETE FROM users WHERE id = 5;
SELECT COUNT(*) FROM class_enrollments WHERE user_id = 5;   -- 0

-- Plano de execução: o índice único é usado na busca por turma
EXPLAIN SELECT user_id FROM class_enrollments WHERE training_class_id = 1;
```

> Depois do teste de CASCADE, rode `./run db:reset && ./run db:populate` para voltar ao estado inicial.

---

## 4. Testes Automatizados — Peso 2,5

🎬 **AÇÃO** (rode antes e mostre a saída, ou rode ao vivo se houver tempo):
```bash
./run test tests/Unit          # unitários (PHPUnit, --testdox)
./run test tests/Integration   # controllers + acesso
./run test:browser             # aceitação (Codeception + Selenium)
./run phpcs && ./run phpstan   # qualidade
```

Resultado esperado: **Unit 174 OK · Integration 83 OK · Acceptance 32 OK · PHPCS sem erros · PHPStan "No errors"**.

### 4.1 Testes de Aceitação (NxN) — Codeception + Selenium

📁 **`tests/Acceptance/Admin/TrainingClassesCest.php`**. Antes de cada cenário, o `BaseAcceptanceCest` recria o banco `arena_test` e roda o Seeder, então os dados da Seção 0.2 sempre estão lá.

| Método (linha) | Rubrica | O que faz |
|---|---|---|
| `registroDaRelacao` (50) | 1.1 Registro | Turma Kids vazia → seleciona Beatriz → clica Matricular → `waitForText` da mensagem na `#ajax_feedback` → vê a linha `#enrolled_student_4`, o contador `1` e que a Beatriz **saiu do select** → `reloadPage()` e a matrícula **continua lá** (persistiu no banco) |
| `visualizacaoDaRelacao` (73) | 1.2 Visualização | Admin vê `3/8` e os 3 alunos da turma 1; na ficha do atleta #2 vê as 2 turmas; faz logout; o **aluno** vê "Minhas Turmas" com `2 turma(s)` e **não** vê a turma Avançado |
| `remocaoDaRelacao` (103) | 1.3 Remoção | Remove o aluno #2 da turma 1, aceita o `confirm`, vê a mensagem, a linha some, o contador cai e o aluno volta ao select; recarrega e **ele continua na turma 2** |
| `funcionalidadeEmAjax` (125) | 1.4 Ajax | Marca a página com `window.__semRecarregar = true` (`executeJS`); matricula, remove e provoca um erro; depois de cada ação confere que **a variável continua existindo**, o que só é possível se a página **não recarregou** |
| `turmaLotadaNaoAceitaMatricula` (154) | Regra de negócio | Enche a turma Avançado (4 vagas), vê o selo LOTADA e a mensagem "a turma está lotada!" |
| `exclusaoDeTurmaComAlunosEhBloqueada` (174) | Dependências | Tenta excluir a turma 1 e vê o bloqueio; exclui a turma Kids (vazia) com sucesso |
| `alunoNaoAcessaGestaoDeTurmas` (192) | Autorização | O aluno abre `/admin/classes/1` e é redirecionado para o dashboard com "Acesso não autorizado" |

🗣️ **FALA**
> "Para provar que é Ajax criamos uma variável JavaScript na página antes da ação. Se a página recarregasse, a variável sumiria. O teste confere que ela continua lá depois de matricular e remover. E para as respostas assíncronas usamos `waitForText` e `waitForElement`, que esperam o DOM ser atualizado."

### 4.2 Testes Unitários

#### 4.2.1 Métodos dos models

📁 **`tests/Unit/Models/TrainingClassTest.php`** (18 testes):

| Teste (linha) | Cobre |
|---|---|
| `test_should_create_class_with_defaults` (51) · `test_validates_required_fields` (61) · `test_validates_level_capacity_and_unique_name` (70) | `validates()`, padrões do construtor e `levelLabel()` |
| `test_students_returns_belongs_to_many_relation` (87) | `students()` e `trainingClasses()` retornam `BelongsToMany`; `enrollments()` retorna `HasMany` |
| `test_enroll_registers_the_relation_in_both_directions` (94) | `enroll()`, `students`, `trainingClasses`, `hasStudent()`, `studentsCount()` |
| `test_student_can_be_in_many_classes` (117) | o "N" do lado do aluno |
| `test_enroll_same_student_twice_is_rejected` (129) | unicidade do par na aplicação |
| `test_enroll_is_rejected_when_class_is_full` (139) · `test_only_students_can_be_enrolled` (152) | `isFull()` e regra de role |
| `test_unenroll_removes_only_the_relation` (161) | `unenroll()` apaga só a pivô |
| `test_available_students_excludes_enrolled_and_managers` (173) · `test_all_ordered_sorts_by_name` (184) | `availableStudents()` e `allOrdered()` |
| `test_destroy_class_with_students_is_blocked` (196) · `test_destroy_class_without_students` (205) | `destroy()` com e sem dependências |
| `test_database_unique_index_rejects_duplicate_enrollment` (213) | **o banco** rejeita duplicata (`Duplicate entry`) mesmo ignorando o model |
| `test_database_restrict_blocks_deleting_class_with_students` (227) | **o banco** rejeita o DELETE (`foreign key constraint fails`) |
| `test_database_cascade_removes_enrollments_when_user_is_deleted` (238) | CASCADE em `user_id` |

📁 **`tests/Unit/Models/EnrollmentTest.php`** (5 testes): criação com `created_at`, campos obrigatórios, **as duas FKs precisam existir**, `trainingClass()`/`user()` (BelongsTo) e `enrollments` (HasMany).

#### 4.2.2 Métodos de outras classes

📁 **`tests/Unit/Core/Database/BelongsToManyTest.php`** (4 testes), testando a classe **do framework**:

| Teste (linha) | Cobre |
|---|---|
| `test_get_returns_empty_array_without_relations` (47) | `get()` → `[]` e `count()` → `0` |
| `test_get_returns_only_related_models_through_pivot` (55) | `get()` traz só os alunos daquela turma, como objetos `User` com atributos preenchidos |
| `test_inverse_direction_uses_swapped_keys` (70) | a mesma classe com as FKs invertidas traz as turmas do aluno |
| `test_magic_property_calls_get` (84) | `Model::__get` chama `get()` para métodos que retornam `BelongsToMany` |

📁 Testes de controller (integração), **`tests/Integration/Controllers/TrainingClassesControllersTest.php`** (15 testes): CRUD de turmas, erros embaixo do campo, **respostas JSON** do Ajax (`test_enroll_via_ajax_returns_json`, linha 156; duplicado, linha 175; sem aluno, linha 191; turma inexistente, linha 204; remoção, linha 217; aluno fora da turma, linha 235), **fallback sem JavaScript** com redirect + flash (linha 250) e as visões do aluno (linhas 264 e 276).

### 4.3 Testes de Acesso (teste de controller)

#### 4.3.1 Rotas autenticadas não devem ser acessíveis por usuários não autenticados

📁 **`tests/Integration/Access/AuthenticatedRoutesAccessTest.php`**. Faz requisições HTTP **reais** (Guzzle) ao Nginx, sem cookie de sessão:

| Teste (linha) | Rotas | Esperado |
|---|---|---|
| `test_should_redirect_unauthenticated_user_from_classes` (112) | `GET /admin/classes`, `/admin/classes/new`, `/admin/classes/1` | **302 → /login** |
| `test_should_block_unauthenticated_class_creation_and_removal` (119) | `POST /admin/classes`, `DELETE /admin/classes/4` | **302 → /login** |
| `test_should_block_unauthenticated_enrollment_registration_and_removal` (129) | `POST /admin/classes/1/enrollments`, `DELETE /admin/classes/1/enrollments/2` | **302 → /login** |
| `test_should_return_401_json_for_unauthenticated_ajax_enrollment` (137) | as mesmas rotas de matrícula com `Accept: application/json` | **401** com `Content-Type: application/json` e `{"code": 401}` |

🗣️ **FALA**
> "Testamos as rotas novas de dois jeitos. Como navegador, sem login, recebemos o redirect para o login. Como chamada Ajax, recebemos 401 em JSON, que é o que um cliente JavaScript sabe tratar. Além disso, o teste de aceitação `alunoNaoAcessaGestaoDeTurmas` mostra que um aluno **logado** também não acessa, porque o middleware `admin` bloqueia."

---

## Encerramento (≈ 30 s)

🗣️ **FALA**
> "Resumindo: implementamos a relação **N para N entre turmas e alunos** com uma tabela pivô protegida por **índice único composto** e por **chaves estrangeiras com RESTRICT e CASCADE**. Matrícula e remoção funcionam **via Ajax**, com respostas JSON e códigos HTTP corretos, e continuam funcionando sem JavaScript. Usamos o **`BelongsToMany`** do framework nos dois sentidos da relação. E cobrimos tudo com testes de aceitação no Selenium, testes unitários dos models e do próprio `BelongsToMany`, testes de controller e testes de acesso. Obrigado!"

---

## Apêndice A — Inventário de arquivos criados e alterados

### A.1 Arquivos CRIADOS

| Arquivo | Para quê |
|---|---|
| `app/Models/TrainingClass.php` | Model da turma: validações, `students()` (BelongsToMany), `enrollments()` (HasMany), `enroll()`, `unenroll()`, `availableStudents()`, `isFull()`, `destroy()` com bloqueio de dependências |
| `app/Models/Enrollment.php` | Model da tabela pivô `class_enrollments`: validações (FKs existem, par único, só alunos, lotação) e `trainingClass()`/`user()` (BelongsTo) |
| `app/Controllers/Admin/TrainingClassesController.php` | `index`, `new`, `create`, `show`, `destroy` das turmas |
| `app/Controllers/Admin/EnrollmentsController.php` | `create`/`destroy` de matrícula; responde JSON (Ajax) ou redirect + flash |
| `app/views/admin/classes/index.phtml` | Lista de turmas com contador `alunos/vagas` e botão Excluir |
| `app/views/admin/classes/new.phtml` | Formulário de nova turma com erros embaixo dos campos |
| `app/views/admin/classes/show.phtml` | Alunos da turma + matrícula/remoção (formulários interceptados pelo Ajax) |
| `app/views/admin/enrollments/result.json.php` | Estrutura do JSON devolvido ao Ajax |
| `public/assets/js/enrollments.js` | Ajax: XMLHttpRequest + Promise + async/await, readyState no console, atualização do DOM |
| `tests/Unit/Models/TrainingClassTest.php` | 18 testes do model da turma e da integridade no banco |
| `tests/Unit/Models/EnrollmentTest.php` | 5 testes do model da pivô |
| `tests/Unit/Core/Database/BelongsToManyTest.php` | 4 testes da classe `BelongsToMany` do framework |
| `tests/Integration/Controllers/TrainingClassesControllersTest.php` | 15 testes de controller (HTML e JSON) |
| `tests/Acceptance/Admin/TrainingClassesCest.php` | 7 testes de aceitação (registro, visualização, remoção, Ajax, lotação, dependências, autorização) |
| `roteiro.md` | Este roteiro |

### A.2 Arquivos ALTERADOS

| Arquivo | Alteração |
|---|---|
| `database/schema.sql` | Tabelas `training_classes` e `class_enrollments` (FKs RESTRICT/CASCADE, UNIQUE composto, índice em `user_id`) e os respectivos `DROP TABLE` |
| `database/Populate/Seeder.php` | 4 turmas e 6 matrículas de exemplo |
| `database/Populate/populate.php` | Mostra a rota `/admin/classes` no fim do populate |
| `app/Models/User.php` | `trainingClasses()` (BelongsToMany, lado inverso) |
| `app/Controllers/DashboardController.php` | Passa `$trainingClasses` para o card "Minhas Turmas" |
| `app/Controllers/Admin/UsersController.php` | `show` passa as turmas do atleta |
| `app/views/dashboard/index.phtml` | Card "Minhas Turmas" |
| `app/views/admin/users/show.phtml` | Card "Turmas do Atleta" |
| `app/views/admin/users/index.phtml` | Botão "Turmas" no Painel Gerencial |
| `app/views/layouts/application.phtml` | Link "Turmas" na navbar (admin) |
| `config/routes.php` | 7 rotas novas (turmas + matrículas) no grupo `auth` + `admin` |
| `core/Http/Controllers/Controller.php` | `renderJson`: cabeçalho corrigido de `chartset` para `charset` |
| `tests/Integration/Access/AuthenticatedRoutesAccessTest.php` | 4 testes de acesso das rotas novas (302 e 401 JSON) |
| `README.md` | Seção 1.4 (Turmas e Matrículas), tabelas novas e perfis de acesso atualizados |

---

## Apêndice B — Checklist da rubrica × seção do roteiro

| Item da rubrica | Peso | Seção |
|---|---|---|
| **Demonstração prática (NxN)** | 2 | 1 |
| 1. Registro da relação | | 1.1 |
| 2. Remoção da relação | | 1.3 |
| 3. Visualização da relação | | 1.2 |
| 4. Tentativa de remoção de registros com dependências | | 1.4 |
| 5. Ajax no DevTools | | 1.5 |
| **Explicação de conceitos** (com referência de livro) | 1,5 | 2 |
| 1. Definição conceitual de NxN | | 2.1 |
| 2. index unique: true — usa? por quê? | | 2.2 |
| 3. Definição de Ajax | | 2.3 |
| 4. Síncrona × assíncrona | | 2.4 |
| 5. Definição e estrutura do JSON | | 2.5 |
| 6. Estados da requisição (readyState) | | 2.6 |
| 7. Async, promise e await | | 2.7 |
| **Explicação do código** | 2,5 | 3 |
| 1.1–1.4 Fluxo: registro, visualização, remoção, Ajax | | 3.1.1–3.1.4 |
| 2.1 BelongsToMany (`core/Database/ActiveRecord/BelongsToMany.php`) | | 3.2.1 |
| 3.1 Banco de dados da funcionalidade | | 3.3 |
| **Testes** | 2,5 | 4 |
| 1.1–1.4 Aceitação: registro, visualização, remoção, Ajax | | 4.1 |
| 2.1 Unitários: métodos dos models | | 4.2.1 |
| 2.2 Unitários: métodos de outras classes | | 4.2.2 |
| 3.1 Acesso: rotas autenticadas × não autenticados | | 4.3.1 |

---

## Apêndice C — Perguntas prováveis do professor (respostas rápidas)

- **"Por que a pivô tem `id` e não uma chave primária composta?"** Porque o `Model` do framework identifica e apaga registros por `id`. A unicidade do par está garantida pelo `UNIQUE (training_class_id, user_id)`, que tem o mesmo efeito prático de uma PK composta.
- **"A validação de duplicidade no model não basta?"** Não. Duas requisições simultâneas podem passar pelo SELECT de verificação antes de qualquer INSERT. O índice único é atômico. O model existe para dar uma **mensagem amigável**, e o banco é a **garantia**.
- **"Por que RESTRICT na turma e CASCADE no aluno?"** Turma com alunos é um dado em uso: apagá-la por engano perderia todas as matrículas, então exigimos uma ação explícita. Já um atleta excluído do sistema não tem por que manter matrículas, e é a mesma regra das faturas.
- **"Por que XMLHttpRequest e não fetch?"** Os dois são Ajax. Usamos XHR para poder mostrar os `readyState` (o `fetch` não expõe esses estados) e embrulhamos numa Promise para usar `async/await`, que é a forma moderna.
- **"O que acontece se o JavaScript estiver desativado?"** Os formulários são HTML normais (`POST` e `_method=DELETE`). O controller detecta que não é JSON e responde com redirect + flash message. Isso está coberto pelo teste `test_enroll_without_ajax_redirects_with_flash`.
- **"E se a sessão expirar no meio do uso?"** O middleware `auth` vê o `Accept: application/json` e responde **401 em JSON**. O JS mostra a mensagem de erro em vez de tentar interpretar a página de login.
- **"Onde está o XSS?"** Em nenhum lugar: no PHP usamos `htmlspecialchars`, e no JS as linhas são criadas com `textContent`, nunca com `innerHTML` de dados do servidor.

---

## Referências bibliográficas

Todas as referências usadas no roteiro, reunidas num só lugar. Elas aparecem em três formatos:
- **Parte 1:** por conceito, na ordem da rubrica, com livro e capítulo. É o que deve ser mostrado ou citado no vídeo.
- **Parte 2:** lista completa dos livros em formato ABNT, em ordem alfabética.
- **Parte 3:** fontes complementares que não são livros (artigo e normas técnicas).

> ⚠️ Confiram a numeração de capítulos e seções na edição disponível na biblioteca (ou no Minha Biblioteca/Pearson da UTFPR) antes de gravar. Edições diferentes podem mudar a numeração.

### Parte 1 — Referência de livro de cada conceito (rubrica 1.5)

| # | Conceito da rubrica | Livro (autor, edição, ano) | Capítulo |
|---|---|---|---|
| 1 | Definição conceitual de NxN | *Sistemas de Banco de Dados* — Elmasri e Navathe, 7. ed., Pearson, 2018 | **Cap. 3** "Modelagem de dados usando o modelo Entidade-Relacionamento (ER)", seção 3.4 (razão de cardinalidade M:N) · **Cap. 9** "Projeto de banco de dados relacional por mapeamento ER e EER para relacional", seção 9.1, passo 5 (mapeamento de relacionamentos binários M:N) |
| 1 | *(complementar)* | *Projeto de Banco de Dados* — Heuser, 6. ed., Bookman, 2009 | **Cap. 5** "Transformações entre modelos" (relacionamento n:n vira tabela) |
| 2 | `index unique: true` | *Sistemas de Banco de Dados* — Elmasri e Navathe, 7. ed., Pearson, 2018 | **Cap. 5** "O modelo de dados relacional e as restrições em bancos de dados relacionais", seção 5.2 (restrições de chave) · **Cap. 17** "Estruturas de indexação para arquivos e projeto físico de banco de dados", seção 17.4 (índices em chaves múltiplas) |
| 2 | *(complementar)* | *Sistema de Banco de Dados* — Silberschatz, Korth e Sudarshan, 7. ed., GEN LTC, 2020 | **Cap. 14** "Indexação" |
| 3 | Definição de Ajax | *JavaScript: O Guia Definitivo* — Flanagan, 6. ed., Bookman, 2013 | **Cap. 18** "HTTP com scripts" (introdução e seção 18.1 "Usando XMLHttpRequest") |
| 4 | Requisição síncrona × assíncrona | *JavaScript: O Guia Definitivo* — Flanagan, 6. ed., Bookman, 2013 | **Cap. 18** "HTTP com scripts", seção 18.1.2, subseção "Respostas síncronas" |
| 4 | *(complementar)* | *JavaScript: O Guia Definitivo* — Flanagan, 7. ed., Bookman, 2022 | **Cap. 13** "JavaScript assíncrono", seção 13.1 |
| 5 | Definição e estrutura do JSON | *O Melhor do JavaScript* — Crockford, Alta Books, 2008 | **Apêndice E** "JSON" |
| 5 | *(complementar)* | *JavaScript: O Guia Definitivo* — Flanagan, 7. ed., Bookman, 2022 | **Cap. 11** "A biblioteca-padrão de JavaScript", seção 11.6 "Serialização e análise de JSON" |
| 6 | Estados da requisição (readyState) | *JavaScript: O Guia Definitivo* — Flanagan, 6. ed., Bookman, 2013 | **Cap. 18** "HTTP com scripts", seção 18.1.2 "Recuperando a resposta" (tabela 18-1, valores de `readyState`) |
| 7 | Async, Promise e await | *JavaScript: O Guia Definitivo* — Flanagan, 7. ed., Bookman, 2022 | **Cap. 13** "JavaScript assíncrono", seções 13.2 "Promessas" e 13.3 "async e await" |
| — | Framework: BelongsToMany / Active Record (Seção 3.2) | *Padrões de Arquitetura de Aplicações Corporativas* — Fowler, Bookman, 2006 | **Cap. 10** "Padrões de arquitetura de fontes de dados" (Active Record) · **Cap. 12** "Padrões estruturais objeto-relacionais" (Association Table Mapping) |

### Parte 2 — Livros (ABNT, ordem alfabética)

1. CROCKFORD, Douglas. **O melhor do JavaScript**. Rio de Janeiro: Alta Books, 2008.
   - Apêndice E – JSON. → *Conceito 5.*

2. ELMASRI, Ramez; NAVATHE, Shamkant B. **Sistemas de banco de dados**. 7. ed. São Paulo: Pearson Education do Brasil, 2018.
   - Cap. 3 – Modelagem de dados usando o modelo Entidade-Relacionamento (ER). → *Conceito 1.*
   - Cap. 5 – O modelo de dados relacional e as restrições em bancos de dados relacionais. → *Conceito 2.*
   - Cap. 9 – Projeto de banco de dados relacional por mapeamento ER e EER para relacional. → *Conceito 1.*
   - Cap. 17 – Estruturas de indexação para arquivos e projeto físico de banco de dados. → *Conceito 2.*

3. FLANAGAN, David. **JavaScript: o guia definitivo**. 6. ed. Porto Alegre: Bookman, 2013.
   - Cap. 18 – HTTP com scripts. → *Conceitos 3, 4 e 6.*

4. FLANAGAN, David. **JavaScript: o guia definitivo**. 7. ed. Porto Alegre: Bookman, 2022.
   - Cap. 11 – A biblioteca-padrão de JavaScript (seção 11.6). → *Conceito 5 (complementar).*
   - Cap. 13 – JavaScript assíncrono. → *Conceito 7 e conceito 4 (complementar).*

5. FOWLER, Martin. **Padrões de arquitetura de aplicações corporativas**. Porto Alegre: Bookman, 2006.
   - Cap. 10 – Padrões de arquitetura de fontes de dados. Cap. 12 – Padrões estruturais objeto-relacionais. → *Seção 3.2, Framework.*

6. HEUSER, Carlos Alberto. **Projeto de banco de dados**. 6. ed. Porto Alegre: Bookman, 2009. (Série Livros Didáticos Informática UFRGS, v. 4).
   - Cap. 5 – Transformações entre modelos. → *Conceito 1 (complementar).*

7. SILBERSCHATZ, Abraham; KORTH, Henry F.; SUDARSHAN, S. **Sistema de banco de dados**. 7. ed. Rio de Janeiro: GEN LTC, 2020.
   - Cap. 14 – Indexação. → *Conceito 2 (complementar).*

### Parte 3 — Fontes complementares (não são livros)

8. GARRETT, Jesse James. **Ajax: a new approach to web applications**. Adaptive Path, 18 fev. 2005. Artigo que criou o termo Ajax. → *Conceito 3.*
9. BRAY, T. (ed.). **RFC 8259: The JavaScript Object Notation (JSON) Data Interchange Format**. IETF, 2017. Disponível em: https://www.rfc-editor.org/rfc/rfc8259. → *Conceito 5.*
10. WHATWG. **XMLHttpRequest Living Standard**. Disponível em: https://xhr.spec.whatwg.org/. → *Conceito 6.*
