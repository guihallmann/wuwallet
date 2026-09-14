# WuWallet

WuWallet is a Laravel application with an Inertia React frontend. The recommended development setup uses Laravel Sail, so contributors can run the same PHP and Node versions without installing PHP, Composer, or Node locally.

## Requirements

Install these tools first:

- Git
- Docker Desktop
- On Windows, enable the WSL 2 engine in Docker Desktop and install Ubuntu through WSL 2

Windows contributors should run the commands below from the Ubuntu/WSL terminal, not from PowerShell or Command Prompt. Clone the project inside the WSL filesystem, such as `~/projects`, for better Docker performance.

macOS and Linux users can run the same commands from a terminal with Docker running.

## First-time setup

1. Clone the repository and enter it:

   ```bash
   git clone <repository-url> wuwallet
   cd wuwallet
   ```

2. Install the PHP dependencies. This bootstrap command uses Docker, so local PHP and Composer are not required:

   ```bash
   docker run --rm \
     -u "$(id -u):$(id -g)" \
     -v "$PWD:/app" \
     -w /app \
     composer:2 composer install
   ```

3. Create the local environment file:

   ```bash
   cp .env.example .env
   ```

   If your WSL user does not use UID/GID `1000`, update `WWWUSER` and `WWWGROUP` in `.env` to the values printed by `id -u` and `id -g`.

4. Build and start Sail in the background:

   ```bash
   ./vendor/bin/sail build
   ./vendor/bin/sail up -d
   ```

5. Generate the application key, create the SQLite database, and build the frontend:

   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run build
   ```

6. Open [http://localhost:8000](http://localhost:8000).

The seeded test account is `test@example.com` with the password `password`.

## Daily commands

Start the application:

```bash
./vendor/bin/sail up -d
```

Stop the application:

```bash
./vendor/bin/sail stop
```

Run the development server with frontend hot reload:

```bash
./vendor/bin/sail npm run dev -- --host 0.0.0.0
```

Run Artisan commands:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

Run tests and checks:

```bash
./vendor/bin/sail test
./vendor/bin/sail composer run lint:check
./vendor/bin/sail composer run types:check
```

View container status or logs:

```bash
./vendor/bin/sail ps
./vendor/bin/sail logs -f laravel.test
```

## Database

The default development database is SQLite. No MySQL or PostgreSQL installation is needed. The database file is created locally by Laravel and is ignored by Git. Tests use an in-memory SQLite database.

## Troubleshooting

- **Docker is not running:** Start Docker Desktop and wait until it reports that Docker is running.
- **`./vendor/bin/sail: Permission denied`:** Run `chmod +x vendor/bin/sail` from WSL.
- **Port 8000 is busy:** Change `APP_PORT` in `.env`, for example to `8001`, then run `./vendor/bin/sail up -d` again.
- **Windows filesystem is slow:** Move the repository from `/mnt/c/...` into your WSL home directory, such as `~/projects/wuwallet`.
- **Containers have stale dependencies:** Run `./vendor/bin/sail down`, then `./vendor/bin/sail build --no-cache` and `./vendor/bin/sail up -d`.

## Optional native setup

Sail is the recommended path. Alternatively, install PHP 8.4+, Composer, Node.js, and npm locally, then use the usual Laravel commands such as `composer install`, `php artisan migrate`, and `npm run dev`.
