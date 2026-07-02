<?php

namespace Clarus\Extend;

use Utopia\Config\Config;

class Exception extends \Exception
{
    /** General */
    public const string GENERAL_UNKNOWN = 'general_unknown';
    public const string GENERAL_SERVER_ERROR = 'general_server_error';
    public const string GENERAL_ROUTE_NOT_FOUND = 'general_route_not_found';
    public const string GENERAL_ARGUMENT_INVALID = 'general_argument_invalid';

    /** Todos */
    public const string TODO_NOT_FOUND = 'todo_not_found';
    public const string TODO_ALREADY_EXISTS = 'todo_already_exists';

    protected string $type = '';

    protected array $errors = [];

    public function __construct(
        string $type = self::GENERAL_UNKNOWN,
        ?string $message = null,
        int|string|null $code = null,
        ?\Throwable $previous = null,
    ) {
        $this->errors = Config::getParam('errors', []);
        $this->type = $type;
        $this->code = $code ?? ($this->errors[$type]['code'] ?? 500);

        if (\is_string($this->code)) {
            $this->code = \is_numeric($this->code) ? (int) $this->code : 500;
        }

        $this->message = $message ?? ($this->errors[$type]['description'] ?? 'An unknown error has occurred.');

        parent::__construct($this->message, (int) $this->code, $previous);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;

        if (isset($this->errors[$type])) {
            $this->code = $this->errors[$type]['code'];
            $this->message = $this->errors[$type]['description'];
        }
    }
}
