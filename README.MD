# php-postgres-api (Slim 4 + Postgres + Docker) — v0.2

Backend API in PHP 8.4 (Slim 4). PostgreSQL storage. Dockerized with nginx + php-fpm. Background worker container.

## Stack
- PHP 8.4, Slim 4, PDO (pgsql)
- PostgreSQL 16
- nginx
- Monolog
- Docker Compose

## Features
- `/tasks` CRUD with type-safe SQL binds
- Health endpoint
- Background worker (periodic DB writes; demo)
- JSON logging to stdout (+ optional file via `LOG_PATH`)
- Migrations auto-run on first DB init

## Project Layout
```
.
├─ docker/                    # Dockerfiles + nginx.conf
│  ├─ php-cli.Dockerfile
│  ├─ php-fpm.Dockerfile
│  └─ nginx.conf
├─ public/
│  └─ index.php               # Slim bootstrap + routes wiring
├─ src/
│  ├─ Db.php
│  ├─ Middleware/
│  │  └─ RequestLogger.php
│  └─ Routes/
│     └─ tasks.php
├─ src/migrations/001_init.sql  # migration (adjust compose if moved)
├─ worker.php
├─ .env
├─ docker-compose.yml
├─ composer.json
└─ Makefile
```

---

## Quick start (Docker)

```bash
# 1) deps
composer install

# 2) env
cp .env .env.local 2>/dev/null || true
# Ensure .env has DB_* and LOG_PATH variables (see next section)

# 3) up
make up    # or: docker compose up -d --build

# 4) smoke
curl -s http://localhost:8080/health
curl -s -X POST http://localhost:8080/tasks -H 'Content-Type: application/json' -d '{"title":"first"}'
curl -s http://localhost:8080/tasks | jq .
```

DB migrations run automatically on first init via the mounted `src/migrations/` directory.  
If you moved migrations to `db/migrations/`, update the mount path in `docker-compose.yml`.

---

## Environment (.env)

```
DB_DSN="pgsql:host=postgres;port=5432;dbname=app"
DB_USER=app
DB_PASS=app

# Optional. If writable, logs also go to this file. Otherwise stdout only.
LOG_PATH=/tmp/app.log
```

Compose passes these to `php-fpm` and `worker`. Adjust for local dev without Docker as needed.

---

## Makefile targets

```makefile
make up       # compose up -d --build
make down     # compose down
make logs     # tail php-fpm logs
make ps       # compose ps
make psql     # psql into DB (user=app db=app)
make seed     # re-run initial SQL (idempotent)
make curl     # GET /health
make list     # GET /tasks
```

---

## API

### Health
```
GET /health
```

### Tasks
```
GET    /tasks
GET    /tasks/{id}
POST   /tasks            {"title":"...", "done":false}
PUT    /tasks/{id}       {"title":"...", "done":true}
DELETE /tasks/{id}
```

### cURL examples
```bash
curl -s http://localhost:8080/tasks | jq .

curl -s -X POST http://localhost:8080/tasks   -H 'Content-Type: application/json'   -d '{"title":"read docs"}' | jq .

curl -s -X PUT http://localhost:8080/tasks/1   -H 'Content-Type: application/json'   -d '{"done":true}' | jq .

curl -s -X DELETE http://localhost:8080/tasks/1 | jq .
```

Responses are JSON. Headers include `X-Request-Id` and `X-Response-Time`.

---

## Worker

`worker.php` runs in a separate container. It shows DB connectivity and background processing.  
Comment out the demo `INSERT` if you don’t want periodic rows while testing, then:

```bash
docker compose restart worker
```

---

## Logging

- Always to stdout.
- Optional file handler if `LOG_PATH` is writable.  
  Example: `LOG_PATH=/tmp/app.log`.

Check logs:
```bash
docker compose logs -f php-fpm
```

---

## Local dev without Docker (optional)

```bash
export DB_DSN='pgsql:host=localhost;port=5432;dbname=app'
export DB_USER=app
export DB_PASS=app
php -S localhost:8080 -t public
```

Run migration manually:
```bash
psql postgresql://app:app@localhost:5432/app -f src/migrations/001_init.sql
```

---

## Troubleshooting

- **`could not find driver`**  
  Rebuild images. Ensure `pdo_pgsql` compiled in `php-fpm.Dockerfile` and `php-cli.Dockerfile`.

- **`connection refused` to postgres**  
  Compose healthcheck waits for DB. Also `Db::pdo()` retries. Verify with:
  ```bash
  docker compose ps
  docker compose logs postgres
  ```

- **`relation "tasks" does not exist`**  
  Migration didn’t run. Either `down -v` and `up` to re-init, or:
  ```bash
  docker compose exec -T postgres psql -U app -d app -f /docker-entrypoint-initdb.d/001_init.sql
  ```

- **`logs/app.log permission denied`**  
  Use stdout only or set `LOG_PATH=/tmp/app.log`.

---

## Roadmap
- v0.3: pagination + query params on `/tasks` (`limit`, `offset`, `done`, `q`)
- v0.4: auth stub + request validation
- v0.5: CI (lint + phpunit) and Postman/REST collection

---

## Licence
MIT (adjust if needed)

---

## Résumé (FR-CA)

- API Slim 4 avec PostgreSQL.  
- Docker Compose: nginx, php-fpm, worker, postgres.  
- Endpoints CRUD `/tasks`, santé `/health`.  
- Logs JSON vers stdout (+ fichier si `LOG_PATH` est accessible).  
- Démarrage rapide: `composer install`, `make up`, `curl /health`.  
- Migrations auto via `src/migrations/001_init.sql`.  

---

### Repo metadata (JSON)

```json
{
  "name": "php-postgres-api",
  "version": "0.2.0",
  "services": ["nginx", "php-fpm", "postgres", "worker"],
  "endpoints": ["/health", "/tasks", "/tasks/{id}"],
  "env": ["DB_DSN", "DB_USER", "DB_PASS", "LOG_PATH"],
  "docker": true,
  "migrations": "src/migrations/001_init.sql"
}
```
