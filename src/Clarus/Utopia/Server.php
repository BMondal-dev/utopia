<?php

namespace Clarus\Utopia;

use Clarus\Auth\Jwt;
use Clarus\Auth\RequestAuthenticator;
use Clarus\Auth\TenantContext;
use Clarus\Database\Factory as DatabaseFactory;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Coroutine;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\Cache\Cache;
use Utopia\Database\Adapter\Postgres;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Validator\Authorization;
use Utopia\Http\Adapter\Swoole\RequestFactory;
use Utopia\Http\Adapter\Swoole\Server as BaseServer;
use Utopia\Pools\Group;

class Server extends BaseServer
{
    protected const string CONTEXT_KEY = "__utopia__";

    public function onRequest(callable $callback): void
    {
        // utopia-php/http 2.x works entirely in terms of PSR-7 requests; there
        // is no more `Utopia\Http\Request` wrapper class to instantiate here.
        $requestFactory = new RequestFactory();

        $this->server->on("request", function (
            SwooleRequest $request,
            SwooleResponse $response,
        ) use ($callback, $requestFactory) {
            $context = new \Utopia\DI\Container($this->resources());
            $context->set("swooleRequest", fn () => $request);
            $context->set("swooleResponse", fn () => $response);

            // Fresh Authorization per request to prevent state leakage across concurrent coroutines.
            $context->set("authorization", fn () => new Authorization());

            // Pop a dedicated DB connection from the pool for this request.
            $dbConnection = null;
            if ($this->resources()->has("pools")) {
                /** @var Group $pools */
                $pools = $this->resources()->get("pools");
                $dbConnection = $pools->get("db")->pop();
                $pdo = $dbConnection->getResource();

                // Global, non-tenant-scoped database handle: users, tenants,
                // memberships, sessions and other account-level collections
                // live here and are never partitioned by tenant.
                $context->set(
                    "db",
                    function (Authorization $authorization, Cache $cache) use (
                        $pdo,
                    ) {
                        return DatabaseFactory::platform(
                            new Database(new Postgres($pdo), $cache),
                            $authorization,
                            $cache,
                        );
                    },
                    ["authorization", "cache"],
                );

                // Tenant-scoped database handle sharing the same pooled
                // connection: business collections (e.g. todos) live in
                // shared tables, isolated per tenant via the `_tenant`
                // column. The tenant itself is applied by the `tenantContext`
                // resource once the active tenant has been resolved.
                $context->set(
                    "dbForTenant",
                    function (Authorization $authorization, Cache $cache) use (
                        $pdo,
                    ) {
                        return DatabaseFactory::forTenant(
                            new Database(new Postgres($pdo), $cache),
                            $authorization,
                            $cache,
                        );
                    },
                    ["authorization", "cache"],
                );
            }

            // Authentication: resolves the session cookie (if any), then the
            // user it belongs to (session cookie or, failing that, a bearer
            // JWT). Both are cached for the lifetime of the request.
            $context->set(
                "session",
                function (
                    ServerRequestInterface $request,
                    Database $db,
                    Authorization $authorization,
                ) {
                    return RequestAuthenticator::resolveSession(
                        $request,
                        $db,
                        $authorization,
                    );
                },
                ["request", "db", "authorization"],
            );

            $context->set(
                "user",
                function (
                    Document $session,
                    ServerRequestInterface $request,
                    Database $db,
                    Authorization $authorization,
                    Jwt $jwt,
                ) {
                    return RequestAuthenticator::resolveUser(
                        $session,
                        $request,
                        $db,
                        $authorization,
                        $jwt,
                    );
                },
                ["session", "request", "db", "authorization", "jwt"],
            );

            // Multitenancy: resolves the tenant selected via the
            // `X-Tenant-Id` header (if the caller is a member of it), adds
            // the corresponding `team:<id>` / `team:<id>/<role>` roles to the
            // Authorization validator, and scopes `dbForTenant` to it.
            $context->set(
                "tenantContext",
                function (
                    ServerRequestInterface $request,
                    Document $user,
                    Database $db,
                    Database $dbForTenant,
                    Authorization $authorization,
                ) {
                    return RequestAuthenticator::resolveTenantContext(
                        $request,
                        $user,
                        $db,
                        $dbForTenant,
                        $authorization,
                    );
                },
                ["request", "user", "db", "dbForTenant", "authorization"],
            );

            $context->set(
                "tenant",
                fn (TenantContext $tenantContext) => $tenantContext->tenant,
                ["tenantContext"],
            );
            $context->set(
                "membership",
                fn (TenantContext $tenantContext) => $tenantContext->membership,
                ["tenantContext"],
            );

            $cid = Coroutine::getCid();
            if ($cid !== -1) {
                Coroutine::getContext()[self::CONTEXT_KEY] = $context;
            } else {
                $this->context = $context;
            }

            try {
                \call_user_func(
                    $callback,
                    $requestFactory->create($request),
                    new Response($response),
                );
            } finally {
                // Return the DB connection to the pool for reuse by the next request.
                $dbConnection?->reclaim();
                if ($cid === -1) {
                    $this->context = null;
                }
            }
        });
    }
}
