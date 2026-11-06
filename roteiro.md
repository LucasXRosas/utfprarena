# Roteiro de Gravação — Entrega "Imagens/Arquivos (Relação 1xN)"

**Sistema:** UTFPR Arena Beach Tennis
**Branch:** `feature/imagens-arquivos`
**Funcionalidades entregues:**

1. **Galeria de fotos das quadras (relação 1xN):** uma quadra (`courts`) possui N imagens (`court_images`). O admin envia, vê e remove fotos; o aluno vê a galeria de cada quadra.
2. **Foto de perfil (avatar):** qualquer usuário logado envia, vê e remove a própria foto na aba **"Meu Perfil"** (`ProfileAvatar`, exigido no item 3.3 da rubrica).

> **Como usar este roteiro:** cada seção segue a ordem da rubrica. Os blocos **🎬 AÇÃO** dizem o que fazer na tela, os blocos **🗣️ FALA** trazem o texto sugerido para narrar e os blocos **📁 ARQUIVO** dizem qual arquivo abrir no VS Code e em que linha. No fim (Apêndice A) há o **inventário completo de todos os arquivos criados e alterados**, e no Apêndice B um checklist da rubrica.

---

## 0. Preparação (antes de apertar o REC)

### 0.1 Ambiente

```bash
git checkout feature/imagens-arquivos
cp .env.example .env            # se ainda não existir
./run composer install
./run up -d
./run db:reset                  # cria users, courts, invoices e court_images
./run db:populate               # popula e limpa uploads antigos
sudo chown -R www-data:www-data public/assets/uploads   # só no Linux
```

Abra `http://localhost:8000`.

### 0.2 Arquivos de exemplo para a demonstração

Deixe uma pasta `~/demo-upload` com:

| Arquivo | Para quê | Como obter |
|---|---|---|
| `quadra1.jpg`, `quadra2.jpg`, `quadra3.png` | uploads válidos | fotos quaisquer com menos de 2MB (ou copie `tests/Support/Data/quadra_foto.jpg`) |
| `perfil.jpg` | avatar | qualquer foto sua com menos de 2MB |
| `grande.jpg` | validação de tamanho | `php -r "file_put_contents('grande.jpg', str_repeat('0', 3*1024*1024));"` (3MB) |
| `documento.pdf` ou `animacao.gif` | formato não aceito | qualquer PDF/GIF |
| `malicioso.php.jpg` | dupla extensão | copie `tests/Support/Data/malicioso.php.jpg` |
| `script_disfarcado.jpg` | script com extensão de imagem | copie `tests/Support/Data/script_disfarcado.jpg` |

### 0.3 Janelas abertas

1. **Navegador** em `http://localhost:8000` (de preferência uma janela anônima para o aluno).
2. **VS Code** com o projeto aberto e o explorador mostrando `public/assets/uploads/`.
3. **Terminal 1** na raiz do projeto, para `ls` no filesystem.
4. **Terminal 2** com o MySQL aberto: `./run db:console`.

### 0.4 Credenciais

| Perfil | E-mail | Senha |
|---|---|---|
| Admin | `admin@arena.com` | `admin123` |
| Aluno | `aluno@arena.com` | `aluno123` |

### 0.5 Divisão sugerida entre os integrantes

| Bloco | Tempo aprox. | Quem |
|---|---|---|
| Abertura + Demonstração prática (Seções 1–4) | 8–10 min | Integrante A |
| Conceitos (Seção 5) | 4–5 min | Integrante B |
| Código e framework (Seção 6) | 10–12 min | Integrante C |
| Banco de dados (Seção 6.4) + Testes (Seção 7) | 6–8 min | Integrante D |

---

## Abertura (≈ 40 s)

🗣️ **FALA**
> "Olá! Somos o grupo do **UTFPR Arena**, um sistema de gerenciamento de arenas de Beach Tennis. Nesta entrega implementamos **upload de imagens com relação 1 para N**. Fizemos duas funcionalidades. A principal é a **galeria de fotos das quadras**: cada quadra tem N fotos, que o administrador envia e remove e que os alunos podem ver. A segunda é a **foto de perfil** de cada usuário, na nova aba *Meu Perfil*. Vamos mostrar o sistema funcionando, o arquivo aparecendo e sumindo no filesystem, as validações de segurança, a integridade do banco, os conceitos, o código e, por fim, os testes automatizados."

---

## 1. Demonstração Prática no Sistema (Relação 1xN) — Peso 1

### 1.0 Contexto da tela

🎬 **AÇÃO**
1. Faça login como **admin** (`admin@arena.com` / `admin123`).
2. Na navbar, mostre o novo link **"Quadras e Galerias"** (e o botão de mesmo nome no Painel Gerencial).
3. Clique nele: aparece `/admin/courts`, com a tabela de quadras, a coluna **Capa** e a coluna **"Fotos na Galeria"**, que mostra `0 foto(s)`.

🗣️ **FALA**
> "Esta é a nova tela de quadras. A **quadra** é a entidade principal, o lado **1**. Cada quadra tem uma galeria com várias imagens, que é o lado **N**. A coluna da direita conta quantas fotos cada galeria tem."

### 1.1 Upload de arquivos (+ arquivo no filesystem)

🎬 **AÇÃO**
1. **Antes do upload**, no Terminal 1:
   ```bash
   ls -la public/assets/uploads/
   ls -la public/assets/uploads/courts/1   # "No such file or directory": ainda não existe
   ```
2. No navegador, clique em **Galeria** na "Quadra Central (Areia Premium)" (`/admin/courts/1`).
3. Mostre o formulário: campo de arquivo, legenda e o texto *"Formatos aceitos: JPG, JPEG, PNG, WEBP • Tamanho máximo: 2MB"*.
4. Selecione `quadra1.jpg`, escreva a legenda "Vista lateral" e clique em **Enviar**.
5. Mostre a mensagem verde: **"Imagem quadra1.jpg enviada com sucesso! Arquivo salvo em: /assets/uploads/courts/1/court_xxxx.jpg"**.
6. Envie também `quadra2.jpg` e `quadra3.png` para mostrar o lado **N** (3 fotos na mesma quadra).
7. **Depois do upload**, no Terminal 1:
   ```bash
   ls -la public/assets/uploads/courts/1
   ```
   Mostre os 3 arquivos `court_<hash>.jpg/.png`. Mostre também no explorador do VS Code.

🗣️ **FALA**
> "O arquivo foi gravado em `public/assets/uploads/courts/1/`. Uma pasta por quadra deixa clara a relação 1xN também no disco. Repare que o nome **não é** `quadra1.jpg`: o sistema gera um nome aleatório com 32 caracteres hexadecimais, para evitar colisão de nomes, sobrescrita e adivinhação de URL. O nome original fica guardado só como metadado no banco."

### 1.2 Apresentação dos arquivos

🎬 **AÇÃO**
1. Na galeria do admin, mostre os **cards**: imagem, legenda, nome original, MIME type (`image/jpeg`), tamanho (KB), o selo verde **"arquivo presente"** (o sistema confere que o arquivo existe no disco) e o caminho `public/assets/uploads/courts/1/...`.
2. Clique em uma imagem: ela abre em tamanho real em outra aba e a URL é servida diretamente pelo Nginx.
3. Volte a `/admin/courts`: a coluna **Capa** mostra a primeira foto e a contagem está em `3 foto(s)`.
4. Abra uma **janela anônima**, faça login como **aluno** (`aluno@arena.com`).
5. Na **Área do Aluno**, mostre que os cards de quadra agora têm **foto de capa** e o botão **"Ver galeria (3 fotos)"**.
6. Clique nele (`/courts/1`): é a galeria em modo **somente leitura**, sem botão de remover.

