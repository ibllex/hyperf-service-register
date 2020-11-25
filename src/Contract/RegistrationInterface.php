<?php

declare(strict_types=1);

namespace iBllex\ServiceRegister\Contract;

/**
 * RegistrationInterface is a service registration interface
 * that can be switched between different implementations
 * to publish services to different service centers.
 */
interface RegistrationInterface
{
    /**
     * Publish service to a service center.
     *
     * @param string $name
     * @param string $protocol
     * @param string $host
     * @param int    $port
     * @param array  $service
     */
    public function publish(string $name, string $protocol, string $host, int $port, array $service);
}
