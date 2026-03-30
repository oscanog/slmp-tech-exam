# SLMP 01

Laravel 12 API application that imports data from JSONPlaceholder into MySQL and exposes a JSON REST API protected by Laravel Passport.

## Setup Guide

### Host Setup

Run these commands from the project root:

```bash
composer install
cp .env.example .env
php artisan key:generate --force
php artisan migrate --force
php artisan slmp:import-jsonplaceholder
php artisan serve
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

The app is database-driver agnostic. Local verification on this machine used SQLite, while Docker can still be wired to MySQL through environment variables when the Docker assets are present.

### Docker Note

Docker is available only in the local working copy right now. The Docker files are intentionally ignored by Git, so a clean clone of the committed repo will not include them until they are added back to version control.

## Import Command

Run the importer with:

```bash
php artisan slmp:import-jsonplaceholder
```

The command entrypoint is `slmp:import-jsonplaceholder`.

## Eloquent Verification

The JSONPlaceholder import writes data with Eloquent model operations in the import service, including:

- `User::create(...)`
- `$record->fill(...)`
- `$record->save()`
- `$modelClass::create(...)`

No raw SQL inserts are used for the resource import flow.

## Authentication Scheme

Authentication is handled with Laravel Passport bearer tokens. Log in through the API and send `Authorization: Bearer <token>` on protected requests.

## Authentication Guide

1. Register a user with `POST /api/auth/register`, or log in with `POST /api/auth/login`.
2. Copy the returned bearer token from the login response.
3. Send `Authorization: Bearer <token>` on protected endpoints.
4. Use `GET /api/auth/me` to verify the authenticated user.

## REST Client Testing

All endpoints are JSON-based and can be tested with Postman, Insomnia, curl, or similar REST clients.

1. Register or log in to get a bearer token.
2. Send `Authorization: Bearer <token>` on protected requests.
3. Import the ready-made Postman collection from `docs/postman/slmp_01.postman_collection.json`.

## Authentication Endpoints

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `POST /api/auth/logout`
- `GET /api/auth/me`

## Protected Resource Endpoints

- `/api/users`
- `/api/posts`
- `/api/comments`
- `/api/albums`
- `/api/photos`
- `/api/todos`

Collection requests include `GET`, `POST`, `PUT`, `PATCH`, and `DELETE` for every resource endpoint.

## Verification Snapshot

- `composer install` works once Composer is available on the machine.
- `php artisan migrate --force` works on the local SQLite setup.
- `php artisan slmp:import-jsonplaceholder` completes successfully.
- `php artisan test` passes.
