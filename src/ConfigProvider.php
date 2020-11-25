<?php

declare(strict_types=1);

namespace iBllex\ServiceRegister;

use iBllex\ServiceRegister\Contract\ConsulAgent;
use iBllex\ServiceRegister\Contract\RegistrationInterface;
use iBllex\ServiceRegister\Listener\RegisterServiceListener;
use iBllex\ServiceRegister\Register\ConsulAgentFactory;
use iBllex\ServiceRegister\Register\ConsulRegistration;

class ConfigProvider
{
    public function __invoke(): array
    {
        !defined('BASE_PATH') && define('BASE_PATH', getcwd());

        return [
            'dependencies' => [
                ConsulAgent::class => ConsulAgentFactory::class,
                RegistrationInterface::class => ConsulRegistration::class,
            ],
            'listeners' => [
                RegisterServiceListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'ignore_annotations' => [
                        'type',
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for service register.',
                    'source' => __DIR__.'/../publish/service_register.php',
                    'destination' => BASE_PATH.'/config/autoload/service_register.php',
                ],
            ],
        ];
    }
}
