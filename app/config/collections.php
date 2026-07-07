<?php

use Utopia\Database\Database;
use Utopia\Database\Helpers\ID;

return [
    "todos" => [
        '$collection' => ID::custom(Database::METADATA),
        '$id' => ID::custom("todos"),
        "name" => "todos",
        "attributes" => [
            [
                '$id' => ID::custom("title"),
                "type" => Database::VAR_STRING,
                "format" => "",
                "size" => APP_LIMIT_TODO_TITLE,
                "signed" => true,
                "required" => true,
                "default" => null,
                "array" => false,
                "filters" => [],
            ],
            [
                '$id' => ID::custom("description"),
                "type" => Database::VAR_STRING,
                "format" => "",
                "size" => APP_LIMIT_TODO_DESCRIPTION,
                "signed" => true,
                "required" => false,
                "default" => "",
                "array" => false,
                "filters" => [],
            ],
            [
                '$id' => ID::custom("completed"),
                "type" => Database::VAR_BOOLEAN,
                "format" => "",
                "size" => 0,
                "signed" => true,
                "required" => false,
                "default" => false,
                "array" => false,
                "filters" => [],
            ],
            [
                '$id' => ID::custom("priority"),
                "type" => Database::VAR_STRING,
                "format" => "",
                "size" => 32,
                "signed" => true,
                "required" => false,
                "default" => "normal",
                "array" => false,
                "filters" => [],
            ],
        ],
        "indexes" => [
            [
                '$id' => ID::custom("_key_completed"),
                "type" => Database::INDEX_KEY,
                "attributes" => ["completed"],
                "orders" => [Database::ORDER_ASC],
            ],
            [
                '$id' => ID::custom("_key_priority"),
                "type" => Database::INDEX_KEY,
                "attributes" => ["priority"],
                "orders" => [Database::ORDER_ASC],
            ],
            [
                '$id' => ID::custom("_createdAt"),
                "type" => Database::INDEX_KEY,
                "attributes" => ['$createdAt'],
                "orders" => [Database::ORDER_DESC],
            ],
        ],
    ],
    "migrations" => [
        '$collection' => ID::custom(Database::METADATA),
        '$id' => ID::custom("migrations"),
        "name" => "migrations",
        "attributes" => [
            [
                '$id' => ID::custom("name"),
                "type" => Database::VAR_STRING,
                "format" => "",
                "size" => 255,
                "signed" => true,
                "required" => true,
                "default" => null,
                "array" => false,
                "filters" => [],
            ],
            [
                '$id' => ID::custom("appliedAt"),
                "type" => Database::VAR_STRING,
                "format" => "",
                "size" => 64,
                "signed" => true,
                "required" => true,
                "default" => null,
                "array" => false,
                "filters" => [],
            ],
        ],
        "indexes" => [],
    ],
];
