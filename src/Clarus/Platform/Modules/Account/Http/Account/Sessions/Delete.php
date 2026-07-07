<?php

namespace Clarus\Platform\Modules\Account\Http\Account\Sessions;

use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;

class Delete extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return "deleteSession";
    }

    public function __construct()
    {
        $this->setHttpMethod(Action::HTTP_REQUEST_METHOD_DELETE)
            ->setHttpPath("/v1/account/sessions/current")
            ->desc("Log out and destroy the current session.")
            ->groups(["api", "account"])
            ->label("auth", true)
            ->inject("response")
            ->inject("db")
            ->inject("authorization")
            ->inject("session")
            ->callback($this->action(...));
    }

    public function action(
        Response $response,
        Database $db,
        Authorization $authorization,
        Document $session,
    ): void {
        if (!$session->isEmpty()) {
            $authorization->skip(
                fn () => $db->deleteDocument("sessions", $session->getId()),
            );
        }

        // Expire the cookie in the browser (queuing it removed, unsent, has no effect on the client).
        $response->addCookie(
            name: APP_AUTH_SESSION_COOKIE,
            value: "",
            expire: \time() - 3600,
            path: "/",
            httponly: true,
            sameSite: "Lax",
        );
        $response->dynamic(new Document(), Response::MODEL_NONE);
    }
}
