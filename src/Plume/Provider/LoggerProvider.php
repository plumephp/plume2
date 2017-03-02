<?php

namespace Plume\Provider;

use Plume\Plume;
use Pimple\Container;
use Plume\Provider\ProviderInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggerProvider implements ProviderInterface
{
	private $logger;
    private $container;

	public function register(Container $plume)
	{
		$plume['logger'] = $this;
        $this->container = $plume;

		$log_dir = $plume->getRootDir().'/../var/logs';

		if (!is_dir($log_dir)) {
			@mkdir($log_dir, 0755, true);
		}

		$plume['logger.file'] = $log_dir . '/' .$plume->getEnv() . '.log';

		$logger = new Logger('plume');
		$logger->pushHandler(new StreamHandler($plume->get('logger.file')));

		$this->logger = $logger;
	}

    public function setLogFile($file)
    {
        $log_dir = $this->container->getRootDir().'/../var/logs';
        $file = $log_dir . '/' . $file;
        $this->logger->pushHandler(new StreamHandler($file));
        return $this;
    }

    public function reset()
    {
        $log_dir = $this->container->getRootDir().'/../var/logs';
        $file = $log_dir . '/' . $this->container->getEnv() . '.log';

        $this->logger->pushHandler(new StreamHandler($file));
    }

	public function __call($method, $arguments)
	{
		if (method_exists($this->logger, $method)) {
			call_user_func_array([$this->logger, $method], $arguments);
		} 
	}

}


?>
