# SLMP 01

Laravel 12 API that imports JSONPlaceholder data into the database with Eloquent and exposes a Passport-protected REST API.

## Clone

```bash
git clone <your-repo-url> slmp_01
cd slmp_01
```

## Run With Docker

```bash
docker compose build
docker compose up -d
docker compose exec app php artisan slmp:import-jsonplaceholder
```

App URL: `http://localhost:8080`
On first boot, wait a few seconds for MySQL, migrations, and Passport setup to finish before opening the app.

## Run Without Docker

```bash
composer install
cp .env.example .env
php artisan key:generate --force
php artisan migrate --force
php artisan slmp:import-jsonplaceholder
php artisan serve
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

## Import Command

```bash
php artisan slmp:import-jsonplaceholder
```

## Auth

1. `POST /api/auth/register` or `POST /api/auth/login`
2. Copy the returned bearer token
3. Send `Authorization: Bearer <token>` on protected requests

## REST Client

Use Postman, Insomnia, curl, or any REST client.

Postman collection: `docs/postman/slmp_01.postman_collection.json`
