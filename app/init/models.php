<?php

use Clarus\Utopia\Response;
use Clarus\Utopia\Response\Model\BaseList;
use Clarus\Utopia\Response\Model\Error;
use Clarus\Utopia\Response\Model\Health;
use Clarus\Utopia\Response\Model\Jwt;
use Clarus\Utopia\Response\Model\Membership;
use Clarus\Utopia\Response\Model\Session;
use Clarus\Utopia\Response\Model\Tenant;
use Clarus\Utopia\Response\Model\Todo;
use Clarus\Utopia\Response\Model\User;

Response::setModel(new Error());
Response::setModel(new Health());
Response::setModel(new Todo());
Response::setModel(
    new BaseList(
        "Todos List",
        Response::MODEL_TODO_LIST,
        "todos",
        Response::MODEL_TODO,
    ),
);
Response::setModel(new User());
Response::setModel(new Session());
Response::setModel(new Jwt());
Response::setModel(new Tenant());
Response::setModel(
    new BaseList(
        "Tenants List",
        Response::MODEL_TENANT_LIST,
        "tenants",
        Response::MODEL_TENANT,
    ),
);
Response::setModel(new Membership());
Response::setModel(
    new BaseList(
        "Memberships List",
        Response::MODEL_MEMBERSHIP_LIST,
        "memberships",
        Response::MODEL_MEMBERSHIP,
    ),
);
