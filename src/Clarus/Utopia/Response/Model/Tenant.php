<?php

namespace Clarus\Utopia\Response\Model;

use Clarus\Utopia\Response;
use Clarus\Utopia\Response\Model;

class Tenant extends Model
{
    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => self::TYPE_STRING,
                'description' => 'Tenant ID.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('$createdAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Tenant creation date in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('name', [
                'type' => self::TYPE_STRING,
                'description' => 'Tenant (organization) name.',
                'default' => '',
                'example' => 'Acme Inc.',
            ])
            ->addRule('slug', [
                'type' => self::TYPE_STRING,
                'description' => 'Unique, URL-friendly tenant identifier.',
                'default' => '',
                'example' => 'acme-inc',
            ])
            ->addRule('status', [
                'type' => self::TYPE_STRING,
                'description' => 'Tenant status (active or suspended).',
                'default' => 'active',
                'example' => 'active',
            ])
            ->addRule('role', [
                'type' => self::TYPE_STRING,
                'description' => 'The caller\'s role in this tenant, when known.',
                'default' => '',
                'example' => 'owner',
                'required' => false,
            ]);
    }

    public function getName(): string
    {
        return 'Tenant';
    }

    public function getType(): string
    {
        return Response::MODEL_TENANT;
    }
}
