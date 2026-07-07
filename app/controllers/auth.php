<?php

/**
 * Authentication & authorization middleware.
 *
 * Runs after `general.php`'s role reset. Resolving `user`/`tenantContext`
 * here (rather than leaving it purely lazy) guarantees that, by the time an
 * action runs, `dbForTenant` is already scoped to the caller's active
 * tenant and the Authorization validator already carries the roles
 * (`user:<id>`, `users`, `team:<tenantId>`, `team:<tenantId>/<role>`)
 * needed for document-level permission checks.
 *
 * Routes opt into enforcement via labels set through `Action::label()`:
 * - `auth` (bool): the request must carry a valid, active user.
 * - `roles` (list<string>): implies `auth`; additionally requires an active
 *   tenant (`X-Tenant-Id` header) in which the caller holds one of the
 *   given roles.
 */

use Clarus\Auth\TenantContext;
use Clarus\Extend\Exception;
use Utopia\Database\Document;
use Utopia\Http\Http;
use Utopia\Http\Route;

Http::init()
    ->groups(['api'])
    ->inject('route')
    ->inject('user')
    ->inject('tenantContext')
    ->action(function (Route $route, Document $user, TenantContext $tenantContext) {
        $requiresAuth = (bool) $route->getLabel('auth', false);
        /** @var list<string> $requiredRoles */
        $requiredRoles = $route->getLabel('roles', []);

        if (!\is_array($requiredRoles)) {
            $requiredRoles = [];
        }

        if (($requiresAuth || $requiredRoles !== []) && $user->isEmpty()) {
            throw new Exception(Exception::GENERAL_UNAUTHORIZED);
        }

        if ($requiredRoles === []) {
            return;
        }

        if (!$tenantContext->isResolved()) {
            throw new Exception(Exception::GENERAL_TENANT_REQUIRED);
        }

        if (!\in_array($tenantContext->getRole(), $requiredRoles, true)) {
            throw new Exception(Exception::GENERAL_FORBIDDEN);
        }
    });
