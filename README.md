# SLMP 01

Laravel 12 API application that imports data from JSONPlaceholder into MySQL and exposes a JSON REST API protected by Laravel Passport.

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
