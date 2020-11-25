<?php

declare(strict_types=1);

namespace iBllex\ServiceRegister\Grpc;

use Hyperf\HttpServer\Router\Router;

class Grpc
{
    /**
     * An easy way to add health check to Grpc services.
     *
     * @param string $server
     */
    public static function addHealthCheck(string $server)
    {
        Router::addServer($server, function () {
            Router::addGroup('/grpc.health.v1.Health', function () {
                Router::post('/Check', 'iBllex\ServiceRegister\Grpc\HealthController@check');
                Router::post('/Watch', 'iBllex\ServiceRegister\Grpc\HealthController@watch');
            });
        });
    }
}
