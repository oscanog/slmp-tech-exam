# API Routes

Base URL:

- Docker: `http://localhost:8080`
- Non-Docker: `http://localhost:8080`

Auth:

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`

System:

- `GET /api/health`

Protected resources:

- `GET /api/users`
- `GET /api/users/{id}`
- `POST /api/users`
- `PUT /api/users/{id}`
- `PATCH /api/users/{id}`
- `DELETE /api/users/{id}`
- `GET /api/posts`
- `GET /api/posts/{id}`
- `POST /api/posts`
- `PUT /api/posts/{id}`
- `PATCH /api/posts/{id}`
- `DELETE /api/posts/{id}`
- `GET /api/comments`
- `GET /api/comments/{id}`
- `POST /api/comments`
- `PUT /api/comments/{id}`
- `PATCH /api/comments/{id}`
- `DELETE /api/comments/{id}`
- `GET /api/albums`
- `GET /api/albums/{id}`
- `POST /api/albums`
- `PUT /api/albums/{id}`
- `PATCH /api/albums/{id}`
- `DELETE /api/albums/{id}`
- `GET /api/photos`
- `GET /api/photos/{id}`
- `POST /api/photos`
- `PUT /api/photos/{id}`
- `PATCH /api/photos/{id}`
- `DELETE /api/photos/{id}`
- `GET /api/todos`
- `GET /api/todos/{id}`
- `POST /api/todos`
- `PUT /api/todos/{id}`
- `PATCH /api/todos/{id}`
- `DELETE /api/todos/{id}`

Auth header for protected routes:

```text
Authorization: Bearer <token>
```

Scopes:

- read routes require `resources:read`
- write routes require `resources:write`

Notes:

- `register` and `login` are public
- `logout` and `me` require authentication
- resource list routes are paginated
- imported rows keep `source_id` as read-only metadata
