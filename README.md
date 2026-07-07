# Clarus Backend

A PHP backend built with [utopia-php/platform](https://github.com/utopia-php/platform) and [utopia-php/http](https://github.com/utopia-php/http), running on Swoole with PostgreSQL and Redis.

Architecture follows the same **Platform → Modules → Services → Actions** pattern used by [Appwrite](https://github.com/appwrite/appwrite).

## Stack

- **PHP 8.4** + **Swoole** — persistent HTTP server
- **utopia-php/platform** — modular route/worker/task registration
- **utopia-php/database** — document store over PostgreSQL
- **PostgreSQL 17 + PostGIS** — required by utopia/database Postgres adapter
- **Redis 7** — cache layer for utopia/database
- **Docker** — containerised development and production

---

## Project structure

```
.
├── app/
│   ├── http.php                 # HTTP entry point
│   ├── init.php                 # Bootstrap
│   ├── init/
│   │   ├── constants.php
│   │   ├── configs.php
│   │   ├── registers.php
│   │   ├── models.php           # Response model registration
│   │   └── resources.php        # DI container (db, cache, redis)
│   ├── config/
│   │   ├── collections.php      # Database schema
│   │   └── errors.php           # Error type metadata
│   └── controllers/
│       └── general.php          # Middleware + platform init
├── src/Clarus/
│   ├── Platform/
│   │   ├── Application.php      # Registers modules
│   │   └── Modules/
│   │       ├── Core.php
│   │       ├── Health/
│   │       └── Todos/           # One Action class per endpoint
│   ├── Utopia/
│   │   ├── Response.php
│   │   └── Server.php
│   ├── Database/
│   │   ├── Setup.php
│   │   ├── Migration.php
│   │   ├── MigrationRegistry.php
│   │   ├── Migrator.php
│   │   └── Migrations/
│   └── Extend/
│       └── Exception.php
├── dev/watch.sh
├── Dockerfile
└── docker-compose.yml
```

---

## API

| Method | Path | Description |
|--------|------|-------------|
| GET | `/v1/health` | Health check |
| GET | `/v1/todos` | List todos (`?completed=true&limit=25&offset=0`) |
| POST | `/v1/todos` | Create todo |
| GET | `/v1/todos/:todoId` | Get todo |
| PATCH | `/v1/todos/:todoId` | Update todo |
| DELETE | `/v1/todos/:todoId` | Delete todo |

### Create todo

```bash
curl -X POST http://localhost:8080/v1/todos \
  -H 'Content-Type: application/json' \
  -d '{"title":"Buy milk","description":"2% organic"}'
```

### List todos

```bash
curl 'http://localhost:8080/v1/todos?completed=false&limit=10'
```

---

## Development

### Prerequisites

- [Podman](https://podman.io/) with [podman-compose](https://github.com/containers/podman-compose), **or** Docker with Compose v2

> Use **`podman-compose`** (not `podman compose`) on this setup — the latter delegates to the legacy `docker-compose` shim and may fail.
>
> The app container uses `network_mode: host` so it can reach Postgres on `127.0.0.1:5432` without container DNS (Podman here lacks `aardvark-dns`).

### Start

```bash
# Podman (recommended here)
podman-compose up --build

# Docker
docker compose up --build
```

| Service  | URL / Port          |
|----------|---------------------|
| App      | http://localhost:8080 |
| Postgres | localhost:5432      |

Redis runs on the internal compose network only (no host port). Development uses an in-memory cache adapter so Podman works without container DNS (`aardvark-dns`).

Edit any `.php` file under `src/` or `app/` — the dev watcher reloads Swoole automatically.

### Database migrations

Run pending forward-only database migrations with:

```bash
podman-compose exec app php app/migrate.php
```

See [`docs/MIGRATIONS.md`](docs/MIGRATIONS.md) for how boot setup, schema config, and migrations fit together.

### Stop

```bash
podman-compose down
```

Wipe database volume (required after changing `DB_USER` / `DB_NAME` defaults):

```bash
podman-compose down -v
```

Old volumes initialized with previous credentials (e.g. `utopia`) will not pick up new defaults automatically.

---

## Environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | `postgres` | Postgres hostname |
| `DB_PORT` | `5432` | Postgres port |
| `DB_NAME` | `clarus` | Database name |
| `DB_USER` | `clarus` | Database user |
| `DB_PASSWORD` | `secret` | Database password |
| `DB_NAMESPACE` | `clarus` | utopia/database namespace prefix |
| `REDIS_HOST` | `redis` | Redis hostname (production cache) |
| `REDIS_PORT` | `6379` | Redis port |
| `_APP_CACHE_ADAPTER` | `redis` | Set to `memory` to skip Redis |
| `_APP_ENV` | `production` | `development` uses in-memory cache |

---

## Production

```bash
docker build --target production -t clarus-backend:latest .
```
