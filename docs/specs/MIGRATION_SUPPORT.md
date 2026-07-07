---

  Migration System Spec

  Goal

  Add a lightweight, Appwrite-inspired migration system for this project.

  It should support:

  php app/migrate.php

  to apply pending forward migrations to the platform database.

  It should not implement rollback in v1.

  ---

  Non-goals for v1

  Do not build:

  - rollback/down migrations
  - migration generator
  - multi-database/project migration system
  - web UI for migration status
  - complex CLI framework
  - raw SQL migration files
  - automatic Drizzle-like schema diffing
  - automatic destructive schema sync from collections.php

  ---

  Current problem

  Current boot setup:

  Http::onStart()
      ->inject("db")
      ->action(function (Database $db) {
          Setup::run($db);
      });

  runs:

  Setup::run($db);

  This creates the database and missing collections.

  But if a collection already exists:

  if (!$database->getCollection($key)->isEmpty()) {
      continue;
  }

  it skips it.

  So later changes like:

  - add attribute
  - delete attribute
  - rename attribute
  - add index
  - delete index
  - backfill existing documents

  will not be applied to existing databases.

  That is why we need migrations.

  ---

  High-level design

  We will have two separate flows:

  Boot setup
    creates database + missing base collections

  Migration runner
    applies explicit versioned migration classes

  Similar to Appwrite:

  setup != migration

  ---

  Proposed file structure

  app/migrate.php
  src/Clarus/Database/Migration.php
  src/Clarus/Database/Migrator.php
  src/Clarus/Database/Migrations/

  Example future migration:

  src/Clarus/Database/Migrations/M202607070001AddTodoPriority.php

  ---

  Migration interface

  namespace Clarus\Database;

  use Utopia\Database\Database;

  interface Migration
  {
      public function getId(): string;

      public function getName(): string;

      public function execute(Database $db): void;
  }

  Use execute() instead of up() because Appwrite uses:

  $migration->execute();

  This keeps naming closer to Appwrite.

  No down().

  No rollback.

  ---

  Example migration class

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

          $db->createIndex(
              collection: 'todos',
              id: '_key_priority',
              type: Database::INDEX_KEY,
              attributes: ['priority'],
              orders: [Database::ORDER_ASC]
          );
      }
  }

  Actual initial migration folder can be empty. The system should still run successfully and say no pending migrations.

  ---

  Migration tracking

  We need a collection to remember which migrations already ran.

  Collection:

  migrations

  Attributes:

  name       string
  appliedAt  string

  Document ID:

  migration id

  Example document:

  {
    "$id": "202607070001_add_todo_priority",
    "name": "Add priority to todos",
    "appliedAt": "2026-07-07T12:00:00+00:00"
  }

  This is not Drizzle-style SQL journaling; it is just Utopia Database metadata.

  ---

  Where to define migrations collection

  Add it to:

  app/config/collections.php

  Something like:

  'migrations' => [
      '$collection' => ID::custom(Database::METADATA),
      '$id' => ID::custom('migrations'),
      'name' => 'migrations',
      'attributes' => [
          [
              '$id' => ID::custom('name'),
              'type' => Database::VAR_STRING,
              'size' => 255,
              'required' => true,
              ...
          ],
          [
              '$id' => ID::custom('appliedAt'),
              'type' => Database::VAR_STRING,
              'size' => 64,
              'required' => true,
              ...
          ],
      ],
      'indexes' => [],
  ],

  Then existing Setup::run($db) can create it on fresh DB.

  But for existing DBs, Setup::run() must also be able to create this new collection if missing. It already does that.

  It skips existing collections individually, not all collections globally.

  So adding migrations collection to config should work.

  ---

  Migrator behavior

  Migrator should:

  1. Ensure base DB setup has run.
  2. Load known migration classes.
  3. Read applied migrations from migrations collection.
  4. Execute only pending migrations.
  5. After each successful migration, write a document to migrations.
  6. Stop immediately on first failure.
  7. Print readable CLI output.

  Pseudo-flow:

  Setup::run($db);

  $migrations = [
      new M202607070001AddTodoPriority(),
  ];

  foreach ($migrations as $migration) {
      if ($db->getDocument('migrations', $migration->getId())->isEmpty() === false) {
          skip
      }

      echo "Running migration ...";

      $migration->execute($db);

      $db->createDocument('migrations', new Document([
          '$id' => ID::custom($migration->getId()),
          'name' => $migration->getName(),
          'appliedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
      ]));

      echo "Done";
  }

  ---

  Migration ordering

  Migrations should be ordered manually in one registry file/array.

  Initial simple approach inside Migrator:

  private function migrations(): array
  {
      return [
          // new M202607070001AddTodoPriority(),
      ];
  }

  This is explicit and safe.

  Later we can move this to:

  src/Clarus/Database/Migrations.php

  or auto-discover classes, but I would not auto-discover in v1.

  Reason: explicit order matters.

  ---

  CLI entrypoint

  Create:

  app/migrate.php

  It should:

  require_once __DIR__ . '/init.php';

  global $container;

  $db = $container->get('db');

  Setup::run($db);

  Migrator::run($db);

  No route/server/Swoole startup needed.

  It should use direct DB resource from the root container, same as boot setup.

  That is okay because migration is a CLI one-off process.

  ---

  Should migrations run automatically on boot?

  For v1, I suggest: no automatic migration on HTTP boot.

  Keep:

  Http::onStart()
      ->inject("db")
      ->action(function (Database $db) {
          Setup::run($db);
      });

  Only bootstraps missing base structures.

  Run migrations explicitly:

  php app/migrate.php

  This follows Appwrite’s separation more closely.

  Later, if desired, add env-controlled auto migration:

  DB_MIGRATIONS_AUTO=true

  but not in v1.

  ---

  Authorization

  Migration is internal system work.

  If needed, use:

  $authorization->skip(fn () => ...)

  or disable authorization in migration runner.

  Your current db already has Authorization injected.

  For v1, safest pattern:

  $authorization->skip(fn () => $migrator->run($db));

  But app/migrate.php will need to resolve both:

  $db = $container->get('db');
  $authorization = $container->get('authorization');

  Then run inside skip.

  ---

  Error behavior

  If a migration fails:

  - print migration ID/name
  - print exception message
  - exit non-zero
  - do not write migration document
  - do not continue to next migration

  This avoids marking failed migrations as applied.

  ---

  Idempotency expectation

  Each migration should ideally be safe enough to retry if it failed before recording.

  But v1 rule:

  > A migration is recorded only after successful completion.

  Migration authors should check existing structures when needed.

  Example:

  if ($db->getCollection('todos')->isEmpty()) {
      return;
  }

  or catch duplicate exceptions for attributes/indexes if necessary.

  ---

  Rollback policy

  No rollback in v1.

  Reason:

  - Appwrite does not implement rollback pattern in migration classes.
  - Many data migrations are not safely reversible.
  - Safer production rollback is DB backup restore + old code.

  Documented policy:

  Before production migration, take database backup.
  If migration fails after partial data changes, restore backup or write corrective forward migration.

  ---

  Phased implementation

  Phase 1: Minimal forward migration runner

  Implement:

  app/migrate.php
  src/Clarus/Database/Migration.php
  src/Clarus/Database/Migrator.php
  src/Clarus/Database/Migrations/.gitkeep

  Add migrations collection to:

  app/config/collections.php

  Behavior:

  php app/migrate.php

  Output:

  Starting migrations...
  No pending migrations.
  Migration completed.

  or:

  Running 202607070001_add_todo_priority: Add priority to todos
  Done 202607070001_add_todo_priority
  Migration completed.

  Phase 2: Helper methods similar to Appwrite

  Add helper methods to base migration or migrator:

  createAttributeFromCollection()
  createIndexFromCollection()

  This lets migrations reuse app/config/collections.php, similar to Appwrite.

  Example:

  $this->createAttributeFromCollection($db, 'todos', 'priority');

  But not needed for Phase 1 unless we immediately have a real schema change.

  Phase 3: Migration status

  Add:

  php app/migrate.php status

  Output:

  Applied:
  - 202607070001_add_todo_priority

  Pending:
  - 202607080001_add_due_date

  Phase 4: Locking

  If multi-replica/parallel deploy becomes real, add DB-level migration lock.

  For v1, not needed if deploy process runs one CLI command.

  ---

  Final recommendation

  Implement Phase 1 now.

  Do not add rollback.

  Do not auto-run migrations on HTTP boot.

  Keep boot setup for first-run database initialization.

  Use php app/migrate.php before starting/deploying new app version when schema changes exist.
