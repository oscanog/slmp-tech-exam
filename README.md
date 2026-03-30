# SLMP Tech Exam passed by Melvin Nogoy

Laravel 12 API that imports JSONPlaceholder "https://jsonplaceholder.typicode.com/" data into the database with Eloquent and exposes a Passport-protected REST API.

## Clone

```bash
git clone https://github.com/oscanog/slmp-tech-exam.git slmp_01
cd slmp_01
```

## Run With Docker

```bash
docker compose build
docker compose up -d --wait
docker compose exec app php artisan slmp:import-jsonplaceholder
```

App URL: `http://localhost:8080`
`--wait` makes Docker return only after the app bootstrap is ready.

## Run Without Docker

```bash
composer install
cp .env.example .env
php artisan key:generate --force
php artisan migrate --force
php artisan slmp:import-jsonplaceholder
php artisan serve --host=127.0.0.1 --port=8080
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