🗣️ **FALA**
> "A mesma galeria é exibida para o aluno sem as ações de gestão. O aluno só tem a rota de leitura `/courts/{id}`. As rotas de upload e remoção ficam no grupo `admin`."

### 1.3 Remoção de arquivo (+ arquivo removido do filesystem)

🎬 **AÇÃO**
1. Volte à janela do admin em `/admin/courts/1`.
2. Copie o nome do arquivo de um card (ex.: `court_ab12...jpg`).
3. Clique em **Remover Imagem** e confirme o `confirm()`.
4. Mostre a mensagem: **"Imagem quadra2.jpg removida com sucesso! Arquivo apagado do filesystem: /assets/uploads/courts/1/court_ab12...jpg"**.
5. Terminal 1:
   ```bash
   ls -la public/assets/uploads/courts/1     # o arquivo sumiu, restam 2
   ```
6. Terminal 2 (MySQL):
   ```sql
   SELECT id, court_id, file_name, original_name FROM court_images WHERE court_id = 1;
   ```
   O registro também sumiu.

🗣️ **FALA**
> "A remoção apaga as duas pontas: o **arquivo físico** e o **registro no banco**. Se só uma delas fosse apagada, ficaria um arquivo órfão no disco ou um registro apontando para um arquivo que não existe."

### 1.4 Validação do arquivo (visão rápida, detalhada na Seção 2)

🎬 **AÇÃO**
1. Em `/admin/courts/1`, tente enviar `documento.pdf` (ou `.gif`). Resultado: **"Erro ao enviar imagem: Formato não permitido. Envie apenas: JPG, JPEG, PNG, WEBP."**
2. Clique em **Enviar** sem escolher arquivo e, pelo DevTools, remova o atributo `required` do input. Resultado: **"Nenhum arquivo foi enviado."**

### 1.5 Bônus: foto de perfil (ProfileAvatar)

🎬 **AÇÃO**
1. Na janela do **aluno**, clique em **"Meu Perfil"** na navbar (`/profile`).
2. Mostre o avatar padrão e o texto "Você ainda não enviou uma foto. Exibindo avatar padrão."
3. Envie `perfil.jpg`. Resultado: **"Foto de perfil atualizada com sucesso! Arquivo salvo em: /assets/uploads/users/2/avatar_xxxx.jpg"**.
4. Mostre a foto em três lugares: no **perfil**, na **navbar** (miniatura redonda) e na **Área do Aluno** (credencial). Na janela do admin, abra `/admin/users/2` e mostre que a foto aparece lá também.
5. Mostre o quadro "Arquivo no filesystem / Banco (users.avatar_name)".
6. Terminal 1: `ls -la public/assets/uploads/users/2`.
7. Envie **outra** foto e rode o `ls` de novo: continua **1 arquivo só**, porque a foto antiga foi apagada.
8. Clique em **Remover Foto**, confirme e rode o `ls` mais uma vez: a pasta fica vazia e volta o avatar padrão.

🗣️ **FALA**
> "O avatar é uma relação 1 para 1: o usuário guarda o nome do arquivo na coluna `avatar_name`. Ao trocar a foto, o sistema apaga a anterior do disco, então não sobra lixo."

---

## 2. Validações Necessárias no Backend — Peso 1

🗣️ **FALA (introdução)**
> "Toda validação é feita **no backend**. O atributo `accept` do input e o `required` são só conveniência para o usuário, porque qualquer pessoa consegue removê-los no DevTools ou enviar a requisição pelo cURL. Quem decide é a classe `ImageValidator`."

### 2.1 Tamanho e formatos aceitos

🎬 **AÇÃO**
1. Envie `grande.jpg` (3MB). Resultado: **"Erro ao enviar imagem: O arquivo excede o tamanho máximo permitido de 2MB."**
2. Envie `documento.pdf`. Resultado: **"Formato não permitido. Envie apenas: JPG, JPEG, PNG, WEBP."**
3. (Opcional) Renomeie um `.png` para `.jpg` e envie. Resultado: **"A extensão .jpg não corresponde ao conteúdo do arquivo (image/png)."**

🗣️ **FALA**
> "O limite de 2MB é verificado pelo **tamanho real do arquivo no disco** (`filesize`), não pelo campo `size` que o navegador manda. Também tratamos os erros nativos do PHP `UPLOAD_ERR_INI_SIZE` e `UPLOAD_ERR_FORM_SIZE`. Os formatos aceitos são JPEG, PNG e WEBP, e a extensão tem que bater com o conteúdo."

### 2.2 Tratamento de upload malicioso

🎬 **AÇÃO**
1. Envie `malicioso.php.jpg`. Resultado: **"Arquivo malicioso: extensões executáveis (.php) não são permitidas."**
2. Envie `script_disfarcado.jpg`, que é um script PHP com extensão `.jpg`. Resultado: **"Arquivo malicioso ou inválido: o conteúdo real (text/x-php) não é uma imagem permitida."**
3. Mostre no Terminal 1 que **nada** foi gravado: `ls public/assets/uploads/courts/1`.
4. Repita o passo 1 na aba **Meu Perfil** e mostre que a proteção vale para os dois uploads.

🗣️ **FALA**
> "Tratamos três ataques clássicos. **Primeiro**, a dupla extensão: `foto.php.jpg` passaria num filtro ingênuo que olha só o final do nome, mas nós olhamos **todos os segmentos**. **Segundo**, o script disfarçado: o arquivo diz ser `.jpg`, e o navegador até manda `Content-Type: image/jpeg`, mas nós lemos os *magic bytes* do conteúdo com `finfo` e descobrimos que é `text/x-php`. **Terceiro**, o arquivo poliglota: uma imagem PNG válida com código PHP colado no fim. Esse passa pelo MIME, mas o validador procura assinaturas como `<?php` e `<script` e barra o arquivo. Esse caso está coberto nos testes unitários. Além disso, o arquivo é **renomeado** com a extensão derivada do MIME real, nunca do nome enviado, e o Nginx encaminha qualquer requisição para o `index.php`, então um arquivo dentro de `uploads/` nunca é executado como PHP."

📁 **ARQUIVO** — mostre rapidamente `app/Services/ImageValidator.php`, linhas 57–143 (os 8 passos numerados nos comentários).

---

## 3. Demonstração da Integridade das Relações — Peso 1

### 3.1 Excluir a entidade principal (cascata: banco + filesystem)

🎬 **AÇÃO**
1. Em `/admin/courts`, clique em **Nova Quadra**, cadastre "Quadra Demonstração Cascata" e você é levado direto à galeria dela.
2. Envie **2 ou 3 fotos**.
3. Anote o ID (ex.: `#4`) e mostre:
   ```bash
   ls -la public/assets/uploads/courts/4
   ```
   ```sql
   SELECT id, court_id, file_name FROM court_images WHERE court_id = 4;
   ```
4. Volte a `/admin/courts` e clique em **Excluir** na quadra #4. Mostre o aviso *"Excluir a quadra e TODAS as fotos da galeria?"* e confirme.
5. Mensagem: **"Quadra Quadra Demonstração Cascata removida com sucesso! 3 imagem(ns) apagada(s) do banco (ON DELETE CASCADE) e do filesystem."**
6. Prove:
   ```bash
   ls -la public/assets/uploads/courts/4      # No such file or directory
   ```
   ```sql
   SELECT * FROM court_images WHERE court_id = 4;   -- Empty set
   ```

