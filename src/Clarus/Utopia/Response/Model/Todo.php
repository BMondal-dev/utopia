<?php

namespace Clarus\Utopia\Response\Model;

use Clarus\Utopia\Response;
use Clarus\Utopia\Response\Model;

class Todo extends Model
{
    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => self::TYPE_STRING,
                'description' => 'Todo ID.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('$createdAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Todo creation date in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('$updatedAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Todo update date in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('title', [
                'type' => self::TYPE_STRING,
                'description' => 'Todo title.',
                'default' => '',
                'example' => 'Buy milk',
            ])
            ->addRule('description', [
                'type' => self::TYPE_STRING,
                'description' => 'Todo description.',
                'default' => '',
                'example' => '2% organic',
                'required' => false,
            ])
            ->addRule('completed', [
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Whether the todo is completed.',
                'default' => false,
                'example' => false,
            ])
            ->addRule('ownerId', [
                'type' => self::TYPE_STRING,
                'description' => 'ID of the user who created the todo.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ]);
    }

    public function getName(): string
    {
        return 'Todo';
    }

    public function getType(): string
    {
        return Response::MODEL_TODO;
    }
}
