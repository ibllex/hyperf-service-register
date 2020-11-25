<?php

declare(strict_types=1);

namespace iBllex\ServiceRegister\Register;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use iBllex\ServiceRegister\Contract\ConsulAgent;
use iBllex\ServiceRegister\Contract\RegistrationInterface;
use Psr\Container\ContainerInterface;

class ConsulRegistration implements RegistrationInterface
{
    /**
     * @var ConsulAgent
     */
    protected $agent;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $registeredServices;

    public function __construct(ContainerInterface $container)
    {
        $this->agent = $container->get(ConsulAgent::class);
        $this->config = $container->get(ConfigInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function publish(string $name, string $protocol, string $host, int $port, array $service)
    {
        if (!in_array($protocol, ['grpc', 'http', 'jsonrpc-http', 'jsonrpc', 'jsonrpc-tcp-length-check'], true)) {
            $this->logger->info(sprintf('Unsupported protocol %s, skip.', $protocol));

            return;
        }

        $this->logger->debug(sprintf('Service %s is registering to the consul.', $name));

        if ($this->isRegistered($name, $host, $port, $protocol)) {
            $this->logger->info(sprintf('Service %s has been already registered to the consul.', $name));

            return;
        }

        if (isset($service['id']) && $service['id']) {
            $nextId = $service['id'];
        } else {
            $nextId = $this->generateId($this->getLastServiceId($name));
        }

        $requestBody = $this->genRequestBody($name, $nextId, $protocol, $host, $port);
        $response = $this->agent->registerService($requestBody);

        if ($response->getStatusCode() === 200) {
            $this->registeredServices[$name][$protocol][$host][$port] = true;
            $this->logger->info(sprintf('Service %s:%s register to the consul successfully.', $name, $nextId));
        } else {
            $this->logger->warning(sprintf('Service %s register to the consul failed.', $name));
        }
    }

    /**
     * @param string $name
     * @param string $id
     * @param string $protocol
     * @param string $address
     * @param int    $port
     *
     * @return array
     */
    protected function genRequestBody(string $name, string $id, string $protocol, string $address, int $port): array
    {
        $interval = $this->config->get('service_register.interval', '2s');
        $deregisterAfter = $this->config->get('service_register.deregister_after', '90m');

        $requestBody = [
            'Name' => $name,
            'ID' => $id,
            'Address' => $address,
            'Port' => $port,
            'Meta' => [
                'Protocol' => $protocol,
            ],
        ];

        if (in_array($protocol, ['http', 'jsonrpc-http'], true)) {
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => $deregisterAfter,
                'HTTP' => "http://{$address}:{$port}/",
                'Interval' => $interval,
            ];
        }
        if (in_array($protocol, ['jsonrpc', 'jsonrpc-tcp-length-check'], true)) {
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => $deregisterAfter,
                'TCP' => "{$address}:{$port}",
                'Interval' => $interval,
            ];
        }
        if ($protocol === 'grpc') {
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => $deregisterAfter,
                'GRPC' => "{$address}:{$port}/grpc.health.v1.Health/Check",
                'Interval' => $interval,
            ];
        }

        return $requestBody;
    }

    /**
     * Determine if a service is already registered.
     *
     * @param string $name
     * @param string $address
     * @param int    $port
     * @param string $protocol
     *
     * @return bool
     */
    protected function isRegistered(string $name, string $address, int $port, string $protocol): bool
    {
        if (isset($this->registeredServices[$name][$protocol][$address][$port])) {
            return true;
        }

        $response = $this->agent->services();
        if ($response->getStatusCode() !== 200) {
            $this->logger->warning(sprintf('Service %s register to the consul failed.', $name));

            return false;
        }

        $services = $response->json();
        $glue = ',';
        $tag = implode($glue, [$name, $address, $port, $protocol]);
        foreach ($services as $serviceId => $service) {
            if (!isset($service['Service'], $service['Address'], $service['Port'], $service['Meta']['Protocol'])) {
                continue;
            }
            $currentTag = implode($glue, [
                $service['Service'],
                $service['Address'],
                $service['Port'],
                $service['Meta']['Protocol'],
            ]);
            if ($currentTag === $tag) {
                $this->registeredServices[$name][$protocol][$address][$port] = true;

                return true;
            }
        }

        return false;
    }

    /**
     * generate service id.
     *
     * @param string $name
     */
    protected function generateId(string $name)
    {
        $exploded = explode('-', $name);
        $length = count($exploded);
        $end = -1;
        if ($length > 1 && is_numeric($exploded[$length - 1])) {
            $end = $exploded[$length - 1];
            unset($exploded[$length - 1]);
        }
        $end = intval($end);
        ++$end;
        $exploded[] = $end;

        return implode('-', $exploded);
    }

    /**
     * If multiple instances of a service are registered,
     * the IDs of the different instances are incremented according to the index.
     * Here we get the ID of the last instance.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getLastServiceId(string $name)
    {
        $maxId = -1;
        $lastService = $name;
        $services = $this->agent->services()->json();
        foreach ($services ?? [] as $id => $service) {
            if (isset($service['Service']) && $service['Service'] === $name) {
                $exploded = explode('-', (string) $id);
                $length = count($exploded);
                if ($length > 1 && is_numeric($exploded[$length - 1]) && $maxId < $exploded[$length - 1]) {
                    $maxId = $exploded[$length - 1];
                    $lastService = $service;
                }
            }
        }

        return $lastService['ID'] ?? $name;
    }
}
