<?php

/**
 * @package plume
 * @author renshan <1005110700@qq.com>
 */

namespace Plume;

use Pimple\Container;

class Controller
{
	private $container;
	const VIWE_PATH = '/Views';

	/**
	 * 注入容器,这个方法只会调用一次
	 * @param $plume
	 * @return bool|null
	 */
	public function setContainer(Plume $plume)
	{
		if ($this->container) {
			return false;
		}

		$this->container = $plume;
	}

	/**
	 * 获得容器
	 * @return Plume\Plume
	 */
	public function getContainer() 
	{
		return $this->container;
	}

	/**
	 * 通过容器获取Provider
	 * @param string
	 * @return mixed
	 */
	public function get($key)
	{
		return $this->getContainer()->get($key);
	}

	/**
	 * 获取请求
	 * @return Plume\Request
	 */
	public function getRequest()
	{
		return $this->getContainer()->getRequest();
	}

	/**
	 * 渲染一个模板
	 * @param $template
	 * @param $parameters
	 */
	public function render($template, Array $parameters = [])
	{
		$dir = $this->getContainer()->getSrcDir();

		$dir_handle = dir($dir);

		$view_paths = [];

		while ($d = $dir_handle->read()) {
			if ($d === '..' || $d === '.') {
				continue;
			}


			$view_path = realpath($dir . '/' . $d . '/' . self::VIWE_PATH);

			if ($view_path && is_dir(realpath($view_path))) {
				$view_paths[$d] = $view_path;
			}

		}

		/**
		 * 不存在任何模板目录
		 */
		if (count($view_paths) === 0) {
			return;
		}

		$twig_autoloader = realpath($dir.'/../vendor/twig/twig/lib/Twig/').'/Autoloader.php';

		/**
		 * 没有安装twig
		 */
		if (!is_file($twig_autoloader)) {
			return;
		}

		require_once($twig_autoloader);

		$loader = new \Twig_Loader_Filesystem([]);

		foreach ($view_paths as $k => $v) {
			$loader->addPath($v, $k);
		}

		/**
		 * 尝试创建缓存目录
		 */
		if (!is_dir($this->getContainer()->getCacheDir())) {
			@mkdir($this->getContainer()->getCacheDir(), 0755, true);
		}

		$debug = $this->getContainer()->getEnv() === Plume::ENV_PROD ? false : true;

		$twig = new \Twig_Environment($loader, ['cache' => $this->getContainer()->getCacheDir(), 'debug' => $debug]);



		/**
		 * 如果是开发模式，开启Profiler
		 */
		if ($this->getContainer()->getEnv() === Plume::ENV_DEV || $this->getContainer()->getEnv() === Plume::ENV_CONSTRUCTOR) {
			$twig->addExtension(new \Twig_Extension_Profiler(new \Twig_Profiler_Profile()));
		}

		return [$twig, $template, $parameters];
	}


	/**
	 * 获取session操作对象
	 * @return Symfony\Component\HttpFoundation\Session\Session
	 */
	public function getSession()
	{
		$session = $this->getRequest()->getSession();

		if ($session === null) {
			$session = new \Symfony\Component\HttpFoundation\Session\Session();
			$session->start();
			$this->getRequest()->setSession($session);
		}

		if (!$session->isStarted()) {
			$session->startt();
		}

		return $session;
	}
}

?>
