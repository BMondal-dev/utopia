<?php

use Clarus\Extend\Exception;

return [
    Exception::GENERAL_UNKNOWN => [
        "name" => Exception::GENERAL_UNKNOWN,
        "description" => "An unknown error has occurred.",
        "code" => 500,
    ],
    Exception::GENERAL_SERVER_ERROR => [
        "name" => Exception::GENERAL_SERVER_ERROR,
        "description" => "Server error occurred.",
        "code" => 500,
    ],
    Exception::GENERAL_ROUTE_NOT_FOUND => [
        "name" => Exception::GENERAL_ROUTE_NOT_FOUND,
        "description" => "Route not found.",
        "code" => 404,
    ],
    Exception::GENERAL_ARGUMENT_INVALID => [
        "name" => Exception::GENERAL_ARGUMENT_INVALID,
        "description" => "The request contains one or more invalid arguments.",
        "code" => 400,
    ],
    Exception::TODO_NOT_FOUND => [
        "name" => Exception::TODO_NOT_FOUND,
        "description" => "Todo with the requested ID could not be found.",
        "code" => 404,
    ],
    Exception::TODO_ALREADY_EXISTS => [
        "name" => Exception::TODO_ALREADY_EXISTS,
        "description" => "Todo with the requested ID already exists.",
        "code" => 409,
    ],
    Exception::GENERAL_UNAUTHORIZED => [
        "name" => Exception::GENERAL_UNAUTHORIZED,
        "description" => "You must be logged in to access this resource.",
        "code" => 401,
    ],
    Exception::GENERAL_FORBIDDEN => [
        "name" => Exception::GENERAL_FORBIDDEN,
        "description" => "You do not have permission to perform this action.",
        "code" => 403,
    ],
    Exception::GENERAL_TENANT_REQUIRED => [
        "name" => Exception::GENERAL_TENANT_REQUIRED,
        "description" =>
            'An active tenant is required for this request. Provide a valid "X-Tenant-Id" header.',
        "code" => 400,
    ],
    Exception::USER_ALREADY_EXISTS => [
        "name" => Exception::USER_ALREADY_EXISTS,
        "description" => "A user with the same email already exists.",
        "code" => 409,
    ],
    Exception::USER_NOT_FOUND => [
        "name" => Exception::USER_NOT_FOUND,
        "description" => "User with the requested ID could not be found.",
        "code" => 404,
    ],
    Exception::USER_INVALID_CREDENTIALS => [
        "name" => Exception::USER_INVALID_CREDENTIALS,
        "description" => "Invalid email and/or password.",
        "code" => 401,
    ],
    Exception::USER_BLOCKED => [
        "name" => Exception::USER_BLOCKED,
        "description" => "This user has been blocked.",
        "code" => 403,
    ],
    Exception::USER_PASSWORD_TOO_WEAK => [
        "name" => Exception::USER_PASSWORD_TOO_WEAK,
        "description" => "Password does not meet the minimum requirements.",
        "code" => 400,
    ],
    Exception::SESSION_NOT_FOUND => [
        "name" => Exception::SESSION_NOT_FOUND,
        "description" => "Session with the requested ID could not be found.",
        "code" => 401,
    ],
    Exception::OAUTH2_PROVIDER_DISABLED => [
        "name" => Exception::OAUTH2_PROVIDER_DISABLED,
        "description" =>
            "This OAuth2 provider is not configured on the server.",
        "code" => 501,
    ],
    Exception::OAUTH2_PROVIDER_UNSUPPORTED => [
        "name" => Exception::OAUTH2_PROVIDER_UNSUPPORTED,
        "description" => "This OAuth2 provider is not supported.",
        "code" => 400,
    ],
    Exception::OAUTH2_STATE_INVALID => [
        "name" => Exception::OAUTH2_STATE_INVALID,
        "description" =>
            "The OAuth2 state parameter is invalid or has expired. Please try signing in again.",
        "code" => 401,
    ],
    Exception::OAUTH2_MISSING_CODE => [
        "name" => Exception::OAUTH2_MISSING_CODE,
        "description" =>
            "The OAuth2 provider did not return an authorization code.",
        "code" => 400,
    ],
    Exception::TENANT_NOT_FOUND => [
        "name" => Exception::TENANT_NOT_FOUND,
        "description" => "Tenant with the requested ID could not be found.",
        "code" => 404,
    ],
    Exception::TENANT_SLUG_ALREADY_EXISTS => [
        "name" => Exception::TENANT_SLUG_ALREADY_EXISTS,
        "description" => "A tenant with the same slug already exists.",
        "code" => 409,
    ],
    Exception::MEMBERSHIP_NOT_FOUND => [
        "name" => Exception::MEMBERSHIP_NOT_FOUND,
        "description" => "Membership with the requested ID could not be found.",
        "code" => 404,
    ],
    Exception::MEMBERSHIP_ALREADY_EXISTS => [
        "name" => Exception::MEMBERSHIP_ALREADY_EXISTS,
        "description" => "This user is already a member of the tenant.",
        "code" => 409,
    ],
    Exception::MEMBERSHIP_LAST_OWNER => [
        "name" => Exception::MEMBERSHIP_LAST_OWNER,
        "description" => "The tenant must always have at least one owner.",
        "code" => 409,
    ],
];
