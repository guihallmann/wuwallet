# WuWallet

O WuWallet é uma aplicação Laravel com frontend React (Inertia). Este guia explica como rodar o projeto de duas formas:

- **Opção A – Ambiente local (sem Docker):** recomendada para quem não tem Docker. Instala PHP, Composer e Node direto na máquina.
- **Opção B – Docker (Laravel Sail) no Windows, sem WSL:** sobe PHP, Node e o MailHog (caixa de e-mails de teste) em containers.

> Se você só precisa enviar convites de clientes e ver o e-mail, a Opção A com `MAIL_MAILER=log` (veja [E-mails e convites](#e-mails-e-convites-de-clientes)) é a mais simples.

## Sumário

- [Pré-requisitos](#pré-requisitos)
- [Opção A – Ambiente local (sem Docker)](#opção-a--ambiente-local-sem-docker)
- [Opção B – Docker no Windows (sem WSL)](#opção-b--docker-no-windows-sem-wsl)
- [E-mails e convites de clientes](#e-mails-e-convites-de-clientes)
- [Comandos do dia a dia](#comandos-do-dia-a-dia)
- [Contas de teste](#contas-de-teste)
- [Banco de dados](#banco-de-dados)
- [Solução de problemas](#solução-de-problemas)

## Pré-requisitos

### Opção A (local)

| Ferramenta | Versão              | Observação                                                                                                                          |
| ---------- | ------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| Git        | qualquer recente    | https://git-scm.com                                                                                                                 |
| PHP        | **8.4 ou superior** | extensões: `mbstring`, `openssl`, `pdo_sqlite`, `sqlite3`, `curl`, `fileinfo`, `zip`, `intl`, `xml`, `ctype`, `tokenizer`, `bcmath` |
| Composer   | 2.x                 | https://getcomposer.org                                                                                                             |
| Node.js    | 22 LTS ou superior  | já inclui o `npm`                                                                                                                   |

No Windows, a forma mais fácil de ter PHP + Composer + Node é instalar o [Laravel Herd](https://herd.laravel.com/windows). Outra opção é o [XAMPP](https://www.apachefriends.org) com PHP 8.4, adicionando o PHP ao `PATH`.

Para conferir a instalação, abra um terminal e rode:

```bash
php -v
composer -V
node -v
npm -v
```

### Opção B (Docker)

- Git
- Docker Desktop instalado no Windows
- Não é necessário instalar PHP, Composer ou Node na máquina

## Opção A – Ambiente local (sem Docker)

Use o PowerShell, Prompt de Comando, Git Bash ou o terminal do VS Code.

### 1. Clonar o repositório

```bash
git clone <url-do-repositorio> wuwallet
cd wuwallet
```

### 2. Instalar tudo de uma vez

O script `setup` instala as dependências PHP, cria o `.env`, gera a chave da aplicação, roda as migrations, instala as dependências do Node e compila o frontend:

```bash
composer run setup
```

Se preferir fazer passo a passo:

```bash
composer install
cp .env.example .env          # no PowerShell: Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

### 3. Ajustar o `.env` para rodar fora do Docker

O `.env.example` foi pensado para o Docker (`MAIL_HOST=mailhog`, que só existe dentro do Docker). Abra o `.env` e ajuste:

```dotenv
APP_URL=http://localhost:8000

# E-mails: escolha UMA das opções da seção "E-mails e convites de clientes"
MAIL_MAILER=log
```

### 4. Popular o banco (opcional, mas recomendado)

```bash
php artisan migrate --seed
```

### 5. Rodar o projeto

```bash
composer run dev
```

Esse comando sobe, ao mesmo tempo, o servidor Laravel, o worker de filas e o Vite (hot reload do frontend). Deixe o terminal aberto e acesse [http://localhost:8000](http://localhost:8000). Para parar, use `Ctrl + C`.

## Opção B – Docker no Windows (sem WSL)

Aqui os comandos são rodados no **PowerShell** (na máquina) ou **dentro do container** (shell do Sail). O script `./vendor/bin/sail` é um script bash e não funciona direto no PowerShell, por isso usamos `docker compose`.

### 1. Clonar o repositório

```powershell
git clone <url-do-repositorio> wuwallet
cd wuwallet
```

### 2. Criar o `.env`

```powershell
Copy-Item .env.example .env
```

O `.env.example` já vem com `MAIL_HOST=mailhog`, que é o que funciona dentro do Docker. Não altere.

### 3. Instalar as dependências PHP (primeira vez)

Como PHP e Composer não estão instalados no Windows, use um container temporário. Esse passo cria a pasta `vendor`:

```powershell
docker run --rm -v "${PWD}:/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
```

No Prompt de Comando (cmd), troque `${PWD}` por `%cd%`.

### 4. Construir e subir os containers

```powershell
docker compose build
docker compose up -d
```

Isso sobe dois serviços: `laravel.test` (a aplicação) e `mailhog` (caixa de e-mails de teste).

### 5. Entrar no shell do container

Todos os comandos `php`, `composer`, `npm` e `artisan` abaixo devem ser rodados **dentro do container**:

```powershell
docker compose exec -u sail laravel.test bash
```

O prompt vai mudar para algo como `sail@abc123:/var/www/html$`. Para sair do container, digite `exit`.

### 6. Preparar a aplicação (dentro do container)

```bash
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

### 7. Rodar o frontend com hot reload (dentro do container)

O servidor PHP já sobe automaticamente com o container. Falta apenas o Vite:

```bash
npm run dev -- --host 0.0.0.0
```

Deixe esse terminal aberto. A aplicação fica em [http://localhost:8000](http://localhost:8000) e a caixa de e-mails (MailHog) em [http://localhost:8025](http://localhost:8025).

> Dentro do container não é necessário rodar `composer run dev`, pois o servidor PHP já está ativo na porta 80 do container (mapeada para 8000). Se precisar do worker de filas, abra outro shell no container e rode `php artisan queue:listen`.

### Comandos do Docker no PowerShell

```powershell
docker compose up -d                  # subir os containers
docker compose stop                   # parar os containers
docker compose down                   # parar e remover os containers
docker compose ps                     # ver status
docker compose logs -f laravel.test   # ver logs da aplicação
docker compose build --no-cache       # reconstruir a imagem do zero
```

## E-mails e convites de clientes

O analista envia um convite por e-mail para o cliente se cadastrar. O `.env.example` usa `MAIL_HOST=mailhog`, que só funciona dentro do Docker. Sem Docker, escolha uma das opções abaixo no `.env` e, depois de alterar, rode `php artisan config:clear`.

### Opção 1 – Log (mais simples, sem instalar nada)

```dotenv
MAIL_MAILER=log
```

O e-mail não é enviado de verdade: ele é gravado em `storage/logs/laravel.log`. Abra o arquivo, procure por `register/invite` e copie o link do convite para o navegador.

Para acompanhar o log em tempo real:

```bash
php artisan pail
```

### Opção 2 – Mailtrap (caixa de entrada online, gratuita)

1. Crie uma conta em https://mailtrap.io e abra **Email Testing > Inboxes > My Inbox**.
2. Copie as credenciais SMTP e preencha o `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=<seu-usuario>
MAIL_PASSWORD=<sua-senha>
```

Os e-mails aparecem na caixa de entrada do Mailtrap.

### Opção 3 – Mailpit local (parecido com o MailHog)

Baixe o executável do Mailpit em https://github.com/axllent/mailpit/releases, execute-o e configure:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

A caixa de entrada fica em [http://localhost:8025](http://localhost:8025).

### Opção 4 – Docker (MailHog)

Na Opção B (Docker), nada precisa ser alterado: os e-mails chegam em [http://localhost:8025](http://localhost:8025).

> O link do convite usa o `APP_URL` do `.env`. Se você rodar em outra porta, ajuste o `APP_URL` (ex.: `http://localhost:8001`).

## Comandos do dia a dia

Na Opção A, rode os comandos no terminal da máquina. Na Opção B, rode **dentro do container** (`docker compose exec -u sail laravel.test bash`).

### Rodar o projeto

```bash
composer run dev
```

Na Opção B use `npm run dev -- --host 0.0.0.0` (veja acima).

### Banco de dados

```bash
php artisan migrate               # aplica migrations pendentes
php artisan migrate:fresh         # APAGA tudo e recria as tabelas
php artisan migrate:fresh --seed  # apaga, recria e popula com dados de teste
php artisan db:seed               # só roda os seeders
php artisan migrate:status        # mostra o estado das migrations
php artisan migrate:rollback      # desfaz o último lote de migrations
```

> Atenção: `migrate:fresh` apaga todos os dados do banco local.

### Wayfinder (rotas tipadas no frontend)

O Wayfinder gera funções TypeScript a partir das rotas e controllers do Laravel (pasta `resources/js/wayfinder`, ignorada pelo Git). Ele é gerado automaticamente com `npm run dev` ou `npm run build`. Para gerar manualmente (por exemplo, após criar ou alterar rotas):

```bash
php artisan wayfinder:generate
```

Se o frontend reclamar que não encontra `@/routes`, `@/actions` ou `@/wayfinder`, rode esse comando.

### Testes

```bash
php artisan test                                 # todos os testes
php artisan test --filter=ClientInvitationTest   # um teste específico
php artisan test tests/Feature                   # só a pasta Feature
```

### Lint, tipos e verificação completa (CI)

```bash
composer run lint          # corrige o estilo do PHP (Pint)
composer run lint:check    # só verifica o estilo do PHP, sem alterar arquivos
composer run types:check   # análise estática do PHP (PHPStan/Larastan)
composer run test          # lint:check + types:check + testes (PHP)

npm run check              # lint/formatação do frontend
npm run check:fix          # corrige lint/formatação do frontend
npm run types:check        # checagem de tipos do TypeScript

composer run ci:check      # TUDO: frontend + tipos + PHP + testes
```

Rode `composer run ci:check` antes de abrir um Pull Request. Se ele passar, o CI também deve passar.

### Outros comandos úteis

```bash
php artisan config:clear   # limpa o cache de configuração (use após alterar o .env)
php artisan optimize:clear # limpa todos os caches
php artisan route:list     # lista todas as rotas
php artisan assets:sync    # importa as ações da B3 e atualiza os preços (brapi)
php artisan tinker         # console interativo do Laravel
php artisan pail           # acompanha os logs em tempo real
npm run build              # gera o frontend para produção
```

## Contas de teste

Criadas por `php artisan db:seed` ou `php artisan migrate --seed`:

| Perfil   | E-mail               | Senha      |
| -------- | -------------------- | ---------- |
| Gestor   | `manager@email.com`  | `password` |
| Analista | `analista@email.com` | `password` |
| Cliente  | `cliente1@email.com` | `password` |

Os dados do gestor podem ser alterados no `.env` com `MANAGER_MASTER_EMAIL`, `MANAGER_MASTER_NAME` e `MANAGER_MASTER_PASSWORD`.

O menu **Movimentações** e o formulário de compra/venda estão disponíveis no perfil de cliente.

## Banco de dados

O banco de desenvolvimento é o **SQLite** (arquivo `database/database.sqlite`), então não é preciso instalar MySQL ou PostgreSQL. O arquivo é criado automaticamente na primeira migration e é ignorado pelo Git. Os testes usam SQLite em memória, sem afetar seus dados.

A sincronização de ativos usa a API da brapi. A chave é opcional (`BRAPI_API_KEY` no `.env`); sem ela o limite é de 20 requisições por minuto.

O seeder (`php artisan db:seed` ou `migrate --seed`) cria os usuários de teste e executa `assets:sync` para popular a tabela de ativos, então é necessário ter internet. Se a brapi falhar, o seed continua e mostra um aviso; nesse caso rode `php artisan assets:sync` depois. O seeder não cria carteiras nem movimentações: crie uma carteira pela aplicação antes de registrar uma compra ou venda.

> `migrate --seed` preserva os dados existentes e aplica apenas migrations pendentes. `migrate:fresh --seed` apaga todas as tabelas antes de recriá-las; use somente se quiser apagar o banco local.

## Solução de problemas

- **Erro "getaddrinfo failed" / `mailhog` ao enviar convite:** você está rodando fora do Docker. Use `MAIL_MAILER=log` (ou outra opção da seção de e-mails) e rode `php artisan config:clear`.
- **`composer: command not found` / `php: command not found`:** o PHP ou o Composer não está no `PATH`. Reabra o terminal depois de instalar, ou use a Opção B.
- **"Your PHP version does not satisfy the requirement":** o projeto exige PHP 8.4 ou superior. Confira com `php -v`.
- **"could not find driver" (SQLite):** habilite as extensões `pdo_sqlite` e `sqlite3` no `php.ini`.
- **"No application encryption key has been specified":** rode `php artisan key:generate`.
- **Tela em branco ou erro de manifest do Vite:** rode `npm install` e `npm run build` (ou deixe o `npm run dev` ativo).
- **Erro de `@/routes`, `@/actions` ou `@/wayfinder` não encontrado:** rode `php artisan wayfinder:generate`.
- **Alterei o `.env` e nada mudou:** rode `php artisan config:clear` e reinicie o `composer run dev`.
- **Porta 8000 ocupada:** no `.env`, mude `APP_PORT` (Docker) ou rode `php artisan serve --port=8001` e ajuste o `APP_URL`.
- **Docker: "docker daemon is not running":** abra o Docker Desktop e espere ele indicar que está em execução.
- **Docker: o projeto está lento no Windows:** mantenha o repositório em um caminho curto e local (por exemplo, `C:\projetos\wuwallet`) e exclua a pasta do antivírus, se possível.
- **Docker: dependências desatualizadas dentro do container:** rode `docker compose down`, depois `docker compose build --no-cache` e `docker compose up -d`.
- **Docker: erro de permissão em `storage` ou `bootstrap/cache`:** dentro do container, rode `chmod -R 775 storage bootstrap/cache`.
- **Quero recomeçar do zero:** `php artisan migrate:fresh --seed`.
