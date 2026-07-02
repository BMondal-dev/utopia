<?php

namespace Clarus\Utopia\Response\Model;

use Clarus\Utopia\Response\Model;

class BaseList extends Model
{
    protected string $name = '';

    protected string $type = '';

    public function __construct(
        string $name,
        string $type,
        string $key,
        string $model,
        bool $paging = true,
    ) {
        $this->name = $name;
        $this->type = $type;

        if ($paging) {
            $this->addRule('total', [
                'type' => self::TYPE_INTEGER,
                'description' => 'Total number of ' . $key . ' that matched your query.',
                'default' => 0,
                'example' => 5,
            ]);
        }

        $this->addRule($key, [
            'type' => $model,
            'description' => 'List of ' . $key . '.',
            'default' => [],
            'array' => true,
        ]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
