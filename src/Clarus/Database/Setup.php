<?php

namespace Clarus\Database;

use Utopia\Config\Config;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Duplicate as DuplicateException;
use Utopia\Database\Helpers\ID;

class Setup
{
    public static function run(Database $database): void
    {
        $collections = Config::getParam('collections', []);

        try {
            $database->create();
        } catch (DuplicateException) {
            // Database metadata already exists.
        }

        foreach ($collections as $key => $collection) {
            if (($collection['$collection'] ?? '') !== Database::METADATA) {
                continue;
            }

            if (!$database->getCollection($key)->isEmpty()) {
                continue;
            }

            $attributes = \array_map(fn (array $attribute) => new Document([
                '$id' => ID::custom($attribute['$id']),
                'type' => $attribute['type'],
                'size' => $attribute['size'],
                'required' => $attribute['required'],
                'signed' => $attribute['signed'],
                'array' => $attribute['array'],
                'filters' => $attribute['filters'],
                'default' => $attribute['default'] ?? null,
                'format' => $attribute['format'] ?? '',
            ]), $collection['attributes']);

            $indexes = \array_map(fn (array $index) => new Document([
                '$id' => ID::custom($index['$id']),
                'type' => $index['type'],
                'attributes' => $index['attributes'],
                'orders' => $index['orders'] ?? [],
                'lengths' => $index['lengths'] ?? [],
            ]), $collection['indexes'] ?? []);

            $database->createCollection($key, $attributes, $indexes);
        }
    }
}
