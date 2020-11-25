# hyperf-service-register

This is a service registry component of [hyperf](https://github.com/hyperf/hyperf) that currently supports publishing services of the grpc, http, jsonrpc-http and jsonrpc protocol to consul.

We will support more service center in the future, like etcd, and you can also do it yourself and open a pull request, it's not difficult.

## Quick usage

#### 1. Install

```shell
composer require ibllex/hyperf-service-register
```

#### 2. Configure the services to publish

Edit `config/autoload/server.php` and add the `publish` option to the service you want to publish. just like this

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

The publish option requires at least two fields, `protocol` and `name`. You can also specify the `id` field, which is generated automatically if not specified.

**You must be aware that if your service is using the `grpc` protocol, you must manually add a health check route to the service.**

Edit your `config/routes.php` and add

```php
// You need to replace the 'grpc' parameter with your own grpc service name.
\iBllex\ServiceRegister\Grpc\Grpc::addHealthCheck('grpc');
```

#### 3. That's all.

## License

This library is under the [MIT](https://github.com/ibllex/hyperf-service-register/blob/main/LICENSE) license.

