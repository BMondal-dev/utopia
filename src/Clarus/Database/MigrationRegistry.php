<?php

namespace Clarus\Database;

class MigrationRegistry
{
    /**
     * @return list<Migration>
     */
    public static function all(): array
    {
        return [
            // Register forward-only migrations here in the order they must run.
        ];
    }
}
