<?php

namespace Clarus\Utopia\Response;

use Utopia\Database\Document;

abstract class Model
{
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_FLOAT = 'double';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_DATETIME_EXAMPLE = '2020-10-15T06:38:00.000+00:00';
    public const TYPE_ENUM = 'enum';

    protected bool $none = false;

    protected bool $any = false;

    protected bool $public = true;

    protected array $rules = [];

    public function filter(Document $document): Document
    {
        return $document;
    }

    abstract public function getName(): string;

    abstract public function getType(): string;

    public function getRules(): array
    {
        return $this->rules;
    }

    protected function addRule(string $key, array $options): self
    {
        $this->rules[$key] = \array_merge([
            'required' => true,
            'array' => false,
            'description' => '',
            'example' => '',
            'sensitive' => false,
            'readOnly' => false,
        ], $options);

        return $this;
    }

    public function isAny(): bool
    {
        return $this->any;
    }

    public function isNone(): bool
    {
        return $this->none;
    }
}
