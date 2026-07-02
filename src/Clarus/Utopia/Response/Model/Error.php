<?php

namespace Clarus\Utopia\Response\Model;

use Clarus\Utopia\Response;
use Clarus\Utopia\Response\Model;

class Error extends Model
{
    public function __construct()
    {
        $this
            ->addRule('message', [
                'type' => self::TYPE_STRING,
                'description' => 'Error message.',
                'default' => '',
                'example' => 'Route not found.',
            ])
            ->addRule('code', [
                'type' => self::TYPE_INTEGER,
                'description' => 'HTTP status code.',
                'default' => 500,
                'example' => 404,
            ])
            ->addRule('type', [
                'type' => self::TYPE_STRING,
                'description' => 'Error type.',
                'default' => self::TYPE_STRING,
                'example' => 'general_route_not_found',
            ])
            ->addRule('version', [
                'type' => self::TYPE_STRING,
                'description' => 'Server version.',
                'default' => '',
                'example' => '0.1.0',
            ])
            ->addRule('file', [
                'type' => self::TYPE_STRING,
                'description' => 'Error file path.',
                'required' => false,
                'default' => '',
            ])
            ->addRule('line', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Error line number.',
                'required' => false,
                'default' => 0,
            ])
            ->addRule('trace', [
                'type' => self::TYPE_STRING,
                'description' => 'Error stack trace.',
                'required' => false,
                'default' => '',
            ])
            ->addRule('path', [
                'type' => self::TYPE_STRING,
                'description' => 'Request path.',
                'required' => false,
                'default' => '',
            ]);
    }

    public function getName(): string
    {
        return 'Error';
    }

    public function getType(): string
    {
        return Response::MODEL_ERROR;
    }
}
