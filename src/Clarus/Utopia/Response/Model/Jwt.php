<?php

namespace Clarus\Utopia\Response\Model;

use Clarus\Utopia\Response;
use Clarus\Utopia\Response\Model;

class Jwt extends Model
{
    public function __construct()
    {
        $this->addRule('jwt', [
            'type' => self::TYPE_STRING,
            'description' => 'A short-lived JSON Web Token bound to the current user and active tenant. Send it as `Authorization: Bearer <jwt>`.',
            'default' => '',
            'example' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
        ]);
    }

    public function getName(): string
    {
        return 'Jwt';
    }

    public function getType(): string
    {
        return Response::MODEL_JWT;
    }
}
