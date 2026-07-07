# Database Migrations

> For how tenant-scoped collections, the `_tenant` column, and the
> `db` / `dbForTenant` handles work, see [`docs/MULTITENANCY.md`](./MULTITENANCY.md).

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
| `src/Clarus/Database/Concerns/UsesCollectionConfig.php` | Helper trait for creating attributes/indexes from `collections.php` definitions |
| `src/Clarus/Database/Concerns/IteratesDocuments.php` | Helper trait for document backfills and transformations |
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

## Migration naming convention

Use one naming format for every migration:

```text
File/class: M{YYYYMMDDHHMMSS}{PascalCaseName}.php
ID:         {YYYYMMDDHHMMSS}_{snake_case_name}
```

Example:

```text
File:  src/Clarus/Database/Migrations/M20260707064230AddTodoPriority.php
Class: M20260707064230AddTodoPriority
ID:    20260707064230_add_todo_priority
```

Use a UTC timestamp when creating the migration. Keep migrations registered in ascending timestamp order in `MigrationRegistry`.

## Creating a migration

Create a class under `src/Clarus/Database/Migrations/`:

```php
<?php

namespace Clarus\Database\Migrations;

use Clarus\Database\Concerns\UsesCollectionConfig;
use Clarus\Database\Migration;
use Utopia\Database\Database;

final class M20260707064230AddTodoPriority implements Migration
{
    use UsesCollectionConfig;

    public function getId(): string
    {
        return '20260707064230_add_todo_priority';
    }

    public function getName(): string
    {
        return 'Add priority to todos';
    }

    public function execute(Database $db): void
    {
        $this->createAttributeFromCollection($db, 'todos', 'priority');
        $this->createIndexFromCollection($db, 'todos', '_key_priority');
    }
}
```

The helper trait reads attribute and index definitions from `app/config/collections.php`, so migrations and fresh-install schema stay aligned. If the migration creates an attribute/index on one collection using another collection's config definition, pass the source collection as the fourth argument:

```php
$this->createAttributeFromCollection($db, 'targetCollection', 'attributeId', 'sourceCollection');
$this->createIndexFromCollection($db, 'targetCollection', 'indexId', 'sourceCollection');
```

Then register it in order:

```php
// src/Clarus/Database/MigrationRegistry.php

use Clarus\Database\Migrations\M20260707064230AddTodoPriority;

public static function all(): array
{
    return [
        new M20260707064230AddTodoPriority(),
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

The default command is `run`. This is equivalent:

```bash
php app/migrate.php run
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
Running 20260707064230_add_todo_priority: Add priority to todos
Done 20260707064230_add_todo_priority
Migration completed.
```

## Checking migration status

Use the `status` command to see registered migrations grouped by applied and pending state:

```bash
podman-compose exec app php app/migrate.php status
```

Direct PHP:

```bash
php app/migrate.php status
```

Example output:

```text
Applied:
- 20260707064230_add_todo_priority (2026-07-07T12:00:00+00:00) Add priority to todos

Pending:
- 202607080001_add_todo_due_at Add due date to todos
```

If a group is empty, status prints `- none`.

## Schema change patterns

Follow the Appwrite-style migration pattern:

| Change type | Preferred migration style |
|-------------|---------------------------|
| Add attribute from `collections.php` | `createAttributeFromCollection()` |
| Add multiple attributes from `collections.php` | `createAttributesFromCollection()` |
| Add index from `collections.php` | `createIndexFromCollection()` |
| Change existing attribute type/size/required/signed/array key | call Utopia DB's `updateAttribute()` directly |
| Change existing attribute default | call Utopia DB's `updateAttributeDefault()` directly |
| Rename attribute | call Utopia DB's `renameAttribute()` directly |
| Delete attribute | call Utopia DB's `deleteAttribute()` directly |
| Rename index | call Utopia DB's `renameIndex()` directly |
| Delete index | call Utopia DB's `deleteIndex()` directly |
| Backfill or transform documents in one collection | use `forEachDocumentInCollection()` |
| Backfill or transform documents across configured top-level collections | use `forEachDocument()` |

`UsesCollectionConfig` is intentionally scoped to creating config-defined attributes and indexes. Updates, renames, and deletes should be explicit in the migration class so the migration describes the operational change clearly. Document backfills can use `IteratesDocuments` to avoid repeating iteration/update boilerplate.

Example required-field update:

```php
final class M202607080001MakeTodoPriorityOptional implements Migration
{
    public function getId(): string
    {
        return '202607080001_make_todo_priority_optional';
    }

    public function getName(): string
    {
        return 'Make todo priority optional';
    }

    public function execute(Database $db): void
    {
        $db->updateAttribute('todos', 'priority', required: false);
    }
}
```

For non-required to required changes, backfill existing documents first, then update the attribute:

```php
use Clarus\Database\Concerns\IteratesDocuments;

final class M202607080002RequireTodoPriority implements Migration
{
    use IteratesDocuments;

    public function execute(Database $db): void
    {
        $this->forEachDocumentInCollection($db, 'todos', function (Document $todo): ?Document {
            if ($todo->getAttribute('priority') !== null) {
                return null;
            }

            $todo->setAttribute('priority', 'normal');
            return $todo;
        });

        $db->updateAttribute('todos', 'priority', required: true);
    }
}
```

`forEachDocumentInCollection()` compares each document before/after the callback and only persists changed documents. Return `null` when no update is needed.

Also update `app/config/collections.php` to reflect the final desired schema for fresh databases.

## Rollback policy

Rollback is intentionally not implemented in v1.

This follows the Appwrite-style forward migration approach. Many schema/data migrations are not safely reversible. For production rollback, take a database backup before running migrations. If needed, restore the backup or write a corrective forward migration.

## Operational notes

- Run migrations before deploying code that depends on the new schema.
- Do not run migrations from request handlers.
- Do not rely on HTTP boot setup to update existing collections.
- Keep migration IDs unique and ordered using `YYYYMMDDHHMMSS_snake_case_name`.
- Keep each migration focused and safe to retry when possible.
