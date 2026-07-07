<?php

namespace Clarus\Database;

use Utopia\Database\Database;

interface Migration
{
    public function getId(): string;

    public function getName(): string;

    public function execute(Database $db): void;
}
