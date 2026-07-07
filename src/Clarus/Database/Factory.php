<?php

namespace Clarus\Database;

use Utopia\Cache\Cache;
use Utopia\Config\Config;
use Utopia\Database\Database;
use Utopia\Database\Validator\Authorization;
use Utopia\System\System;

/**
 * Builds {@see Database} handles the way Appwrite's
 * {@see \Appwrite\Database\Factory} does: a platform/global handle and a
 * tenant-scoped handle that shares the same physical database.
 */
final class Factory
{
    public static function platform(
        Database $database,
        Authorization $authorization,
        Cache $cache,
    ): Database {
        return self::configure($database, $authorization, $cache)
            ->setSharedTables(false)
            ->setTenant(null);
    }

    public static function forTenant(
        Database $database,
        Authorization $authorization,
        Cache $cache,
    ): Database {
        return self::configure($database, $authorization, $cache)
            ->setSharedTables(true)
            ->setGlobalCollections(self::globalCollectionIds());
    }

    /**
     * Collection schemas that live in the global `db` handle and are not
     * partitioned per tenant. Used for metadata cache keys when reading schemas
     * through a tenant-scoped handle (see Appwrite's
     * `projectGlobalCollections()`).
     *
     * @return list<string>
     */
    public static function globalCollectionIds(): array
    {
        /** @var array<string, array<string, mixed>> $collections */
        $collections = Config::getParam('collections', []);

        return \array_values(\array_filter(
            \array_keys($collections),
            fn (string $id) => !($collections[$id]['tenant'] ?? false),
        ));
    }

    private static function configure(
        Database $database,
        Authorization $authorization,
        Cache $cache,
    ): Database {
        return $database
            ->setAuthorization($authorization)
            ->setDatabase(System::getEnv('DB_NAME', 'clarus'))
            ->setNamespace(System::getEnv('DB_NAMESPACE', 'clarus'))
            ->setTimeout(APP_DATABASE_TIMEOUT_MILLISECONDS)
            ->setMaxQueryValues(APP_DATABASE_QUERY_MAX_VALUES);
    }
}
