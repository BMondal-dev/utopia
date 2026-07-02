<?php

namespace Clarus\Utopia\Response\Model;

use Clarus\Utopia\Response;
use Clarus\Utopia\Response\Model;

class Health extends Model
{
    public function __construct()
    {
        $this
            ->addRule('name', [
                'type' => self::TYPE_STRING,
                'description' => 'Service name.',
                'default' => '',
                'example' => 'Clarus Backend',
            ])
            ->addRule('version', [
                'type' => self::TYPE_STRING,
                'description' => 'Server version.',
                'default' => '',
                'example' => '0.1.0',
            ])
            ->addRule('status', [
                'type' => self::TYPE_ENUM,
                'description' => 'Service status.',
                'default' => 'pass',
                'example' => 'pass',
                'enum' => ['pass', 'fail'],
            ])
            ->addRule('ping', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Health check duration in milliseconds.',
                'default' => 0,
                'example' => 0,
            ]);
    }

    public function getName(): string
    {
        return 'Health';
    }

    public function getType(): string
    {
        return Response::MODEL_HEALTH;
    }
}
