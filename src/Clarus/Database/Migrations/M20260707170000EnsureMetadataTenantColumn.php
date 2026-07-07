<?php

namespace Clarus\Database\Migrations;

use Clarus\Database\Concerns\EnsuresSharedTableMetadata;
use Clarus\Database\Migration;
use Utopia\Database\Database;

final class M20260707170000EnsureMetadataTenantColumn implements Migration
{
    use EnsuresSharedTableMetadata;

    public function getId(): string
    {
        return '20260707170000_ensure_metadata_tenant_column';
    }

    public function getName(): string
    {
        return 'Ensure metadata table supports shared-table tenant reads';
    }

    public function execute(Database $db): void
    {
        $this->ensureMetadataSupportsSharedTables($db);
    }
}
