<?php

namespace Clarus\Utopia;

use Swoole\Coroutine;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Utopia\Http\Adapter\Swoole\Request;
use Utopia\Http\Adapter\Swoole\Server as BaseServer;

class Server extends BaseServer
{
    protected const string CONTEXT_KEY = '__utopia__';

    public function onRequest(callable $callback): void
    {
        $this->getServer()->on('request', function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
            $context = new \Utopia\DI\Container($this->resources());
            $context->set('swooleRequest', fn () => $request);
            $context->set('swooleResponse', fn () => $response);

            $cid = Coroutine::getCid();
            if ($cid !== -1) {
                Coroutine::getContext()[self::CONTEXT_KEY] = $context;
            } else {
                $this->context = $context;
            }

            try {
                \call_user_func($callback, new Request($request), new Response($response));
            } finally {
                if ($cid === -1) {
                    $this->context = null;
                }
            }
        });
    }
}
