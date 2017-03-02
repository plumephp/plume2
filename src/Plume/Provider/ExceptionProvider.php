<?php

/**
 * @package Plume
 * @author renshan <1005110700@qq.com>
 */

namespace Plume\Provider;

use Plume\Plume;
use Pimple\Container;
use Plume\Provider\ProviderInterface;
use Plume\Exception\RouteNotFoundException;

class ExceptionProvider implements ProviderInterface
{

	private $container;

	public function register(Container $container)
	{
		$this->container = $container;
		set_exception_handler([$this, 'exceptionHandler']);
	}

	public function exceptionHandler($e) 
	{
		$this->container->get('logger')->error($e);
		if ($e instanceof RouteNotFoundException) {
			$this->routeNotFound($e);
		} else {
			$this->serverError($e);
		}
	}

	public function routeNotFound($e)
	{
		header("HTTP/2.0 404 Nout Found");

		list($twig, $template, $parameters) = $this->render("404.html", ['trace' =>  $e]);

		echo $twig->render($template, $parameters);
	}

	public function serverError($e)
	{
		header("HTTP/2.0 500 Internal Error");
		list($twig, $template, $parameters) = $this->render("500.html", ['trace' =>  $e]);
		echo $twig->render($template, $parameters);

	}

	private function render($template, $parameters)
	{
		$dir = $this->container->getSrcDir();

		$twig_autoloader = realpath($dir.'/../vendor/twig/twig/lib/Twig/').'/Autoloader.php';

		/**
		 * 没有安装twig
		 */
		if (!is_file($twig_autoloader)) {
			return;
		}

		require_once($twig_autoloader);

		$debug = $this->container->getEnv() === Plume::ENV_DEV ? true : false;

		$loader = new \Twig_Loader_Filesystem([]);
		$view_path = $debug ?  __DIR__.'/../Resources/Views' : $this->container->getSrcDir().'/../config/resources/views';
		$loader->addPath($view_path);

		/**
		 * 尝试创建缓存目录
		 */
		if (!is_dir($this->container->getCacheDir())) {
			@mkdir($this->getContainer()->getCacheDir(), 0755, true);
		}


		$twig = new \Twig_Environment($loader, ['cache' => $this->container->getCacheDir(), 'debug' => $debug]);

		if ($this->container->getEnv() !== Plume::ENV_PROD) {
			$twig->addExtension(new \Twig_Extension_Debug());
		}

		return [$twig, $template, $parameters];

	}
}

?>
