<?php

namespace Clarus\Auth;

use Utopia\Database\Document;

/**
 * The resolved "active tenant" for the current request: which tenant the
 * `X-Tenant-Id` header points to, and the caller's membership (and role)
 * within it. Both documents are empty when no valid, active membership
 * could be resolved.
 */
final class TenantContext
{
    public function __construct(
        public readonly Document $tenant,
        public readonly Document $membership,
    ) {
    }

    public function isResolved(): bool
    {
        return !$this->tenant->isEmpty() && !$this->membership->isEmpty();
    }

    public function getRole(): string
    {
        return (string) $this->membership->getAttribute('role', '');
    }
}
