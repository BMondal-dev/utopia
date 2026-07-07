<?php

namespace Clarus\Database\Concerns;

use Utopia\Database\Adapter\Postgres;
use Utopia\Database\Database;
use Utopia\Database\PDO;
use Utopia\System\System;

/**
 * Ensures the internal {@see Database::METADATA} physical table can be read
 * while a tenant-scoped {@see Database} handle has shared tables enabled.
 *
 * Utopia always stores collection schemas in `_metadata`. When
 * `setSharedTables(true)` is active, metadata reads include a `_tenant`
 * predicate (with `OR _tenant IS NULL` for global rows). The metadata table
 * must therefore expose a nullable `_tenant` column even though collection
 * schema documents themselves are global.
 */
trait EnsuresSharedTableMetadata
{
    protected function ensureMetadataSupportsSharedTables(Database $database): void
    {
        if (!$database->getAdapter() instanceof Postgres) {
            return;
        }

        $pdo = $this->metadataPdo();
        $schema = $database->getDatabase();
        $table = $database->getNamespace() . '_' . Database::METADATA;

        if ($this->metadataColumnExists($pdo, $schema, $table, '_tenant')) {
            return;
        }

        $quotedSchema = $this->quoteMetadataIdentifier($schema);
        $quotedTable = $this->quoteMetadataIdentifier($table);

        $pdo->exec(
            "ALTER TABLE {$quotedSchema}.{$quotedTable} ADD COLUMN _tenant INTEGER DEFAULT NULL",
        );
    }

    private function metadataPdo(): PDO
    {
        $host = System::getEnv('DB_HOST', 'postgres');
        $port = System::getEnv('DB_PORT', '5432');
        $name = System::getEnv('DB_NAME', 'clarus');
        $user = System::getEnv('DB_USER', 'clarus');
        $pass = System::getEnv('DB_PASSWORD', 'secret');

        return new PDO(
            "pgsql:host={$host};port={$port};dbname={$name};connect_timeout=3",
            $user,
            $pass,
            Postgres::getPDOAttributes(),
        );
    }

    private function metadataColumnExists(
        PDO $pdo,
        string $schema,
        string $table,
        string $column,
    ): bool {
        $statement = $pdo->prepare(
            'SELECT 1 FROM information_schema.columns
             WHERE table_schema = :schema AND table_name = :table AND column_name = :column
             LIMIT 1',
        );
        $statement->execute([
            'schema' => $schema,
            'table' => $table,
            'column' => $column,
        ]);

        return (bool) $statement->fetchColumn();
    }

    private function quoteMetadataIdentifier(string $identifier): string
    {
        return '"' . \str_replace('"', '""', $identifier) . '"';
    }
}
