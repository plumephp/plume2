<?php

namespace Plume;

use Pimple\Container;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\HttpFoundation\Response;
use Plume\Exception\RouteNotFoundException;
use Plume\Provider\LoggerProvider;
use Plume\Provider\RedisProvider;
use Plume\Provider\ExceptionProvider;


class Plume extends Container
{
	private $env;
	private $booted;
	private $request;
	private $config;

	const ENV_DEV ='dev';
	const ENV_PROD = 'prod';
	const ENV_CONSTRUCTOR = 'constructor';

	public function run()
	{
		if ($this->booted) {
			return;
		}

		ob_start();

		/**
		 * 如果是生产环境，则不输出dump
		 */
		if ($this->getEnv() === self::ENV_PROD) {
			VarDumper::setHandler(function($var) {
				return;
			});
		}

		$this->register(new ExceptionProvider());
		$this->register(new LoggerProvider());
		$this->register(new RedisProvider());

		$this->booted = true;

		$db_config = $this->getConfiguration('mysql');
        if (!array_key_exists('charset', $db_config)) {
            $db_config['charset'] = 'utf8';
        }
        if (!array_key_exists('collation', $db_config)) {
            $db_config['collation'] = 'utf8_unicode_ci';
        }
		Database::bootDatabase($db_config);

		/**
		 * 是否自动开启session
		 */
		$session_config = $this->getConfiguration('session');
		if (array_key_exists('auto_start', $session_config) && $session_config['auto_start'] == true) {
			session_start();
		}
		

		/**
		 * 解析路由并返回控制器的执行结果
		 */
		$response = $this->parseAction();

		/**
		 * action 返回的内容是否是Symfony\Component\HttpFoundation\Response
		 */
		$is_http_foundation_response = $response instanceof Response;

		/**
		 * 输出 action 的内容给请求端
		 * 一旦 `响应被发送，后面的代码绝对不能有任何输出`
		 */
		if ($is_http_foundation_response) {
			$response->send();
		} else if ((is_array($response) && (list($twig, $template, $parameters) = $response) && ($twig instanceof \Twig_Environment))) {
			echo $twig->render($template, $parameters);
		} else {
			throw new \Exception('控制器的返回值必须是标准Response或者渲染的试图');
		}
		ob_end_flush();
	}

	
		/**
		 *
		 * 解析路由并返回控制器的执行结果
		 * @return mixed
		 * @throws RouteNotFoundException
		 * @throws \Exception
		 *
		 */
		public function parseAction()
		{
			$request = new Request();
			$this->request = $request->getRequest();
			$maps = Router::parse($this, $this->getRequest());
			$map_namespace = $maps['bundle'] . '\Controller\\' . $maps['controller'];

			$controller_file = $this->getSrcDir() . '/' . str_replace('\\', '/', $map_namespace) . '.php';

			/**
			 * Controller 文件不存在
			 */
			if (!file_exists($controller_file) && $this->getEnv() !== 'prod') {
				throw new RouteNotFoundException("路由不存在");
			}

			/**
			 * Controller 文件存在但命名空间不正确
			 */
			if (!class_exists($map_namespace)) {
				throw new RouteNotFoundException("路由不存在");
			}

			$refClass = new \ReflectionClass($map_namespace);
			$methods = $refClass->getMethods();
			$target_action = null;
			
			foreach ($methods as $method) {
				if ($method->name === $maps['action']) {
					$target_action = $method->name;
					break;
				}
			}

			/**
			 * path info 中的 action 不存在于 path info 所指定的 Controller 内
			 */

			if ($target_action === null) {
				throw new RouteNotFoundException("路由不存在");
			}

			$controller_instance = new $map_namespace;
			
			/**
			 * 注入container
			 */
			$response = \call_user_func_array([$controller_instance, 'setContainer'], [$this]);

			/**
			 * 调用 action
			 */
			return \call_user_func([$controller_instance, $target_action]);
		}

	/**
	 * 设置开发环境变量
	 * 可选值为：dev、prod、constructor
	 * 当env为contructor时，环境为plume本身开发
	 * @param $env
	 */
	public function env($env = 'dev')
	{

		$env = strtolower($env);

		if ($env != 'dev' && $env != 'prod' && $env != 'constructor') {
			$env = 'dev';
		}

		$this->env = $env;
	}

	/**
	 * 获取开发环境变量
	 * @return string
	 */
	public function getEnv()
	{
		if (empty($this->env)) {
			$this->env = 'dev';
		}

		return $this->env;
	}

	/**
	 * 获取程序入口
	 * @return string
	 */
	public function getRootDir()
	{
		$root_file = realpath($_SERVER['SCRIPT_FILENAME']);

		$paths = explode('/', $root_file);

		$root = '/';

		for ($i=0; $i<count($paths)-1; ) {
			$root = $root . '/' . $paths[$i++];
		}

		return realpath($root);
	}

	/**
	 * 获取当前的请求
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * 获取逻辑代码的根目录
	 * @return string
	 */
	public function getSrcDir()
	{
		$path = realpath($this->getRootDir() .'/../');

		if ($this->getEnv() === 'constructor') {
			return realpath($path . '/' . 'app');
		}

		return realpath($path . '/' . 'src');
	}

	/**
	 * 检测程序是否启动过
	 * @return bool
	 */
	public function hasBooted()
	{
		return boolval($this->booted);
	}

	/**
	 * 获取缓存目录
	 * @return string
	 */
	public function getCacheDir()
	{
		$src_dir = $this->getSrcDir();

		if (!is_dir($src_dir.'/../var/cache')) {
			@mkdir($src_dir.'/../var/cache', 0755, true);
		}

		return realpath($src_dir.'/../var/cache/');
	}

	/**
	 * 获取项目的包
	 * @return array
	 */
	public function getBundles()
	{
		$bundles = [];

		$src_dir = $this->getSrcDir();

		$dir_handle = dir($src_dir);

		while ($dir = $dir_handle->read()) {
			if ($dir === '..' || $dir === '.') {
				continue;
			}


			/**
			 * 过滤首字母是小写的目录
			 */
			if (preg_match('/a-z/', $dir[0])) {
				continue;
			}

			$bundles[$dir] = realpath($src_dir . '/' . $dir);

		}

		return $bundles;

	}

	/**
	 * 获取$container[key]
	 * @return mixed|null
	 */
	public function get($key)
	{
		$keys = $this->keys();

		foreach ($keys as $_key) {
			if ($_key === $key) {
				return $this[$key];
			}
		}

		return null;
	}

	/**
	 * 获取配置文件
	 * @param $key
	 * @return array
	 */
	public function getConfiguration($key = null)
	{
		if ($this->config == null) {
			$this->config = $config = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->getRootDir().'/../config/app.yml'));
		} else {
			$config = $this->config;
		}

		if ($key === null) {
			return $config;
		}

		if (array_key_exists($key, $config)) {
			return $config[$key];
		}

		return [];
	}

}

