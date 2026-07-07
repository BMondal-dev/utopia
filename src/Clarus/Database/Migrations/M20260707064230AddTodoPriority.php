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
