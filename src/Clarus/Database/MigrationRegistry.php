<?php

namespace Clarus\Database;

use Clarus\Database\Migrations\M20260707064230AddTodoPriority;

class MigrationRegistry
{
    /**
     * @return list<Migration>
     */
    public static function all(): array
    {
        return [
            new M20260707064230AddTodoPriority(),
        ];
    }
}
