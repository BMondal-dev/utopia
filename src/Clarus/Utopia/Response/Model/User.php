<?php

namespace Clarus\Utopia\Response\Model;

use Clarus\Utopia\Response;
use Clarus\Utopia\Response\Model;
use Utopia\Database\Document;

class User extends Model
{
    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => self::TYPE_STRING,
                'description' => 'User ID.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('$createdAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'User creation date in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('name', [
                'type' => self::TYPE_STRING,
                'description' => 'User name.',
                'default' => '',
                'example' => 'Jane Doe',
            ])
            ->addRule('email', [
                'type' => self::TYPE_STRING,
                'description' => 'User email address.',
                'default' => '',
                'example' => 'jane@example.com',
            ])
            ->addRule('emailVerified', [
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Whether the user email is verified.',
                'default' => false,
                'example' => false,
            ])
            ->addRule('status', [
                'type' => self::TYPE_STRING,
                'description' => 'User status (active or blocked).',
                'default' => 'active',
                'example' => 'active',
            ]);
    }

    public function filter(Document $document): Document
    {
        // Never leak the password hash to API responses.
        $document->removeAttribute('password');

        return $document;
    }

    public function getName(): string
    {
        return 'User';
    }

    public function getType(): string
    {
        return Response::MODEL_USER;
    }
}
