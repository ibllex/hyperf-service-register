<?php

declare(strict_types=1);

namespace iBllex\ServiceRegister\Grpc;

use iBllex\ServiceRegister\Grpc\Health\HealthCheckRequest;
use iBllex\ServiceRegister\Grpc\Health\HealthCheckResponse;

class HealthController
{
    public function check(HealthCheckRequest $request)
    {
        $message = new HealthCheckResponse();
        $message->setStatus(HealthCheckResponse\ServingStatus::SERVING);

        return $message;
    }

    public function watch(HealthCheckRequest $request)
    {
        $message = new HealthCheckResponse();
        $message->setStatus(HealthCheckResponse\ServingStatus::SERVING);

        return $message;
    }
}
