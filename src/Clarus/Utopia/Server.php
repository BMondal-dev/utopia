<?php

namespace Clarus\Utopia;

use Swoole\Coroutine;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\Cache\Cache;
use Utopia\Database\Adapter\Postgres;
use Utopia\Database\Database;
use Utopia\Database\Validator\Authorization;
use Utopia\Http\Adapter\Swoole\Request;
use Utopia\Http\Adapter\Swoole\Server as BaseServer;
use Utopia\Pools\Group;
use Utopia\System\System;

class Server extends BaseServer
{
    protected const string CONTEXT_KEY = '__utopia__';

    public function onRequest(callable $callback): void
    {
        $this->server->on('request', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            $context = new \Utopia\DI\Container($this->resources());
            $context->set('swooleRequest', fn () => $request);
            $context->set('swooleResponse', fn () => $response);

            // Fresh Authorization per request to prevent state leakage across concurrent coroutines.
            $context->set('authorization', fn () => new Authorization());

            // Pop a dedicated DB connection from the pool for this request.
            $dbConnection = null;
            if ($this->resources()->has('pools')) {
                /** @var Group $pools */
                $pools = $this->resources()->get('pools');
                $dbConnection = $pools->get('db')->pop();
                $pdo = $dbConnection->getResource();

                $context->set('db', function (Authorization $authorization, Cache $cache) use ($pdo) {
                    $name = System::getEnv('DB_NAME', 'clarus');
                    $database = new Database(new Postgres($pdo), $cache);
                    $database
                        ->setAuthorization($authorization)
                        ->setDatabase($name)
                        ->setNamespace(System::getEnv('DB_NAMESPACE', 'clarus'))
                        ->setTimeout(APP_DATABASE_TIMEOUT_MILLISECONDS)
                        ->setMaxQueryValues(APP_DATABASE_QUERY_MAX_VALUES);
                    return $database;
                }, ['authorization', 'cache']);
            }

            $cid = Coroutine::getCid();
            if ($cid !== -1) {
                Coroutine::getContext()[self::CONTEXT_KEY] = $context;
            } else {
                $this->context = $context;
            }

            try {
                \call_user_func($callback, new Request($request), new Response($response));
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
