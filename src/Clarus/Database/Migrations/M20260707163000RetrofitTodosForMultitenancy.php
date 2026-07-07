<?php

namespace Clarus\Database\Migrations;

use Clarus\Database\Concerns\UsesCollectionConfig;
use Clarus\Database\Migration;
use Utopia\Database\Database;

/**
 * Moves legacy global `todos` onto the tenant-scoped schema defined in
 * `app/config/collections.php` (shared `_tenant` column + `ownerId`).
 *
 * Fresh databases already get the final schema from boot setup. This
 * migration recreates the collection for databases that still have the
 * pre-multitenancy `todos` table.
 */
final class M20260707163000RetrofitTodosForMultitenancy implements Migration
{
    use UsesCollectionConfig;

    public function getId(): string
    {
        return '20260707163000_retrofit_todos_for_multitenancy';
    }

    public function getName(): string
    {
        return 'Retrofit todos for multitenancy (tenant scoping + ownerId)';
    }

    public function execute(Database $db): void
    {
        if ($db->getCollection('todos')->isEmpty()) {
            return;
        }

        if ($this->collectionHasAttribute($db, 'todos', 'ownerId')) {
            return;
        }

        $this->recreateCollectionFromConfig($db, 'todos');
    }
}
