<?php

namespace Clarus\Auth\Validator;

use Utopia\Validator;

class EmailAddress extends Validator
{
    public function getDescription(): string
    {
        return 'Value must be a valid email address.';
    }

    public function isValid(mixed $value): bool
    {
        if (!\is_string($value) || $value === '') {
            return false;
        }

        if (\strlen($value) > 320) {
            return false;
        }

        return \filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function isArray(): bool
    {
        return false;
    }

    public function getType(): string
    {
        return self::TYPE_STRING;
    }
}
