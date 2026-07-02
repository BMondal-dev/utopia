<?php

namespace Clarus\Platform\Modules\Todos\Http\Todos;

use Clarus\Extend\Exception;
use Clarus\Utopia\Response;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Duplicate as DuplicateException;
use Utopia\Database\Helpers\ID;
use Utopia\Database\Validator\Authorization;
use Utopia\Platform\Action;
use Utopia\Platform\Scope\HTTP;
use Utopia\Validator\Boolean;
use Utopia\Validator\Text;

class Create extends Action
{
    use HTTP;

    public static function getName(): string
    {
        return 'createTodo';
    }

    public function __construct()
    {
        $this
            ->setHttpMethod(Action::HTTP_REQUEST_METHOD_POST)
            ->setHttpPath('/v1/todos')
            ->desc('Create todo')
            ->groups(['api', 'todos'])
            ->param('todoId', '', new Text(36), 'Todo ID. Choose a custom ID, pass `unique()` to generate one, or leave empty to auto-generate.', true)
            ->param('title', '', new Text(APP_LIMIT_TODO_TITLE), 'Todo title.')
            ->param('description', '', new Text(APP_LIMIT_TODO_DESCRIPTION), 'Todo description.', true)
            ->param('completed', false, new Boolean(), 'Whether the todo is completed.', true)
            ->inject('response')
            ->inject('db')
            ->inject('authorization')
            ->callback($this->action(...));
    }

    public function action(
        string $todoId,
        string $title,
        string $description,
        bool $completed,
        Response $response,
        Database $db,
        Authorization $authorization,
    ): void {
        if ($title === '') {
            throw new Exception(Exception::GENERAL_ARGUMENT_INVALID, 'Param "title" is not optional.');
        }

        $todoId = ($todoId === '' || $todoId === 'unique()') ? ID::unique() : $todoId;

        $todo = new Document([
            '$id' => $todoId,
            '$permissions' => [],
            'title' => $title,
            'description' => $description,
            'completed' => $completed,
        ]);

        try {
            $todo = $authorization->skip(fn () => $db->createDocument('todos', $todo));
        } catch (DuplicateException) {
            throw new Exception(Exception::TODO_ALREADY_EXISTS);
        }

        $response
            ->setStatusCode(Response::STATUS_CODE_CREATED)
            ->dynamic($todo, Response::MODEL_TODO);
    }
}
