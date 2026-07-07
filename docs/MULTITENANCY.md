# Multitenancy

This project runs many tenants (organizations) out of a **single Postgres
database**, following the same approach Appwrite uses for projects.

Isolation is achieved with **shared tables + a `_tenant` column**, not a
table or schema per tenant. Every tenant-scoped row lives in the same
physical table (for example `clarus_todos`) and carries the sequence of the
tenant that owns it.

- [Two database handles](#two-database-handles)
- [The `Clarus\Database\Factory`](#the-clarusdatabasefactory)
- [Which tables get a `_tenant` column](#which-tables-get-a-_tenant-column)
- [The `_metadata` table](#the-_metadata-table)
- [Request lifecycle](#request-lifecycle)
- [Route protection: `auth` and `roles`](#route-protection-auth-and-roles)
- [Document permissions](#document-permissions)
- [Adding a tenant-scoped collection](#adding-a-tenant-scoped-collection)
- [Boot setup vs migrations for tenancy](#boot-setup-vs-migrations-for-tenancy)
- [Troubleshooting](#troubleshooting)

## Two database handles

Each request has access to two `Utopia\Database\Database` instances. They
share the same pooled PDO connection but are configured differently.

| Handle | Shared tables | Tenant | Holds |
|--------|---------------|--------|-------|
| `db` | off | none | Global/account data: `users`, `tenants`, `memberships`, `sessions`, `identities`, `tokens`, `migrations` |
| `dbForTenant` | on | active tenant | Business data scoped to one org: `todos` (and any future `tenant => true` collection) |

Rules of thumb:

- Anything about **who the caller is** or **which orgs exist** uses `db`.
- Anything **owned by a specific org** uses `dbForTenant`.

`db` never applies a `_tenant` filter. `dbForTenant` automatically adds
`AND (_tenant IN (:tenant) ...)` to every query once a tenant is set, so
tenant A can never read tenant B's rows.

Both handles are wired up per request in
`src/Clarus/Utopia/Server.php`.

## The `Clarus\Database\Factory`

`src/Clarus/Database/Factory.php` centralizes how the two handles are built,
mirroring Appwrite's `Appwrite\Database\Factory` (`platform()` vs
`project()`):

```php
// Global handle: no tenant partitioning.
DatabaseFactory::platform($database, $authorization, $cache);

// Tenant handle: shared tables, with global collection schemas registered.
DatabaseFactory::forTenant($database, $authorization, $cache);
```

`forTenant()` calls `setGlobalCollections(Factory::globalCollectionIds())`.
`globalCollectionIds()` returns every collection in
`app/config/collections.php` that is **not** marked `tenant => true`. In
utopia-php/database this list controls the metadata **cache key** for those
schemas, so schema reads for global collections are not keyed per tenant.

The factory is used in three places so configuration never drifts:

| Location | Handle |
|----------|--------|
| `app/init/resources.php` | boot/CLI `db` (used by `Setup::run` and migrations) |
| `src/Clarus/Utopia/Server.php` | per-request `db` |
| `src/Clarus/Utopia/Server.php` | per-request `dbForTenant` |

The active tenant is **not** set by the factory. It is applied later in the
request once the tenant has been resolved (see
[Request lifecycle](#request-lifecycle)).

## Which tables get a `_tenant` column

A physical table gets a `_tenant` column **only if it is created with shared
tables enabled**. This is decided per collection, not globally.

| Table | `_tenant`? | Reason |
|-------|-----------|--------|
| `clarus_users` | no | global collection |
| `clarus_tenants` | no | global collection |
| `clarus_memberships` | no | global collection |
| `clarus_sessions` | no | global collection |
| `clarus_todos` | **yes** | `tenant => true` in config |
| `clarus_todos_perms` | **yes** | permission table for a tenant-scoped collection |
| `clarus__metadata` | **yes** | internal schema table (see below) |

The toggle lives in
`src/Clarus/Database/Concerns/UsesCollectionConfig.php`:

```php
$isTenantScoped = (bool) ($collection['tenant'] ?? false);

try {
    $database->setSharedTables($isTenantScoped);
    $database->createCollection($collectionId, $attributes, $indexes);
} finally {
    $database->setSharedTables($wasSharedTables);
}
```

So a new collection is global unless its definition in
`app/config/collections.php` sets `"tenant" => true`.

### Two different `_tenant` columns, two different jobs

`_tenant` shows up in two roles that should not be confused:

- On a **data table** (`clarus_todos`) and its **perms table**
  (`clarus_todos_perms`): identifies **which org owns the row / permission**.
  This is what enforces tenant isolation.
- The **permissions** (`_perms`) table itself answers a different question:
  **which roles may read/update/delete each document** (document-level ACL).
  See [Document permissions](#document-permissions).

Tenant isolation (`_tenant`) and document ACL (`_perms`) are independent
layers and both apply to tenant-scoped collections.

## The `_metadata` table

`clarus__metadata` is a single internal table where utopia-php/database
stores the schema of every collection. There is exactly one of them for the
whole database.

Because `dbForTenant` reads schemas with shared tables enabled, utopia adds a
`_tenant` predicate to metadata reads as well:

```sql
AND ("main"._tenant IN (:_tenant) OR "main"._tenant IS NULL)
```

The `OR _tenant IS NULL` branch exists specifically so global schema rows are
still visible. But the query only works if the table actually has a `_tenant`
column. If it doesn't, Postgres raises:

```
SQLSTATE[42703]: Undefined column: column main._tenant does not exist
```

Therefore `clarus__metadata` always needs a nullable `_tenant` column
(schema rows keep it `NULL`). This is handled in two ways depending on
whether the database is new or existing — see
[Boot setup vs migrations for tenancy](#boot-setup-vs-migrations-for-tenancy).

## Request lifecycle

For an authenticated, tenant-scoped route (for example `GET /v1/todos`):

1. **`session`** — `RequestAuthenticator::resolveSession()` reads the
   `clarus_session` cookie and validates the session secret against `db`.
2. **`user`** — `RequestAuthenticator::resolveUser()` loads the user (from
   the session, or a bearer JWT) and adds `user:<id>` / `users` roles to the
   request's `Authorization`.
3. **`tenantContext`** — `RequestAuthenticator::resolveTenantContext()`:
   - reads the `X-Tenant-Id` header,
   - confirms the user has an **active membership** in that tenant (via `db`),
   - confirms the tenant is active,
   - adds `team:<tenantId>` and `team:<tenantId>/<role>` roles to
     `Authorization`,
   - scopes the tenant handle with `dbForTenant->setTenant($tenant->getSequence())`.

   Only the **tenant sequence** is set here; shared tables were already
   enabled by the factory.
4. **`auth.php` middleware** enforces the route's `auth` / `roles` labels.
5. **Action** runs and uses `dbForTenant` for todo reads/writes. Every query
   is now filtered to the active tenant and checked against document
   permissions.

Identity lookups (session/user/membership/tenant) intentionally run inside
`Authorization::skip()`: possession of a valid session secret or JWT is the
proof of access, not a row-level ACL.

## Route protection: `auth` and `roles`

Routes opt into enforcement with labels, checked in
`app/controllers/auth.php`:

```php
$this
    ->label('auth', true)                    // must be a logged-in, active user
    ->label('roles', MembershipRole::all()); // + must have one of these roles in the active tenant
```

- `auth` (bool): request must carry a valid, active user, else `401`.
- `roles` (list): implies `auth`, and additionally requires an **active
  tenant** (`X-Tenant-Id`) in which the caller holds one of the listed roles.
  - Missing/invalid tenant → `400 general_tenant_required`.
  - Wrong role → `403 general_forbidden`.

Available roles (`src/Clarus/Auth/MembershipRole.php`), highest to lowest
rank: `owner` (50), `admin` (40), `auditor` (30), `employee` (20),
`contractor` (10). Roles are flat — each action declares exactly which roles
may run it; rank is only used where "at least as privileged as" is needed
(e.g. only an owner may grant the owner role).

## Document permissions

Within a tenant, access to individual documents is controlled by
utopia-php/database document permissions, stored in the collection's `_perms`
table. Todos are created with a team-read / owner-or-manager-write policy
(`src/Clarus/Platform/Modules/Todos/Http/Todos/Create.php`):

```php
Permission::read(Role::team($tenantId)),                          // everyone in the org can read
Permission::update(Role::user($ownerId)),                         // the creator can edit
Permission::update(Role::team($tenantId, MembershipRole::OWNER)), // owners can edit
Permission::update(Role::team($tenantId, MembershipRole::ADMIN)), // admins can edit
Permission::delete(Role::user($ownerId)),                         // creator can delete
Permission::delete(Role::team($tenantId, MembershipRole::OWNER)),
Permission::delete(Role::team($tenantId, MembershipRole::ADMIN)),
```

These `team:*` / `user:*` roles line up with the roles added to
`Authorization` during tenant resolution, so document checks "just work" for
the rest of the request.

## Adding a tenant-scoped collection

1. Add the collection to `app/config/collections.php` with `"tenant" => true`:

```php
"invoices" => [
    '$collection' => ID::custom(Database::METADATA),
    '$id' => ID::custom("invoices"),
    "name" => "invoices",
    "tenant" => true,           // <- makes it tenant-scoped (gets _tenant)
    "attributes" => [ /* ... */ ],
    "indexes" => [ /* ... */ ],
],
```

2. In the HTTP actions, inject **`dbForTenant`** (not `db`), add
   `->label('auth', true)` and `->label('roles', ...)`, and set document
   permissions on create using `Role::team($tenantId)` / `Role::user($ownerId)`.

3. For existing databases, add a migration to create the collection (fresh
   databases get it from boot setup). See `docs/MIGRATIONS.md`.

A collection **without** `"tenant" => true` is created as a plain global
table and should be accessed through `db`.

## Boot setup vs migrations for tenancy

There are two paths that must both produce the same final schema.

**Fresh database — `Setup::run($db)`** (`src/Clarus/Database/Setup.php`):

- Creates the database metadata with shared tables enabled on first boot, so
  `clarus__metadata` is created **with** a `_tenant` column from day one.
- Creates each configured collection, toggling shared tables based on the
  collection's `tenant` flag.
- Calls `ensureMetadataSupportsSharedTables()` as a safety net.

**Existing database — migration.** Databases created before multitenancy
already had a `clarus__metadata` (and `clarus_todos`) table **without**
`_tenant`. Boot setup skips existing collections, so these are fixed by
registered, forward-only migrations:

| Migration | What it does |
|-----------|--------------|
| `M20260707163000RetrofitTodosForMultitenancy` | Recreates `todos` on the tenant-scoped schema (adds `_tenant` + `ownerId`) if the legacy table lacks `ownerId`. Destructive to legacy todo rows. |
| `M20260707170000EnsureMetadataTenantColumn` | `ALTER TABLE clarus__metadata ADD COLUMN _tenant INTEGER DEFAULT NULL` if the column is missing. Idempotent. |

Both are registered in `src/Clarus/Database/MigrationRegistry.php` and run via:

```bash
podman-compose exec app php app/migrate.php
```

The `_tenant`-adding logic itself lives in
`src/Clarus/Database/Concerns/EnsuresSharedTableMetadata.php`, shared by both
`Setup` and the migration so the two paths stay identical.

> Whenever you add a new tenant-scoped collection to an already-deployed
> database, remember the same rule: config drives fresh installs, a migration
> upgrades existing ones.

## Troubleshooting

**`column main._tenant does not exist` on a tenant route.** The
`clarus__metadata` table is missing its `_tenant` column. Run migrations (or
restart the app so `Setup::run` patches it):

```bash
podman-compose exec app php app/migrate.php
# verify
podman-compose exec postgres psql -U clarus -d clarus \
  -c "SELECT column_name FROM information_schema.columns \
      WHERE table_name='clarus__metadata' AND column_name='_tenant';"
```

**`Attribute not found` on a tenant route.** The data table exists but is
missing a tenant-scoped attribute (for example `clarus_todos` lost `_tenant`
or `ownerId`). Reconcile the table with `app/config/collections.php` via a
migration.

**`An active tenant is required` (`400`).** The route has a `roles` label but
the request did not send a valid `X-Tenant-Id` the caller is a member of.

**`You must be logged in` (`401`).** Missing/invalid `clarus_session` cookie
or bearer JWT on a route labeled `auth`.
