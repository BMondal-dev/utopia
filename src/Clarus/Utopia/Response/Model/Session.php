<?php

namespace Clarus\Utopia\Response\Model;

use Clarus\Utopia\Response;
use Clarus\Utopia\Response\Model;
use Utopia\Database\Document;

class Session extends Model
{
    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => self::TYPE_STRING,
                'description' => 'Session ID.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('$createdAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Session creation date in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('userId', [
                'type' => self::TYPE_STRING,
                'description' => 'ID of the user associated with this session.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('provider', [
                'type' => self::TYPE_STRING,
                'description' => 'Authentication provider used to create this session.',
                'default' => 'email',
                'example' => 'email',
            ])
            ->addRule('expire', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Session expiry date in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ]);
    }

    public function filter(Document $document): Document
    {
        // Never leak the hashed session secret to API responses.
        $document->removeAttribute('secret');

        return $document;
    }

    public function getName(): string
    {
        return 'Session';
    }

    public function getType(): string
    {
        return Response::MODEL_SESSION;
    }
}
