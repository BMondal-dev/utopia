<?php

use Clarus\Auth\Jwt;
use Clarus\Database\Factory as DatabaseFactory;
use Utopia\Auth\Hashes\Argon2;
use Utopia\Cache\Adapter\Memory as MemoryCache;
use Utopia\Cache\Adapter\Redis as RedisCache;
use Utopia\Cache\Cache;
use Utopia\Database\Adapter\Postgres;
use Utopia\Database\Database;
use Utopia\Database\PDO;
use Utopia\Database\Validator\Authorization;
use Utopia\DI\Container;
use Utopia\Http\Http;
use Utopia\Pools\Adapter\Swoole as SwoolePool;
use Utopia\Pools\Group;
use Utopia\Pools\Pool;
use Utopia\System\System;

global $container;

$container = new Container();

$container->set("authorization", fn () => new Authorization());

$container->set("hash", fn () => new Argon2());

$container->set(
    "jwt",
    fn () => new Jwt(System::getEnv("APP_SECRET", ""), APP_AUTH_JWT_TTL_SECONDS),
);

$container->set("cache", function () {
    $adapter = System::getEnv("_APP_CACHE_ADAPTER", "");

    if (
        $adapter === "memory" ||
        System::getEnv("_APP_ENV", Http::MODE_TYPE_PRODUCTION) ===
            Http::MODE_TYPE_DEVELOPMENT
    ) {
        return new Cache(new MemoryCache());
    }

    $host = System::getEnv("REDIS_HOST", "redis");
    $port = (int) System::getEnv("REDIS_PORT", 6379);
    $pass = System::getEnv("REDIS_PASS", "");

    $redis = new \Redis();
    @$redis->pconnect($host, $port);

    if ($pass !== "") {
        $redis->auth($pass);
    }

    $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);

    return new Cache(new RedisCache($redis));
});

$container->set("pools", function () {
    $host = System::getEnv("DB_HOST", "postgres");
    $port = System::getEnv("DB_PORT", "5432");
    $name = System::getEnv("DB_NAME", "clarus");
    $user = System::getEnv("DB_USER", "clarus");
    $pass = System::getEnv("DB_PASSWORD", "secret");
    $poolSize = (int) System::getEnv("DB_POOL_SIZE", "14");

    $pool = new Pool(new SwoolePool(), "db", $poolSize, function () use (
        $host,
        $port,
        $name,
        $user,
        $pass,
    ) {
        return new PDO(
            "pgsql:host={$host};port={$port};dbname={$name};connect_timeout=3",
            $user,
            $pass,
            Postgres::getPDOAttributes(),
        );
    });

    $group = new Group();
    $group->add($pool);
    return $group;
});

$container->set(
    "db",
    function (Authorization $authorization, Cache $cache) {
        $host = System::getEnv("DB_HOST", "postgres");
        $port = System::getEnv("DB_PORT", "5432");
        $name = System::getEnv("DB_NAME", "clarus");
        $user = System::getEnv("DB_USER", "clarus");
        $pass = System::getEnv("DB_PASSWORD", "secret");

        $pdo = new PDO(
            "pgsql:host={$host};port={$port};dbname={$name};connect_timeout=3",
            $user,
            $pass,
            Postgres::getPDOAttributes(),
        );

        return DatabaseFactory::platform(
            new Database(new Postgres($pdo), $cache),
            $authorization,
            $cache,
        );
    },
    ["authorization", "cache"],
);
