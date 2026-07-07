<?php

namespace Clarus\Database;

use Clarus\Database\Concerns\EnsuresSharedTableMetadata;
use Clarus\Database\Concerns\UsesCollectionConfig;
use Utopia\Config\Config;
use Utopia\Database\Database;
use Utopia\Database\Exception\Duplicate as DuplicateException;

class Setup
{
    use EnsuresSharedTableMetadata;
    use UsesCollectionConfig;

    public static function run(Database $database): void
    {
        $collections = Config::getParam('collections', []);

        $sharedTables = $database->getSharedTables();

        try {
            // Appwrite creates project databases with shared tables enabled
            // from the start so the internal `_metadata` table includes a
            // nullable `_tenant` column. We do the same on first boot.
            if (!$database->exists()) {
                $database->setSharedTables(true);
            }

            $database->create();
        } catch (DuplicateException) {
            // Database metadata already exists.
        } finally {
            $database->setSharedTables($sharedTables);
        }

        $setup = new self();

        foreach ($collections as $key => $collection) {
            if (($collection['$collection'] ?? '') !== Database::METADATA) {
                continue;
            }

            if (!$database->getCollection($key)->isEmpty()) {
                continue;
            }

            $setup->createCollectionFromConfig($database, $key);
        }

        $setup->ensureMetadataSupportsSharedTables($database);
    }
}
