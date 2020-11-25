[English](./README.md) | 中文

# hyperf-service-register

这是一个 [hyperf](https://github.com/hyperf/hyperf) 的服务注册库，目前支持将 grpc, http, jsonrpc-http 和 jsonrpc 协议的服务发布到 consul。

我们今后可能会支持更多的服务中心，例如 `etcd`，你也可以自己实现并提交 PR，添加新的服务中心支持并不难。

## 快速使用

#### 1. 安装

```shell
composer require ibllex/hyperf-service-register
```

#### 2. 配置需要发布的服务

编辑 `config/autoload/server.php` 并在想要发布的服务配置选项中添加 `publish` 选项，例如：

```php
<?php
...
return [
  	...
    'servers' => [
        [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9501,
            ...
            'publish' => [
                'protocol' => 'http',
                'name' => 'http-service',
            ],
        ],
        [
            'name' => 'grpc',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            ...
            'publish' => [
                'protocol' => 'grpc',
                'name' => 'grpc-service',
            ],
        ],
        [
            'name' => 'jsonrpc-http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9504,
            ...
            'publish' => [
                'protocol' => 'jsonrpc-http',
                'name' => 'jsonrpc-http-service',
            ],
        ],
    ],
  	...
];

```

`publish` 选项必须至少包含 `protocol` 和 `name` 字段，你也可以指定 `id` 字段，如果没有指定 `id` 的话我们会自动生成。

**需要注意的是如果你的服务使用 `grpc` 协议，你必须手动添加健康检查路由：**

编辑 `config/routes.php` 并添加：

```php
// 请将 'grpc' 参数替换为你自己的 grpc 服务名称
\iBllex\ServiceRegister\Grpc\Grpc::addHealthCheck('grpc');
```

#### 3. 就酱

## License

This library is under the [MIT](https://github.com/ibllex/hyperf-service-register/blob/main/LICENSE) license.

