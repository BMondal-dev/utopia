<?php

namespace Clarus\Database;

use Clarus\Database\Migrations\M20260707064230AddTodoPriority;
use Clarus\Database\Migrations\M20260707163000RetrofitTodosForMultitenancy;
use Clarus\Database\Migrations\M20260707170000EnsureMetadataTenantColumn;

class MigrationRegistry
{
    /**
     * @return list<Migration>
     */
    public static function all(): array
    {
        return [
            new M20260707064230AddTodoPriority(),
            new M20260707163000RetrofitTodosForMultitenancy(),
            new M20260707170000EnsureMetadataTenantColumn(),
        ];
    }
}
