<?php

use Clarus\Extend\Exception;

return [
    Exception::GENERAL_UNKNOWN => [
        'name' => Exception::GENERAL_UNKNOWN,
        'description' => 'An unknown error has occurred.',
        'code' => 500,
    ],
    Exception::GENERAL_SERVER_ERROR => [
        'name' => Exception::GENERAL_SERVER_ERROR,
        'description' => 'Server error occurred.',
        'code' => 500,
    ],
    Exception::GENERAL_ROUTE_NOT_FOUND => [
        'name' => Exception::GENERAL_ROUTE_NOT_FOUND,
        'description' => 'Route not found.',
        'code' => 404,
    ],
    Exception::GENERAL_ARGUMENT_INVALID => [
        'name' => Exception::GENERAL_ARGUMENT_INVALID,
        'description' => 'The request contains one or more invalid arguments.',
        'code' => 400,
    ],
    Exception::TODO_NOT_FOUND => [
        'name' => Exception::TODO_NOT_FOUND,
        'description' => 'Todo with the requested ID could not be found.',
        'code' => 404,
    ],
    Exception::TODO_ALREADY_EXISTS => [
        'name' => Exception::TODO_ALREADY_EXISTS,
        'description' => 'Todo with the requested ID already exists.',
        'code' => 409,
    ],
];
