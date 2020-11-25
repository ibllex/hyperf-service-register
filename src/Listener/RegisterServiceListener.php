<?php

declare(strict_types=1);

namespace iBllex\ServiceRegister\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\MainWorkerStart;
use Hyperf\Server\Event\MainCoroutineServerStart;
use iBllex\ServiceRegister\Contract\RegistrationInterface;
use Psr\Container\ContainerInterface;

class RegisterServiceListener implements ListenerInterface
{
    /**
     * @var RegistrationInterface
     */
    protected $registration;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->registration = $container->get(RegistrationInterface::class);
        $this->config = $container->get(ConfigInterface::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function listen(): array
    {
        return [
            MainWorkerStart::class,
            MainCoroutineServerStart::class,
        ];
    }

    /**
     * @param MainCoroutineServerStart|MainWorkerStart $event
     */
    public function process(object $event)
    {
        $continue = true;
        while ($continue) {
            try {
                foreach ($this->getServers() as $name => $info) {
                    [$host, $port, $protocol, $service] = $info;
                    $this->registration->publish($name, $protocol, $host, $port, $service);
                }
                $continue = false;
            } catch (\Exception $throwable) {
                if (strpos($throwable->getMessage(), 'Connection failed') !== false) {
                    $this->logger->warning('Cannot register service, connection of service center failed, re-register after 10 seconds.');
                    sleep(10);
                } else {
                    throw $throwable;
                }
            }
        }
    }

    protected function getServers(): array
    {
        $result = [];
        $servers = $this->config->get('server.servers', []);
        foreach ($servers as $server) {
            if (!isset($server['publish']['name'], $server['publish']['protocol'], $server['name'], $server['host'], $server['port'])) {
                continue;
            }

            if (!$server['name']) {
                throw new \InvalidArgumentException('Invalid server name');
            }

            $publish = $server['publish']['name'];
            if (!$publish) {
                throw new \InvalidArgumentException('Invalid publish name');
            }

            $host = $server['host'];
            if (in_array($host, ['0.0.0.0', 'localhost'])) {
                $host = $this->getInternalIp();
            }

            if (!filter_var($host, FILTER_VALIDATE_IP)) {
                throw new \InvalidArgumentException(sprintf('Invalid host %s', $host));
            }

            $port = $server['port'];
            if (!is_numeric($port) || ($port < 0 || $port > 65535)) {
                throw new \InvalidArgumentException(sprintf('Invalid port %s', $port));
            }

            $port = (int) $port;
            if (isset($result[$publish])) {
                throw new \InvalidArgumentException(sprintf('Publish name %s already in use', $publish));
            }

            $result[$publish] = [$host, $port, $server['publish']['protocol'], $server['publish']];
        }

        return $result;
    }

    protected function getInternalIp(): string
    {
        $ips = swoole_get_local_ip();
        if (is_array($ips) && !empty($ips)) {
            return current($ips);
        }

        /** @var mixed|string $ip */
        $ip = gethostbyname(gethostname());

        if (is_string($ip)) {
            return $ip;
        }

        throw new \RuntimeException('Can not get the internal IP.');
    }
}
