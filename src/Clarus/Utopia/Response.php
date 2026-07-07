<?php

namespace Clarus\Utopia;

use Clarus\Utopia\Response\Model;
use Swoole\Http\Response as SwooleHTTPResponse;
use Utopia\Database\Document;
use Utopia\Http\Adapter\Swoole\Response as SwooleResponse;

class Response extends SwooleResponse
{
    public const MODEL_NONE = 'none';
    public const MODEL_ERROR = 'error';
    public const MODEL_HEALTH = 'health';
    public const MODEL_TODO = 'todo';
    public const MODEL_TODO_LIST = 'todoList';

    protected array $payload = [];

    /** @var array<string, Model> */
    protected static array $models = [];

    public function __construct(SwooleHTTPResponse $response)
    {
        parent::__construct($response);
    }

    public static function setModel(Model $model): void
    {
        self::$models[$model->getType()] = $model;
    }

    public function getModel(string $key): Model
    {
        if (!isset(self::$models[$key])) {
            throw new \Exception('Undefined model: ' . $key);
        }

        return self::$models[$key];
    }

    public static function hasModel(string $key): bool
    {
        return isset(self::$models[$key]);
    }

    public function dynamic(Document $document, string $model): void
    {
        $output = $this->output(clone $document, $model);

        if ($model === self::MODEL_NONE) {
            $this->noContent();
            return;
        }

        $this->json(!empty($output) ? $output : new \stdClass());
    }

    public function output(Document $document, string $model): array
    {
        $data = clone $document;
        $modelObject = $this->getModel($model);
        $output = [];

        $data = $modelObject->filter($data);

        if ($modelObject->isAny()) {
            $this->payload = $data->getArrayCopy();

            return $this->payload;
        }

        foreach ($modelObject->getRules() as $key => $rule) {
            if (!$data->isSet($key) && $rule['required']) {
                if (\array_key_exists('default', $rule)) {
                    $data->setAttribute($key, $rule['default']);
                } else {
                    throw new \Exception('Model ' . $modelObject->getName() . ' is missing response key: ' . $key);
                }
            }

            if (!$data->isSet($key) && !$rule['required']) {
                $output[$key] = null;
                continue;
            }

            if ($rule['array']) {
                if (!\is_array($data[$key])) {
                    throw new \Exception($key . ' must be an array of type ' . $rule['type']);
                }

                foreach ($data[$key] as $index => $item) {
                    if ($item instanceof Document) {
                        if (!self::hasModel($rule['type'])) {
                            throw new \Exception('Missing model for rule: ' . $rule['type']);
                        }

                        $data[$key][$index] = $this->output($item, $rule['type']);
                    }
                }
            } elseif ($data[$key] instanceof Document) {
                $data[$key] = $this->output($data[$key], $rule['type']);
            }

            $output[$key] = $data[$key];
        }

        $this->payload = $output;

        return $this->payload;
    }

    public function json(mixed $data): void
    {
        if (!\is_array($data) && !$data instanceof \stdClass) {
            throw new \Exception('Response body is not a valid JSON object.');
        }

        $this->payload = \is_array($data) ? $data : (array) $data;

        $this
            ->setContentType(self::CONTENT_TYPE_JSON, self::CHARSET_UTF8)
            ->send(\json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
