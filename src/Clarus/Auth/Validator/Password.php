<?php

namespace Clarus\Auth\Validator;

use Utopia\Validator;

class Password extends Validator
{
    public function __construct(
        private readonly int $minLength = 8,
        private readonly int $maxLength = 256,
    ) {
    }

    public function getDescription(): string
    {
        return "Password must be between {$this->minLength} and {$this->maxLength} characters.";
    }

    public function isValid(mixed $value): bool
    {
        if (!\is_string($value)) {
            return false;
        }

        $length = \mb_strlen($value);

        return $length >= $this->minLength && $length <= $this->maxLength;
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
