# Database Migrations

This project uses a lightweight, Appwrite-inspired migration system on top of `utopia-php/database`.

Migrations are explicit PHP classes, forward-only, and run through:

```bash
php app/migrate.php
```

In containerized development, run:

```bash
podman-compose exec app php app/migrate.php
```

## Boot setup vs migrations

There are two separate database flows:

| Flow | Entry point | Purpose |
|------|-------------|---------|
| Boot setup | `Setup::run($db)` | Create the database metadata and missing base collections |
| Migrations | `php app/migrate.php` | Apply explicit schema/data changes to existing databases |

Boot setup is still responsible for first-run initialization. It reads `app/config/collections.php` and creates missing collections.

Migrations are responsible for changes after a collection already exists, such as adding attributes, adding indexes, renaming attributes, deleting indexes, or backfilling documents.

## Why both `collections.php` and migrations are needed

`app/config/collections.php` describes the desired schema for fresh databases.

Migration classes describe how to move an existing database from an older schema to the newer schema.

When changing schema, usually update both:

1. `app/config/collections.php`
2. Add a migration class under `src/Clarus/Database/Migrations/`
3. Register the migration in `src/Clarus/Database/MigrationRegistry.php`

If only `collections.php` is changed, existing collections are not updated because boot setup skips collections that already exist.

## Files

| File | Role |
|------|------|
| `app/migrate.php` | CLI entrypoint for running pending migrations |
| `src/Clarus/Database/Migration.php` | Migration interface |
| `src/Clarus/Database/Migrator.php` | Runs pending migrations and records successful ones |
| `src/Clarus/Database/MigrationRegistry.php` | Explicit ordered list of migrations |
| `src/Clarus/Database/Migrations/` | Directory for migration classes |
| `app/config/collections.php` | Includes the `migrations` tracking collection |

## Migration tracking

Applied migrations are stored in the `migrations` collection.

Each migration is recorded using the migration ID as the document ID:

```json
{
  "$id": "202607070001_add_todo_priority",
  "name": "Add priority to todos",
  "appliedAt": "2026-07-07T12:00:00+00:00"
}
```

A migration is recorded only after `execute()` finishes successfully.

If a migration fails, the runner stops immediately and exits with a non-zero status. The failed migration is not marked as applied.

## Creating a migration

Create a class under `src/Clarus/Database/Migrations/`:

```php
<?php

namespace Clarus\Database\Migrations;

use Clarus\Database\Migration;
use Utopia\Database\Database;

final class M202607070001AddTodoPriority implements Migration
{
    public function getId(): string
    {
        return '202607070001_add_todo_priority';
    }

    public function getName(): string
    {
        return 'Add priority to todos';
    }

    public function execute(Database $db): void
    {
        $db->createAttribute(
            collection: 'todos',
            id: 'priority',
            type: Database::VAR_STRING,
            size: 32,
            required: false,
            default: 'normal'
        );
    }
}
```

Then register it in order:

```php
// src/Clarus/Database/MigrationRegistry.php

use Clarus\Database\Migrations\M202607070001AddTodoPriority;

public static function all(): array
{
    return [
        new M202607070001AddTodoPriority(),
    ];
}
```

## Running migrations

Local container:

```bash
podman-compose exec app php app/migrate.php
```

Direct PHP, if local PHP has the required extensions and DB access:

```bash
php app/migrate.php
```

Expected output when there are no migrations:

```text
Starting migrations...
No pending migrations.
Migration completed.
```

Expected output when a migration runs:

```text
Starting migrations...
Running 202607070001_add_todo_priority: Add priority to todos
Done 202607070001_add_todo_priority
Migration completed.
```

## Rollback policy

Rollback is intentionally not implemented in v1.

This follows the Appwrite-style forward migration approach. Many schema/data migrations are not safely reversible. For production rollback, take a database backup before running migrations. If needed, restore the backup or write a corrective forward migration.

## Operational notes

- Run migrations before deploying code that depends on the new schema.
- Do not run migrations from request handlers.
- Do not rely on HTTP boot setup to update existing collections.
- Keep migration IDs unique and ordered, preferably timestamp-prefixed.
- Keep each migration focused and safe to retry when possible.
