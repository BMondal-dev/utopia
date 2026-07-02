# Utopia Backend

A PHP backend built with [utopia-php/http](https://github.com/utopia-php/http) running on Swoole, with PostgreSQL.

## Stack

- **PHP 8.4** + **Swoole** — persistent HTTP server (no FPM, no Apache)
- **utopia-php/http** — lightweight routing framework
- **PostgreSQL 17** — primary database
- **Docker** — containerised development and production

---

## Why hot reload is different with Swoole

Traditional PHP (FPM/Apache) boots a fresh process per request, so saving a file is enough — the next request picks up the new code automatically.

Swoole is different. It starts **once** and stays running in memory. Edited files on disk have no effect until the process restarts. This is what makes Swoole fast in production, but it means development needs an extra step.

### How it works here

```
┌──────────────────────────────────────────┐
│  Docker container (development target)   │
│                                          │
│  watch.sh                                │
│    ├─ starts: php app/http.php (Swoole)  │
│    └─ inotifywait watches src/ & app/   │
│         └─ .php file saved?              │
│              ├─ kill Swoole process      │
│              └─ restart php app/http.php │
└──────────────────────────────────────────┘
         ▲ volume mount ▲
   your editor on the host
```

1. **Volume mounts** in `docker-compose.yml` make host files visible inside the container instantly — no rebuild needed.
2. **`inotifywait`** (from `inotify-tools`) watches `src/` and `app/` for any `.php` file change.
3. **`dev/watch.sh`** kills the running Swoole process and starts it again the moment a change is detected.

The Dockerfile uses a **multi-stage build** so the watcher is only present in the `development` target and never ships to production.

---

## Development

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) with Compose v2, **or** Podman with `podman-compose`

> **Using Podman?** Install `podman-compose` (`sudo apt install podman-compose`) and replace every `docker compose` command below with `podman-compose`.

### Start

```bash
# Docker
docker compose up --build

# Podman
podman-compose up --build
```

The first build takes a minute (downloads images, compiles `pdo_pgsql`). Subsequent starts are fast.

| Service  | URL / Port          |
|----------|---------------------|
| App      | http://localhost    |
| Postgres | localhost:5432      |

### Hot reload in action

Edit any `.php` file under `src/` or `app/` and save. You will see in the logs:

```
app-1  | [watch] change detected — reloading...
app-1  | [watch] starting server...
```

The server is back up within a second.

### Logs

```bash
docker compose logs -f app
```

### Stop

```bash
docker compose down
```

To also wipe the database volume:

```bash
docker compose down -v
```

---

## Production

Build the `production` target — no watcher, no dev tools:

```bash
docker build --target production -t utopia-backend:latest .
```

---

## Environment variables

| Variable      | Default   | Description          |
|---------------|-----------|----------------------|
| `DB_HOST`     | `postgres`| Postgres hostname    |
| `DB_PORT`     | `5432`    | Postgres port        |
| `DB_NAME`     | `utopia`  | Database name        |
| `DB_USER`     | `utopia`  | Database user        |
| `DB_PASSWORD` | `secret`  | Database password    |

Override them by creating a `.env` file next to `docker-compose.yml`:

```env
DB_NAME=myapp
DB_USER=myuser
DB_PASSWORD=strongpassword
```

---

## Project structure

```
.
├── app/
│   └── http.php          # Entry point — routes defined here
├── src/                  # Your application code (PSR-4 autoloaded)
├── dev/
│   └── watch.sh          # Hot-reload watcher (development only)
├── Dockerfile
├── docker-compose.yml
└── composer.json
```
