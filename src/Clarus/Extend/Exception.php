<?php

namespace Clarus\Extend;

use Utopia\Config\Config;

class Exception extends \Exception
{
    /** General */
    public const string GENERAL_UNKNOWN = "general_unknown";
    public const string GENERAL_SERVER_ERROR = "general_server_error";
    public const string GENERAL_ROUTE_NOT_FOUND = "general_route_not_found";
    public const string GENERAL_ARGUMENT_INVALID = "general_argument_invalid";

    /** Authorization */
    public const string GENERAL_UNAUTHORIZED = "general_unauthorized";
    public const string GENERAL_FORBIDDEN = "general_forbidden";
    public const string GENERAL_TENANT_REQUIRED = "general_tenant_required";

    /** Users */
    public const string USER_ALREADY_EXISTS = "user_already_exists";
    public const string USER_NOT_FOUND = "user_not_found";
    public const string USER_INVALID_CREDENTIALS = "user_invalid_credentials";
    public const string USER_BLOCKED = "user_blocked";
    public const string USER_PASSWORD_TOO_WEAK = "user_password_too_weak";

    /** Sessions */
    public const string SESSION_NOT_FOUND = "session_not_found";

    /** OAuth2 */
    public const string OAUTH2_PROVIDER_DISABLED = "oauth2_provider_disabled";
    public const string OAUTH2_PROVIDER_UNSUPPORTED = "oauth2_provider_unsupported";
    public const string OAUTH2_STATE_INVALID = "oauth2_state_invalid";
    public const string OAUTH2_MISSING_CODE = "oauth2_missing_code";

    /** Tenants */
    public const string TENANT_NOT_FOUND = "tenant_not_found";
    public const string TENANT_SLUG_ALREADY_EXISTS = "tenant_slug_already_exists";

    /** Memberships */
    public const string MEMBERSHIP_NOT_FOUND = "membership_not_found";
    public const string MEMBERSHIP_ALREADY_EXISTS = "membership_already_exists";
    public const string MEMBERSHIP_LAST_OWNER = "membership_last_owner";

    /** Todos */
    public const string TODO_NOT_FOUND = "todo_not_found";
    public const string TODO_ALREADY_EXISTS = "todo_already_exists";

    protected string $type = "";

    protected array $errors = [];

    public function __construct(
        string $type = self::GENERAL_UNKNOWN,
        ?string $message = null,
        int|string|null $code = null,
        ?\Throwable $previous = null,
    ) {
        $this->errors = Config::getParam("errors", []);
        $this->type = $type;
        $this->code = $code ?? ($this->errors[$type]["code"] ?? 500);

        if (\is_string($this->code)) {
            $this->code = \is_numeric($this->code) ? (int) $this->code : 500;
        }

        $this->message =
            $message ??
            ($this->errors[$type]["description"] ??
                "An unknown error has occurred.");

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
            $this->code = $this->errors[$type]["code"];
            $this->message = $this->errors[$type]["description"];
        }
    }
}
