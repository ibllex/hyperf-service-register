<?php

declare(strict_types=1);

namespace iBllex\ServiceRegister\Register;

use Hyperf\Consul\Agent;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use Psr\Container\ContainerInterface;

class ConsulAgentFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new Agent(function () use ($container) {
            $config = $container->get(ConfigInterface::class);
            $token = $config->get('consul.token', '');
            $options = [
                'timeout' => 2,
                'base_uri' => $config->get('consul.uri', Agent::DEFAULT_URI),
            ];

            if (!empty($token)) {
                $options['headers'] = [
                    'X-Consul-Token' => $token,
                ];
            }

            return $container->get(ClientFactory::class)->create($options);
        });
    }
}
