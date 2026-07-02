<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Utopia\Http\Http;
use Utopia\Http\Request;
use Utopia\Http\Response;
use Utopia\Http\Adapter\Swoole\Server;

$http = new Http(new Server('0.0.0.0', '8080'), 'UTC');

Http::get('/')
    ->inject('request')
    ->inject('response')
    ->action(function (Request $request, Response $response) {
        $response->json(['status' => 'ok', 'message' => 'utopia is walking']);
    });

Http::get('/health')
    ->inject('response')
    ->action(function (Response $response) {
        $response->json(['status' => 'ok']);
    });

$http->start();
