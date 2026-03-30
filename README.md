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
docker compose exec app php artisan slmp:runtime-check
```

App URL: `http://localhost:8080`
`--wait` makes Docker return only after the app bootstrap is ready.
`slmp:runtime-check` verifies imported counts, health, register, login, `me`, protected posts access, logout, and revoked-token behavior against the live API.

## Run Without Docker

```bash
composer install
cp .env.example .env
php artisan key:generate --force
php artisan migrate --force
php artisan slmp:import-jsonplaceholder
php artisan serve --host=127.0.0.1 --port=8080
php artisan slmp:runtime-check
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

## Import Command

```bash
php artisan slmp:import-jsonplaceholder
```

## Runtime Check

```bash
php artisan slmp:runtime-check
```

This is the quick live proof command for both Docker and non-Docker runs.
It checks imported row counts, API health, auth flow, one protected resource read, logout, and token revocation.

## Auth

Minimal auth endpoints:

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`

Use the bearer token returned by `login` on protected requests:

`Authorization: Bearer <token>`

## REST Client

Use Postman, Insomnia, curl, or any REST client.

Postman collection: `docs/postman/slmp_01.postman_collection.json`
