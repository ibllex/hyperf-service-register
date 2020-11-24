<?php

declare(strict_types=1);

namespace iBllex\ServiceRegister;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            'listeners' => [
            ],
        ];
    }
}
