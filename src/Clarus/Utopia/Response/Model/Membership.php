<?php

namespace Clarus\Utopia\Response\Model;

use Clarus\Utopia\Response;
use Clarus\Utopia\Response\Model;

class Membership extends Model
{
    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => self::TYPE_STRING,
                'description' => 'Membership ID.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('$createdAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Membership creation date in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('tenantId', [
                'type' => self::TYPE_STRING,
                'description' => 'ID of the tenant this membership belongs to.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('userId', [
                'type' => self::TYPE_STRING,
                'description' => 'ID of the member user.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('role', [
                'type' => self::TYPE_STRING,
                'description' => 'Role held by the user within the tenant.',
                'default' => '',
                'example' => 'employee',
            ])
            ->addRule('status', [
                'type' => self::TYPE_STRING,
                'description' => 'Membership status (active or revoked).',
                'default' => 'active',
                'example' => 'active',
            ]);
    }

    public function getName(): string
    {
        return 'Membership';
    }

    public function getType(): string
    {
        return Response::MODEL_MEMBERSHIP;
    }
}
