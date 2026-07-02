<?php

use Clarus\Database\Setup;
use Clarus\Extend\Exception;
use Clarus\Platform\Application;
use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Helpers\Role;
use Utopia\Database\Validator\Authorization;
use Utopia\Http\Http;
use Utopia\Http\Request;
use Utopia\Http\Response as HttpResponse;
use Utopia\Http\Route;
use Utopia\Platform\Service;
use Utopia\System\System;

Http::init()
    ->groups(['api'])
    ->inject('authorization')
    ->action(function (Authorization $authorization) {
        $authorization->cleanRoles();
        $authorization->addRole(Role::any()->toString());
    });

Http::init()
    ->groups(['api'])
    ->inject('route')
    ->inject('request')
    ->inject('response')
    ->action(function (Route $route, Request $request, Response $response) {
        $response
            ->addHeader('Access-Control-Allow-Origin', '*')
            ->addHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE, OPTIONS')
            ->addHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->addHeader('Access-Control-Max-Age', '86400');

        if ($request->getMethod() === Http::REQUEST_METHOD_OPTIONS) {
            $response->setStatusCode(HttpResponse::STATUS_CODE_NOCONTENT)->send('');
        }
    });

Http::error()
    ->groups(['api'])
    ->inject('error')
    ->inject('request')
    ->inject('response')
    ->inject('route')
    ->action(function (\Throwable $error, Request $request, Response $response, ?Route $route) {
        $class = \get_class($error);
        $code = $error->getCode();
        $message = $error->getMessage();

        switch ($class) {
            case \Utopia\Http\Exception::class:
                $error = new Exception(Exception::GENERAL_UNKNOWN, $message, $code, $error);
                switch ($code) {
                    case 400:
                        $error->setType(Exception::GENERAL_ARGUMENT_INVALID);
                        break;
                    case 404:
                        $error->setType(Exception::GENERAL_ROUTE_NOT_FOUND);
                        break;
                }
                break;
        }

        if (!$error instanceof Exception) {
            $error = new Exception(Exception::GENERAL_UNKNOWN, $message, $code, $error);
        }

        $code = $error->getCode();
        $message = $error->getMessage();
        $type = $error->getType();

        switch ($code) {
            case 400:
            case 401:
            case 403:
            case 404:
            case 409:
            case 422:
            case 429:
                break;
            default:
                $code = HttpResponse::STATUS_CODE_INTERNAL_SERVER_ERROR;
                $message = Http::isProduction()
                    ? 'Server error occurred.'
                    : $error->getMessage();
        }

        $payload = [
            'message' => $message,
            'type' => $type,
            'code' => $code,
            'version' => APP_VERSION,
        ];

        if (!Http::isProduction()) {
            $payload['file'] = $error->getFile();
            $payload['line'] = $error->getLine();
            $payload['trace'] = $error->getTraceAsString();
            $payload['path'] = $route?->getPath() ?? $request->getURI();
        }

        $response
            ->setStatusCode($code)
            ->dynamic(new Document($payload), Response::MODEL_ERROR);
    });

Http::shutdown()
    ->groups(['api'])
    ->inject('request')
    ->inject('response')
    ->action(function (Request $request, Response $response) {
        if (!$response->isSent()) {
            throw new Exception(Exception::GENERAL_ROUTE_NOT_FOUND);
        }
    });

Http::onStart()
    ->inject('db')
    ->action(function (Database $db) {
        Setup::run($db);
    });

Http::get('/')
    ->groups(['web'])
    ->inject('response')
    ->action(function (Response $response) {
        $response->redirect('/v1/health');
    });

$platform = new Application();
$platform->init(Service::TYPE_HTTP);