🗣️ **FALA**
> "Quando a quadra é excluída, o **banco** remove automaticamente as linhas de `court_images` por causa do `ON DELETE CASCADE` na chave estrangeira. Mas o banco não sabe que existem arquivos no disco. Por isso o método `Court::destroy()` foi sobrescrito: primeiro apaga a quadra no banco e, se der certo, apaga a pasta `uploads/courts/{id}` inteira."

### 3.2 Registrar uma imagem para uma galeria inexistente

**(a) Diretamente no banco**, como a rubrica sugere. Terminal 2:

```sql
INSERT INTO court_images (court_id, file_name, original_name, mime_type, size)
VALUES (999, 'court_fantasma.jpg', 'fantasma.jpg', 'image/jpeg', 100);
```

Resultado esperado:
```
ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails
(`arena_development`.`court_images`, CONSTRAINT `fk_court_images_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ...)
```

🗣️ **FALA**
> "Mesmo ignorando a aplicação, o MySQL recusa o registro: **não existe quadra 999**, então a FK é violada (erro 1452)."

**(b) Pela aplicação**, com duas abas:
1. Na aba 1, abra a galeria de uma quadra recém-criada (ex.: `/admin/courts/5`) e escolha um arquivo, **sem enviar ainda**.
2. Na aba 2, exclua essa quadra em `/admin/courts`.
3. Volte à aba 1 e clique em **Enviar**. Resultado: **"Galeria inexistente: a quadra #5 não existe."**

Alternativa: no DevTools, mude o `action` do formulário para `/admin/courts/999/images` e envie.

🗣️ **FALA**
> "A aplicação também valida em três camadas: o controller busca a quadra antes de tudo, o serviço `CourtGallery::add()` confere se ela existe e o model `CourtImage` usa a validação `foreignKeyExists`. Se mesmo assim o INSERT falhar (por exemplo, numa condição de corrida), o serviço captura a `PDOException` e **apaga o arquivo já gravado**, para não deixar órfão."

---

## 4. (Transição) Resumo da demonstração

🗣️ **FALA**
> "Resumindo a parte prática: upload, exibição e remoção funcionando, com o arquivo aparecendo e sumindo no filesystem; validações de tamanho, formato e upload malicioso; e integridade referencial garantida pelo banco e pela aplicação. Agora vamos aos conceitos."

---

## 5. Explicação de Conceitos — Peso 1,5

> Cada conceito vem com a referência de livro exigida pela rubrica.

### 5.1 Chave Estrangeira (FK — Foreign Key)

🗣️ **FALA**
> "Chave estrangeira é um atributo, ou conjunto de atributos, de uma relação cujos valores precisam corresponder aos valores da chave primária de outra relação, ou então ser nulos. No nosso caso, `court_images.court_id` é uma FK que referencia `courts.id`. É ela que **materializa a relação 1xN**: muitas imagens apontam para uma mesma quadra."

📚 **Referência:** ELMASRI, R.; NAVATHE, S. B. *Sistemas de Banco de Dados*. 7. ed. São Paulo: Pearson, 2018. Cap. 5 (O Modelo de Dados Relacional e as Restrições em Bancos de Dados Relacionais), seção sobre restrições de integridade referencial e chaves estrangeiras.

📁 Mostre `database/schema.sql`, linhas 49–65.

### 5.2 Integridade Referencial

🗣️ **FALA**
> "Integridade referencial é a regra que garante que **toda referência aponta para algo que existe**. Uma imagem não pode apontar para uma quadra inexistente. O SGBD aplica essa regra em todo INSERT e UPDATE na tabela filha, como vimos no erro 1452, e em todo DELETE e UPDATE na tabela pai, conforme a ação referencial configurada."

📚 **Referência:** SILBERSCHATZ, A.; KORTH, H. F.; SUDARSHAN, S. *Sistema de Banco de Dados*. 7. ed. Rio de Janeiro: GEN LTC, 2020. Cap. 4 (SQL Intermediária), seção "Restrições de integridade" e "Integridade referencial".
📚 Complementar: DATE, C. J. *Introdução a Sistemas de Bancos de Dados*. 8. ed. Rio de Janeiro: Elsevier/Campus, 2004. Cap. 9 (Integridade).

### 5.3 CASCADE, SET NULL e RESTRICT

🗣️ **FALA**
> "Quando o registro pai é excluído ou atualizado, a FK precisa saber o que fazer com os filhos. São as **ações referenciais**:
> - **CASCADE**: propaga a operação, ou seja, excluir a quadra exclui as imagens. **É o que usamos** em `court_images`, porque uma foto não faz sentido sem a quadra. Também usamos em `invoices.user_id`.
> - **SET NULL**: mantém o filho e coloca `NULL` na FK. Exige que a coluna aceite nulo. Serviria, por exemplo, para manter as fotos 'sem quadra' num acervo.
> - **RESTRICT** (ou NO ACTION): **impede** a exclusão do pai enquanto houver filhos. O MySQL lança o erro 1451. Seria a escolha se quiséssemos obrigar o admin a apagar as fotos antes da quadra.
>
> Também usamos `ON UPDATE CASCADE`: se o id da quadra mudasse, as imagens acompanhariam."

📚 **Referência:** ELMASRI; NAVATHE, 2018, Cap. 6 (SQL Básica), seção "Especificando restrições de integridade referencial" (opções `ON DELETE`/`ON UPDATE` com `CASCADE`, `SET NULL`, `SET DEFAULT`, `RESTRICT`/`NO ACTION`).

💡 **Demonstração opcional no MySQL (≈ 1 min)**, com tabelas descartáveis, para mostrar RESTRICT e SET NULL na prática (o CASCADE já foi mostrado na Seção 3.1):
```sql
-- RESTRICT
CREATE TABLE demo_pai (id INT PRIMARY KEY) ENGINE=InnoDB;
CREATE TABLE demo_filho (
    id INT PRIMARY KEY,
    pai_id INT NULL,
    CONSTRAINT fk_demo FOREIGN KEY (pai_id) REFERENCES demo_pai(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
INSERT INTO demo_pai VALUES (1);
INSERT INTO demo_filho VALUES (10, 1);
DELETE FROM demo_pai WHERE id = 1;
-- ERROR 1451 (23000): Cannot delete or update a parent row: a foreign key constraint fails

-- SET NULL
ALTER TABLE demo_filho DROP FOREIGN KEY fk_demo;
ALTER TABLE demo_filho ADD CONSTRAINT fk_demo FOREIGN KEY (pai_id) REFERENCES demo_pai(id) ON DELETE SET NULL;
DELETE FROM demo_pai WHERE id = 1;     -- agora funciona
SELECT * FROM demo_filho;              -- pai_id = NULL (o filho continua existindo)

DROP TABLE demo_filho; DROP TABLE demo_pai;
```

### 5.4 `multipart/form-data` (enctype)

🗣️ **FALA**
> "Por padrão, um formulário HTML envia `application/x-www-form-urlencoded`, que codifica tudo como texto `chave=valor&...` e **não consegue transportar binário**. Para enviar arquivos usamos `enctype="multipart/form-data"`. O corpo da requisição é dividido em **partes** separadas por um *boundary*, e cada parte tem cabeçalhos próprios (`Content-Disposition: form-data; name="image"; filename="quadra1.jpg"` e `Content-Type`) seguidos dos bytes do arquivo. O PHP interpreta isso, grava o arquivo numa pasta temporária e preenche o array `$_FILES` com `name`, `type`, `tmp_name`, `error` e `size`."

🎬 Mostre no DevTools → aba **Network** → requisição `POST /admin/courts/1/images` → **Payload / Request Headers** com `Content-Type: multipart/form-data; boundary=----WebKitFormBoundary...`.

📁 Mostre `app/views/admin/courts/show.phtml`, linha 26 (`enctype="multipart/form-data"`).

📚 **Referência:** GOURLEY, D.; TOTTY, B. *HTTP: The Definitive Guide*. Sebastopol: O'Reilly, 2002. Cap. 15 (Entities and Encodings), seção sobre *multipart form submissions*. Norma: RFC 7578 — *Returning Values from Forms: multipart/form-data* (IETF, 2015).
📚 Complementar (PHP): WELLING, L.; THOMSON, L. *PHP and MySQL Web Development*. 5. ed. Addison-Wesley, 2016. Cap. 17 (Interacting with the File System and the Server), seção "Uploading Files".

### 5.5 MIME Type

🗣️ **FALA**
> "MIME type (*Multipurpose Internet Mail Extensions*) é o rótulo padronizado `tipo/subtipo` que identifica a natureza de um conteúdo: `image/jpeg`, `image/png`, `application/pdf`, `text/x-php`... O servidor usa esse rótulo no `Content-Type` para o navegador saber como exibir. **Ponto crítico de segurança:** o MIME que o navegador manda em `$_FILES['type']` é **declarado pelo cliente** e pode ser forjado. Por isso descartamos esse valor e detectamos o MIME real pelos *magic bytes*: JPEG começa com `FF D8 FF`, PNG com `89 50 4E 47`. Quem faz isso é `finfo` (libmagic)."

📁 Mostre `app/Services/ImageValidator.php`, linhas 111–118 (`new info(FILEINFO_MIME_TYPE)`).

📚 **Referência:** GOURLEY; TOTTY, 2002, Cap. 3 (HTTP Messages) e Cap. 15 (Entities and Encodings), seções sobre *Media Types and Charsets* e *Content-Type*. Normas: RFC 2045 e RFC 2046 (MIME, IETF, 1996).
📚 Segurança: OWASP. *Unrestricted File Upload* / *File Upload Cheat Sheet*. Disponível em: owasp.org.

---

## 6. Explicação do Código — Peso 2,5

### Visão geral da arquitetura (mostrar por 30 s)

```
Navegador (form multipart)
   │  POST /admin/courts/{court_id}/images
   ▼
config/routes.php ──► middleware auth + admin
   ▼
Admin\CourtImagesController::create()
   │   Court::findById()                 (galeria existe?)
   ▼
Court::gallery() ──► Services\CourtGallery::add()
   │   ├─ ImageValidator::validate()     (tamanho, formato, MIME real, malicioso)
   │   ├─ FileStorage::store()           (move_uploaded_file p/ uploads/courts/{id}/)
   │   └─ $court->images()->new([...])   (HasMany → CourtImage) → save()
   ▼
MySQL: INSERT INTO court_images (... court_id FK → courts.id)
```

🗣️ **FALA**
> "Separamos as responsabilidades: o **controller** só orquestra a requisição; o **ImageValidator** valida; o **FileStorage** mexe no filesystem; o **CourtGallery** e o **ProfileAvatar** são as regras de negócio de cada funcionalidade; e os **models** representam as tabelas e as relações do ActiveRecord do framework."

### 6.1 Fluxo de execução do código (Relação 1xN)

#### 6.1.1 Registro da imagem

📁 **Siga este caminho no VS Code:**

1. **`config/routes.php`** (linhas 52–62): rotas dentro de `Route::middleware('auth')` → `Route::middleware('admin')`. Destaque o recurso aninhado:
   ```php
   Route::post('/admin/courts/{court_id}/images', [AdminCourtImagesController::class, 'create'])
   ```
   > "O `{court_id}` na URL deixa explícito que a imagem pertence a uma quadra, que é o lado 1 da relação."

2. **`app/views/admin/courts/show.phtml`** (linhas 25–49): form com `method="POST"`, `enctype="multipart/form-data"`, `<input type="file" name="image" accept="image/jpeg,image/png,image/webp">` e o campo `caption`.

3. **`core/Http/Request.php`** (linhas 20–59): o framework **não** expunha `$_FILES`. Adicionamos:
   - `$this->files = $_FILES` no construtor;
   - `getFile('image')`, que retorna o array do arquivo ou `null`;
   - `hasFile()` e `addFiles()`, este usado pelos testes de controller para simular uploads.

4. **`app/Controllers/Admin/CourtImagesController.php`**, método `create()` (linha 19):
   - busca a quadra com `Court::findById($court_id)` e, se não existir, mostra *"Galeria inexistente"*;
   - `$gallery = $court->gallery();`
   - `$gallery->add($request->getFile('image'), $caption)`;
   - mostra a mensagem de sucesso com a URL ou a de erro com `firstError()`, escapada com `htmlspecialchars`.

5. **`app/Services/CourtGallery.php`**, método `add()` (linha 55), o coração do registro:
   - (a) confere se a quadra existe com `Court::exists(['id' => ...])`;
   - (b) `$this->validator->validate($file)`;
   - (c) `FileStorage::randomName('court', $validator->safeExtension())`, que gera `court_<32 hex>.jpg`;
   - (d) `$this->storage->store($tmp_name, 'courts/{id}', $fileName)`;
   - (e) `$this->court->images()->new([...])`: o **HasMany** já preenche o `court_id`;
   - (f) `$image->save()` dentro de um `try/catch (PDOException)`;
   - (g) se não salvar, **apaga o arquivo** para manter disco e banco consistentes.

6. **`app/Services/FileStorage.php`**, método `store()` (linha 31): cria a pasta com `mkdir(..., 0775, true)`, aplica `basename()` no nome (bloqueia `../../`) e chama `moveUploadedFile()` → `move_uploaded_file()`.
   > "`move_uploaded_file` só move arquivos que **realmente chegaram por HTTP POST**, então ninguém consegue usar esse fluxo para mover um arquivo qualquer do servidor."

7. **`app/Models/CourtImage.php`**: `$columns` (linha 29), `validates()` (linha 50) com `notEmpty` + `Validations::foreignKeyExists('court_id', $this, Court::class)`.

8. **`lib/Validations.php`**, `foreignKeyExists()` (linha 34): nova validação genérica de integridade referencial na camada de aplicação.

#### 6.1.2 Visualização da imagem

1. **`app/Controllers/Admin/CourtsController.php`** `show()` (linha 65) e **`app/Controllers/CourtsController.php`** `show()` (linha 18): carregam `$court->gallery()->images()`.
2. **`app/Services/CourtGallery.php`** `images()` (linha 39) → `$this->court->images()->get()` → **HasMany::get()** → `CourtImage::where(['court_id' => $id])`.
3. **`app/Models/CourtImage.php`** `relativePath()` (linha 69) monta `courts/{court_id}/{file_name}` e `url()` (linha 74) devolve `/assets/uploads/courts/...`.
4. **`core/Constants/Constants.php`**: os novos `uploadsPath()` (caminho físico) e `uploadsUrl()` (URL pública). Em `APP_ENV=testing` eles usam a subpasta `uploads/test`, para que os testes não misturem arquivos com os de desenvolvimento.
5. **`server/nginx.conf`**: o bloco `location ~ \.(css|js|jpeg|png|jpg|webp|gif|ico)` serve as imagens como arquivos estáticos. Adicionamos `webp`, `gif` e `ico`.
6. **Views:** `admin/courts/show.phtml` (gestão), `courts/show.phtml` (aluno, somente leitura), `admin/courts/index.phtml` (capa + contagem via `Court::coverImage()` e `imagesCount()`), `dashboard/index.phtml` (capa + botão "Ver galeria").

#### 6.1.3 Remoção da imagem

1. **`config/routes.php`**: `Route::delete('/admin/courts/{court_id}/images/{id}', ...)`. O form usa `<input type="hidden" name="_method" value="DELETE">`, que é o *method spoofing* já suportado pelo `Request` do framework.
2. **`app/Controllers/Admin/CourtImagesController.php`** `destroy()` (linha 51):
   ```php
   $image = $court->images()->findById((int) $imageId);
   ```
   > "Usamos o `findById` do **HasMany**, que filtra por `court_id` **e** `id`. Assim não dá para apagar a imagem de outra quadra trocando o ID na URL."
3. **`app/Services/CourtGallery.php`** `remove()` (linha 105): confere se a imagem pertence à galeria, depois `FileStorage::delete()` (`unlink`) e `$image->destroy()` (DELETE no banco).
4. **Cascata:** `app/Models/Court.php` `destroy()` (linha 149) chama `parent::destroy()` (o banco faz o CASCADE) e depois `CourtGallery::destroyFiles()`, que chama `FileStorage::deleteDirectory('courts/{id}')`.
5. Também para o usuário: `app/Models/User.php` `destroy()` (linha 161) apaga a pasta `uploads/users/{id}` quando o atleta é excluído pelo CRUD.

#### 6.1.4 Validação da imagem

📁 **`app/Services/ImageValidator.php`**, método `validate()` (linha 57). Percorra os comentários numerados:

| Passo | Linha | Regra | Mensagem |
|---|---|---|---|
| 1 | 62 | `error` do upload (`UPLOAD_ERR_NO_FILE`, `INI_SIZE`, `FORM_SIZE`) | "Nenhum arquivo foi enviado." / "excede o tamanho" |
| 2 | 85 | `filesize()` real: vazio ou > 2MB | "O arquivo excede o tamanho máximo permitido de 2MB." |
| 3 | 95 | qualquer segmento do nome em `FORBIDDEN_EXTENSIONS` (php, phtml, phar, sh, exe, js, html, svg…) | "Arquivo malicioso: extensões executáveis (.php)…" |
| 4 | 105 | extensão final ∈ jpg, jpeg, png, webp | "Formato não permitido…" |
| 5 | 111 | MIME real via `finfo` ∈ `ALLOWED_TYPES` | "…o conteúdo real (text/x-php) não é uma imagem permitida." |
| 6 | 120 | extensão coerente com o MIME | "A extensão .jpg não corresponde ao conteúdo…" |
| 7 | 127 | `getimagesize()` consegue ler largura e altura | "corrompido ou não é uma imagem válida" |
| 8 | 133 | conteúdo sem `<?php`, `<script`, `__halt_compiler` | "foi detectado código embutido na imagem" |

🗣️ **FALA**
> "A ordem importa: as verificações baratas vêm primeiro (erro, tamanho, nome) e as que leem o conteúdo vêm depois. O validador é **reutilizado** pela galeria e pelo avatar, e as views usam `acceptAttribute()`, `allowedLabel()` e `maxSizeLabel()` da própria classe. Assim a regra mostrada ao usuário é sempre a mesma que é aplicada."

### 6.3 Explicação do Funcionamento do Framework

#### 6.3.1 BelongsTo — `core/Database/ActiveRecord/BelongsTo.php`

📁 Abra o arquivo (são 19 linhas).

🗣️ **FALA**
> "O `BelongsTo` representa o lado **N → 1**. Ele recebe o model atual, a classe relacionada e o nome da FK. No `get()`, ele lê o valor da FK no model (`$this->model->court_id`) e faz `Court::findBy(['id' => valor])`, ou seja, um `SELECT ... FROM courts WHERE id = :id`."

📁 Mostre o uso em `app/Models/CourtImage.php`, linha 61:
```php
public function court(): BelongsTo
{
    return $this->belongsTo(Court::class, 'court_id');
}
```

📁 Mostre como o framework transforma isso em propriedade, em `core/Database/ActiveRecord/Model.php`, método `__get` (linhas 46–73):
> "Quando acessamos `$image->court` (sem parênteses), o `__get` não encontra um atributo com esse nome. Ele converte o nome para camelCase, acha o método `court()`, usa **Reflection** para conferir que o tipo de retorno é `BelongsTo`, `HasMany` ou `BelongsToMany` e chama `->get()` automaticamente. Por isso `$image->court->name` funciona. O teste `CourtImageTest::test_image_belongs_to_court` cobre esse caso."

#### 6.3.2 HasMany — `core/Database/ActiveRecord/HasMany.php`

📁 Abra o arquivo.

🗣️ **FALA**
> "O `HasMany` é o lado **1 → N**, e usamos quatro métodos dele:
> - `get()` (linha 19): `CourtImage::where(['court_id' => $court->id])` devolve todas as imagens da quadra;
> - `new($params)` (linha 27): cria um `CourtImage` **já com o `court_id` preenchido**. Usamos no `CourtGallery::add()`;
> - `findById($id)` (linha 34): busca **dentro da relação** (`court_id` + `id`). Usamos no `destroy` do controller por segurança;
> - `paginate()` (linha 44): pagina os filhos. Não foi necessário aqui, mas está disponível."

📁 Mostre o uso em `app/Models/Court.php`, linha 119:
```php
public function images(): HasMany
{
    return $this->hasMany(CourtImage::class, 'court_id');
}
```
> "E `$court->images` (sem parênteses) devolve o array de imagens pelo mesmo mecanismo do `__get`."

📚 Se quiserem citar: FOWLER, M. *Padrões de Arquitetura de Aplicações Corporativas*. Porto Alegre: Bookman, 2006. Padrão **Active Record** e **Foreign Key Mapping** (Cap. 10 e 12).

#### 6.3.3 ProfileAvatar — `app/Services/ProfileAvatar.php`

📁 Abra o arquivo.

🗣️ **FALA**
> "O `ProfileAvatar` encapsula a foto de perfil. Ele recebe o `User` e, opcionalmente, um `ImageValidator` e um `FileStorage` (**injeção de dependência**, que é o que permite testá-lo).
> - `path()` (linha 35): devolve a URL da foto ou `/assets/images/defaults/avatar.png` se não houver foto, ou se o arquivo tiver sumido do disco;
> - `update($file)` (linha 76): valida, grava como `avatar_<hash>.<ext>` em `uploads/users/{id}/`, **apaga a foto anterior** e faz `$user->update(['avatar_name' => ...])`. Usamos `update()` do Model, e não `save()`, para atualizar só essa coluna sem disparar as validações de senha;
> - `remove()` (linha 103): apaga o arquivo e coloca `avatar_name = NULL`;
> - `removeAll()` (linha 119): apaga a pasta do usuário e é chamado por `User::destroy()`.
>
> No `User` (linha 152) criamos `avatar(): ProfileAvatar`, então a view usa `$user->avatar()->path()`."

📁 Mostre o uso em `app/views/layouts/application.phtml` (miniatura na navbar) e em `app/Controllers/ProfileController.php` (`show`, `updateAvatar`, `destroyAvatar`).

### 6.4 Demonstração do Banco de Dados

#### 6.4.1 Explicar o banco para esta funcionalidade

📁 Abra `database/schema.sql`, linhas 46–65.

```sql
CREATE TABLE court_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    court_id INT NOT NULL,                 -- FK: a qual quadra pertence (lado N)
    file_name VARCHAR(100) NOT NULL,       -- nome gerado: court_<hash>.jpg
    original_name VARCHAR(255) NOT NULL,   -- nome enviado pelo usuário (só exibição)
    mime_type VARCHAR(50) NOT NULL,        -- MIME real detectado
    size INT UNSIGNED NOT NULL,            -- bytes
    caption VARCHAR(255) NULL,             -- legenda opcional
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_court_images_file_name (file_name),
    KEY idx_court_images_court_id (court_id),
    CONSTRAINT fk_court_images_court
        FOREIGN KEY (court_id) REFERENCES courts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;
```

Diagrama para mostrar ou desenhar:

```
┌──────────────┐ 1          N ┌────────────────────┐
│   courts     │──────────────│   court_images     │
├──────────────┤              ├────────────────────┤
│ id (PK)      │◄─────────────│ court_id (FK)      │
│ name         │  ON DELETE   │ id (PK)            │
│ type         │  CASCADE     │ file_name (UNIQUE) │
│ status       │              │ original_name      │
└──────────────┘              │ mime_type, size    │
                              │ caption, created_at│
┌──────────────┐              └────────────────────┘
│   users      │
├──────────────┤   avatar (1x1): coluna users.avatar_name
│ avatar_name  │   → arquivo em uploads/users/{id}/
└──────────────┘
```

🗣️ **FALA**
> "**O banco não guarda o binário da imagem.** Guarda só os metadados e o nome do arquivo. O binário fica no filesystem, servido pelo Nginx. Assim o banco fica leve, os backups ficam menores e o servidor web entrega arquivos estáticos de forma eficiente. O `file_name` é `UNIQUE` para nunca haver duas linhas apontando para o mesmo arquivo. O índice em `court_id` acelera o `WHERE court_id = ?` do HasMany, e a `CONSTRAINT` com `ON DELETE CASCADE` garante a integridade. A tabela usa InnoDB, o engine do MySQL que suporta FK."

🎬 **AÇÃO** no Terminal 2 (MySQL):
```sql
SHOW CREATE TABLE court_images\G
DESCRIBE court_images;

-- lado N de uma quadra
SELECT id, court_id, file_name, original_name, mime_type, size, caption
FROM court_images WHERE court_id = 1;

-- relação 1xN com JOIN e contagem
SELECT c.id, c.name, COUNT(ci.id) AS total_fotos
FROM courts c LEFT JOIN court_images ci ON ci.court_id = c.id
GROUP BY c.id, c.name;

-- avatar
SELECT id, name, avatar_name FROM users WHERE id = 2;

-- metadados da FK
SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME, DELETE_RULE, UPDATE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE();
```
> A última consulta mostra `fk_court_images_court | court_images | courts | CASCADE | CASCADE` e também a FK de `invoices`.

---

## 7. Explicação dos Testes Automatizados — Peso 2

🎬 **AÇÃO** (rode antes da gravação e mostre a saída; ou rode ao vivo se houver tempo):
```bash
./run test tests/Unit          # unitários (PHPUnit, --testdox)
./run test tests/Integration   # controllers + acesso
./run test:browser             # aceitação (Codeception + Selenium)
./run phpcs && ./run phpstan   # qualidade
```

🗣️ **FALA (introdução)**
> "Os testes usam uma pasta isolada, `public/assets/uploads/test`, e a classe `UploadFixtures` gera os arquivos na hora: um PNG válido montado byte a byte, um JPEG real, um arquivo de 3MB, um script PHP, um PNG poliglota e um GIF. Como em CLI não existe upload HTTP de verdade, o `TestFileStorage` substitui **só** o `move_uploaded_file` por `copy`. Todo o resto do código roda igual à produção."

📁 `tests/Support/Upload/UploadFixtures.php` e `tests/Support/Upload/TestFileStorage.php`.

### 7.1 Testes de Aceitação (Codeception + Selenium)

📁 **`tests/Acceptance/Admin/CourtGalleryCest.php`**

| Método | Rubrica | O que faz |
|---|---|---|
| `uploadDeImagem` | 1.1 Upload | Cria uma quadra, anexa `quadra_foto.jpg` (`attachFile`), envia, vê a mensagem de sucesso, vê 1 `<img>` e o selo "arquivo presente", e **confere no filesystem** com `Assert::assertFileExists` |
| `visualizacaoDeImagem` | 1.2 Visualização | O admin envia uma foto para a quadra 1, faz logout, o **aluno** loga, clica em "Ver galeria", vê a legenda e a imagem e **não** vê "Remover Imagem" |
| `remocaoDeImagem` | 1.3 Remoção | Envia, guarda o caminho físico, clica em remover, aceita o `confirm` e confere a mensagem e que o **arquivo sumiu do disco** |
| `validacaoTamanhoMaximo` | Validação | Gera um arquivo de 3MB em `_before` e confere a mensagem de 2MB |
| `validacaoUploadMalicioso` | Validação | `malicioso.php.jpg` e `script_disfarcado.jpg` são rejeitados |
| `exclusaoDaQuadraRemoveGaleria` | Integridade | Quadra com 2 fotos é excluída; confere a mensagem de CASCADE e que **a pasta foi apagada** |

📁 **`tests/Acceptance/Profile/ProfileAvatarCest.php`**

| Método | O que faz |
|---|---|
| `uploadEVisualizacaoDoAvatar` | O aluno envia uma foto; o `src` aponta para `/assets/uploads/users/`, o arquivo existe e a mesma foto aparece na navbar e na Área do Aluno |
| `remocaoDoAvatar` | Remove a foto; volta o avatar padrão e o arquivo some do disco |
| `uploadDeAvatarMalicioso` | `malicioso.php.jpg` é rejeitado |

📁 Fixtures: `tests/Support/Data/quadra_foto.jpg`, `malicioso.php.jpg` e `script_disfarcado.jpg` (inofensivos, só para teste).

### 7.2 Testes Unitários

#### 7.2.1 Métodos dos models

📁 **`tests/Unit/Models/CourtImageTest.php`**
- `test_should_create_image_with_created_at`
- `test_validates_required_fields`: `notEmpty` em court_id, file_name, original_name e mime_type
- `test_validates_that_court_exists`: `foreignKeyExists` com court_id 9999
- `test_image_belongs_to_court`: **BelongsTo** e `$image->court->name`
- `test_court_has_many_images`: **HasMany**, `$court->images` e `findById` dentro da relação
- `test_relative_path_url_and_size_label`
- `test_database_rejects_image_for_nonexistent_court`: INSERT puro no banco lança `PDOException` "foreign key constraint fails" (**galeria inexistente**)
- `test_database_cascade_deletes_images_with_court`: DELETE puro na quadra apaga as imagens (**CASCADE**)

📁 **`tests/Unit/Models/CourtTest.php`** (novos): `test_court_images_relationship_is_has_many`, `test_cover_image_is_first_image_of_gallery`, `test_destroy_removes_court_and_its_images`.

📁 **`tests/Unit/Models/Users/UserTest.php`** (novo): `test_user_avatar_returns_profile_avatar_with_default_image`.

#### 7.2.2 Testes para o serviço de upload de imagem/arquivo

📁 **`tests/Unit/Services/CourtGalleryTest.php`**
- `test_add_saves_file_and_database_record`: confere banco (court_id, original_name, mime, legenda com `trim`, nome aleatório) **e** filesystem
- `test_add_multiple_images_relation_one_to_many`: 3 imagens na mesma quadra
- `test_add_rejects_invalid_file_without_writing_anything`: nem registro nem pasta são criados
- `test_add_to_nonexistent_gallery_fails`
- `test_remove_deletes_file_and_record`
- `test_remove_image_from_another_gallery_is_denied`
- `test_destroying_court_removes_images_from_database_and_filesystem`

📁 **`tests/Unit/Services/ProfileAvatarTest.php`**
- avatar padrão, `update` grava arquivo + `avatar_name`, **troca apaga o arquivo antigo**, arquivo malicioso não altera o usuário, usuário não salvo, `remove`, `remove` sem foto, **excluir o usuário apaga o avatar**.

📁 **`tests/Unit/Services/FileStorageTest.php`**
- grava, bloqueia *directory traversal* (`../../../evil.png` vira `evil.png`), apaga arquivo e pasta, o storage real **recusa** arquivo que não veio por HTTP (`move_uploaded_file`), nomes aleatórios únicos.

#### 7.2.3 Testes para as validações

📁 **`tests/Unit/Services/ImageValidatorTest.php`** (14 testes, sem banco)
- **Formatos:** PNG válido, JPEG com extensão maiúscula, GIF recusado, PNG com extensão `.jpg` recusado
- **Tamanho:** > 2MB, `UPLOAD_ERR_INI_SIZE`, limite customizado, arquivo vazio, nenhum arquivo
- **Malicioso:** `shell.php`, `shell.php.png` (dupla extensão), script renomeado para `.jpg`, **PNG poliglota com `<?php`**
- helpers das views (`accept`, rótulos)

### 7.3 Testes de Acesso (teste de controller)

#### 7.3.1 Rotas autenticadas não devem ser acessíveis por usuários não autenticados

📁 **`tests/Integration/Access/AuthenticatedRoutesAccessTest.php`**: usa **Guzzle** contra o Nginx real (`http://web:80`), com `allow_redirects => false`, e confere **302 → /login** para:
- `GET /profile`
- `POST /profile/avatar` (multipart com arquivo!) e `DELETE /profile/avatar`
- `GET /courts/1`
- `GET /admin/courts` e `GET /admin/courts/1`
- `POST /admin/courts/1/images` (multipart) e `DELETE /admin/courts/1/images/1`
- `DELETE /admin/courts/1`

🗣️ **FALA**
> "Testamos inclusive o POST com arquivo anexado: o middleware `auth` barra a requisição antes de qualquer código de upload rodar."

📁 Testes de controller complementares:
- **`tests/Integration/Controllers/CourtGalleryControllersTest.php`**: listagem com contagem, galeria com `multipart/form-data`, criação de quadra, exclusão em cascata (banco + disco), upload para galeria inexistente, upload sem arquivo, remoção, remoção de imagem de **outra** quadra (não encontrada), e visão do aluno sem botão de remover.
- **`tests/Integration/Controllers/ProfileControllerTest.php`**: perfil com avatar padrão/enviado, upload malicioso via `$_FILES` simulado e remoção.
- **`tests/Integration/Controllers/ControllerTestCase.php`**: `post()` ganhou o parâmetro `$files` para simular `$_FILES`.

---

## Encerramento (≈ 30 s)

🗣️ **FALA**
> "Com isso cobrimos a relação 1xN com a galeria de fotos das quadras e o avatar de perfil, cada arquivo gravado e removido no filesystem com o registro no banco; as validações de tamanho, formato e upload malicioso; a integridade referencial com FK e ON DELETE CASCADE, também validada na aplicação; e a suíte de testes de aceitação, unitários e de acesso. Obrigado!"

---

## Apêndice A — Inventário completo de arquivos criados e alterados

### A.1 Arquivos CRIADOS

| Arquivo | Conteúdo / Responsabilidade |
|---|---|
| `app/Models/CourtImage.php` | Model da tabela `court_images` (lado N). Colunas, `created_at` padrão, `validates()` (`notEmpty` + `foreignKeyExists`), `court(): BelongsTo`, `relativePath()`, `url()`, `sizeLabel()`. |
| `app/Services/ImageValidator.php` | Validação de upload em 8 passos (erro do upload, tamanho ≤ 2MB, extensões executáveis em qualquer segmento, extensão permitida, MIME real via `finfo`, coerência extensão/MIME, `getimagesize`, assinaturas de código). `safeExtension()`, `mimeType()`, helpers para a view. |
| `app/Services/FileStorage.php` | Operações de filesystem: `store()` (mkdir + `basename` + `move_uploaded_file`), `delete()`, `deleteDirectory()`, `exists()`, `absolutePath()`, `url()`, `randomName()`. `moveUploadedFile()` é protegido para os testes poderem substituí-lo. |
| `app/Services/CourtGallery.php` | Regra de negócio da galeria 1xN: `add()` (confere a quadra, valida, grava, cria via `HasMany::new`, faz rollback do arquivo em caso de erro), `remove()` (verifica a quadra dona, apaga arquivo + registro), `destroyFiles()`, `images()`, `url()`, `fileExists()`. |
| `app/Services/ProfileAvatar.php` | Foto de perfil: `path()` (com fallback para o avatar padrão), `hasAvatar()`, `update()` (troca e apaga a antiga), `remove()`, `removeAll()`, `directory()`. |
| `app/Controllers/ProfileController.php` | `show` (GET /profile), `updateAvatar` (POST /profile/avatar), `destroyAvatar` (DELETE /profile/avatar). |
| `app/Controllers/CourtsController.php` | `show` (GET /courts/{id}): galeria somente leitura para usuários autenticados. |
| `app/Controllers/Admin/CourtsController.php` | `index`, `new`, `create`, `show` (galeria com upload) e `destroy` (cascata) das quadras. |
| `app/Controllers/Admin/CourtImagesController.php` | `create` (upload; trata galeria inexistente) e `destroy` (remove via `HasMany::findById`). |
| `app/views/profile/show.phtml` | Aba "Meu Perfil": avatar atual, caminho no filesystem e no banco, form `multipart/form-data`, botão remover. |
| `app/views/admin/courts/index.phtml` | Lista de quadras com capa, status, contagem de fotos e ações Galeria/Excluir (com confirmação). |
| `app/views/admin/courts/new.phtml` | Formulário de cadastro de quadra. |
| `app/views/admin/courts/show.phtml` | Galeria do admin: form de upload (multipart, accept, legenda), cards com metadados, selo "arquivo presente", caminho físico, botão remover. |
| `app/views/courts/show.phtml` | Galeria do aluno (somente leitura). |
| `tests/Support/Upload/UploadFixtures.php` | Gera arquivos de teste (PNG byte a byte, JPEG, 3MB, script PHP, poliglota, GIF), simula `$_FILES` e limpa `uploads/test`. |
| `tests/Support/Upload/TestFileStorage.php` | `FileStorage` que troca `move_uploaded_file` por `copy` nos testes. |
| `tests/Support/Data/quadra_foto.jpg` | Fixture de imagem válida para os testes de aceitação. |
| `tests/Support/Data/malicioso.php.jpg` | Fixture de dupla extensão (conteúdo inofensivo). |
| `tests/Support/Data/script_disfarcado.jpg` | Fixture de script com extensão de imagem (conteúdo inofensivo). |
| `tests/Unit/Models/CourtImageTest.php` | Testes do model, das relações e da FK/CASCADE no banco. |
| `tests/Unit/Services/ImageValidatorTest.php` | Testes das validações (formato, tamanho, malicioso). |
| `tests/Unit/Services/FileStorageTest.php` | Testes do storage. |
| `tests/Unit/Services/CourtGalleryTest.php` | Testes do serviço de upload da galeria. |
| `tests/Unit/Services/ProfileAvatarTest.php` | Testes do serviço de avatar. |
| `tests/Integration/Controllers/CourtGalleryControllersTest.php` | Testes dos controllers da galeria. |
| `tests/Integration/Controllers/ProfileControllerTest.php` | Testes do controller de perfil. |
| `tests/Acceptance/Admin/CourtGalleryCest.php` | Aceitação: upload, visualização, remoção, validações e cascata. |
| `tests/Acceptance/Profile/ProfileAvatarCest.php` | Aceitação: avatar (upload, visualização, remoção, malicioso). |
| `roteiro.md` | Este roteiro. |

### A.2 Arquivos ALTERADOS

| Arquivo | O que mudou |
|---|---|
| `database/schema.sql` | `DROP TABLE IF EXISTS court_images` + nova tabela `court_images` com `CONSTRAINT fk_court_images_court ... ON DELETE CASCADE ON UPDATE CASCADE`, `UNIQUE(file_name)` e índice em `court_id`. |
| `app/Models/Court.php` | `use HasMany/CourtGallery`; `@property array<CourtImage> $images`; novos `images(): HasMany`, `gallery()`, `coverImage()`, `imagesCount()`; **override de `destroy()`** que apaga a pasta da galeria após o DELETE. |
| `app/Models/User.php` | `use ProfileAvatar`; novo `avatar(): ProfileAvatar`; **override de `destroy()`** que apaga `uploads/users/{id}`. |
| `lib/Validations.php` | Nova validação `foreignKeyExists($attribute, $obj, $relatedClass)`. |
| `core/Http/Request.php` | Suporte a `$_FILES`: propriedade `$files`, `getFile()`, `hasFile()`, `addFiles()`. |
| `core/Constants/Constants.php` | `uploadsPath()` e `uploadsUrl()` (subpasta `/test` em `APP_ENV=testing`). |
| `config/routes.php` | Rotas de perfil (`/profile`, `/profile/avatar` POST/DELETE), galeria do aluno (`/courts/{id}`) e admin (`/admin/courts` CRUD + `/admin/courts/{court_id}/images` POST/DELETE). |
| `app/views/layouts/application.phtml` | Links "Meu Perfil" e "Quadras e Galerias" (admin) e miniatura do avatar na navbar. |
| `app/views/dashboard/index.phtml` | Avatar na credencial + link "Alterar foto"; cards de quadra com foto de capa e botão "Ver galeria (N fotos)". |
| `app/views/admin/users/show.phtml` | Exibe o avatar do atleta. |
| `app/views/admin/users/index.phtml` | Botão "Quadras e Galerias" no cabeçalho do painel. |
| `public/assets/css/application.css` | Classes `.avatar-sm/lg/xl`, `.gallery-thumb`, `.court-cover`, `.court-cover-sm` (`object-fit: cover`). |
| `server/nginx.conf` | Extensões estáticas: `webp`, `gif` e `ico` adicionadas. |
| `database/Populate/populate.php` | Limpa `uploads/courts` e `uploads/users` ao repopular (evita arquivos órfãos) e imprime as novas rotas. |
| `.github/workflows/ci.yml` | Job de aceitação: `chown www-data` em `public/assets/uploads` antes de subir os containers (o PHP-FPM precisa gravar os uploads). |
| `tests/Integration/Access/AuthenticatedRoutesAccessTest.php` | 8 novos testes de acesso para as rotas de imagens (GET, POST multipart, DELETE). |
| `tests/Integration/Controllers/ControllerTestCase.php` | `post()` aceita `$files` (simula `$_FILES`). |
| `tests/Unit/Models/CourtTest.php` | 3 testes novos da relação `images()` e do `destroy()`. |
| `tests/Unit/Models/Users/UserTest.php` | Teste de `avatar()`. |
| `README.md` | Seção 1.3 "Imagens e Arquivos (1xN)", tabela de perfis atualizada e menção à nova tabela. |

---

## Apêndice B — Checklist da rubrica × seção do roteiro

| Item da rubrica | Peso | Onde no roteiro |
|---|---|---|
| 1.1 Upload de arquivos | 1 | Seção 1.1 |
| 1.2 Apresentação dos arquivos | 1 | Seção 1.2 |
| 1.3 Remoção de arquivo | 1 | Seção 1.3 |
| 1.4 Validação do arquivo | 1 | Seções 1.4 e 2 |
| Arquivo no filesystem: no upload | 1 | Seção 1.1, passos 1 e 7 |
| Arquivo no filesystem: na remoção | 1 | Seção 1.3, passo 5 |
| Validações: tamanho e formatos | 1 | Seção 2.1 |
| Validações: upload malicioso | 1 | Seção 2.2 |
| Integridade: excluir a entidade principal | 1 | Seção 3.1 |
| Integridade: galeria inexistente | 1 | Seção 3.2 (banco + aplicação) |
| Conceito: FK | 1,5 | Seção 5.1 |
| Conceito: Integridade Referencial | 1,5 | Seção 5.2 |
| Conceito: CASCADE / SET NULL / RESTRICT | 1,5 | Seção 5.3 |
| Conceito: multipart/form-data | 1,5 | Seção 5.4 |
| Conceito: MIME Type | 1,5 | Seção 5.5 |
| Código 1.1–1.4 (registro, visualização, remoção, validação) | 2,5 | Seções 6.1.1 a 6.1.4 |
| Framework 3.1 BelongsTo | 2,5 | Seção 6.3.1 |
| Framework 3.2 HasMany | 2,5 | Seção 6.3.2 |
| Framework 3.3 ProfileAvatar | 2,5 | Seção 6.3.3 |
| Banco 4.1 | 2,5 | Seção 6.4 |
| Aceitação: upload / visualização / remoção | 2 | Seção 7.1 |
| Unitários: models / serviço de upload / validações | 2 | Seção 7.2 |
| Acesso: rotas autenticadas | 2 | Seção 7.3 |

---

## Apêndice C — Perguntas prováveis do professor (respostas rápidas)

- **"Por que não guardar a imagem no banco (BLOB)?"** Deixa o banco pesado, os backups lentos e obriga a passar pelo PHP para servir cada imagem. No filesystem, o Nginx serve como arquivo estático e o banco guarda só a referência.
- **"E se alguém enviar `foto.php`?"** É bloqueado pela extensão. Mesmo que passasse, o arquivo seria renomeado para `court_<hash>.jpg`, e o Nginx sempre usa `SCRIPT_FILENAME = index.php`, então nada em `uploads/` é executado.
- **"Por que o CASCADE não basta?"** O CASCADE apaga **linhas**. Os **arquivos** estão fora do banco, então precisam ser apagados pela aplicação (`Court::destroy()` → `deleteDirectory`).
- **"Por que validar a FK na aplicação se o banco já valida?"** Para dar uma mensagem amigável ("Galeria inexistente") em vez de uma exceção SQL, e para não gravar o arquivo no disco à toa. O banco continua sendo a última linha de defesa.
- **"Como vocês testam upload se `move_uploaded_file` só funciona com HTTP?"** Nos unitários, o `TestFileStorage` substitui só esse método. Nos testes de aceitação, o upload é real, feito pelo navegador via Selenium.
- **"Por que o nome do arquivo é aleatório?"** Evita colisão, sobrescrita, *path traversal* e enumeração de arquivos, e impede que caracteres do nome original causem problemas.
