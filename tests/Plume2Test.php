<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Plume\Plume;
use Plume\Router;

class Plume2Test extends TestCase
{
	private $app;
	private $map;
	private $request;

	/**
	 * @before
	 */
	public function startApp()
	{
		$_SERVER['SCRIPT_FILENAME'] = '/home/renshan/Development/PHP/plume/web/index.php';
		$app = new Plume;
		$app->env(Plume::ENV_CONSTRUCTOR);
		$this->app = $app;
	
		$request = Request::create('http://localhost/index.php/ace/index/index', 'GET', ['name' => 'renshan'], [], [], ['SCRIPT_FILENAME' => '/home/renshan/Development/PHP/plume-skeleton/web/index.php']);
		$this->request = $request;
		$this->map = Router::parse($this->app, $request);

	}

	public function testEnv()
	{
		$this->assertEquals(Plume::ENV_CONSTRUCTOR, $this->app->getEnv());
	}

	public function testRouter()
	{
		$map = $this->map;

		$this->assertEquals('Ace', $map['bundle']);
		$this->assertEquals('IndexController', $map['controller']);
		$this->assertEquals('indexAction', $map['action']);
	}

	public function testResponse()
	{
		$response = $this->app->parseAction();

		list ($twig, $template, $params) = $response;

		$this->assertTrue($twig instanceof \Twig_Environment);
		$this->assertEquals('@Ace/User/index.html', $template);
		$this->assertArrayHasKey('name', $params);	
	}

	public function testRedis()
	{
		/**
		 * void all echo of plume app
		 */
		ob_start();
		$this->app->run();
		ob_clean();
		ob_flush();
		$redis = $this->app->get('plume.redis');

		$name = 'plume';
		$redis->setEx('name', 5, $name);

		$this->assertEquals($name, $redis->get('name'));
	}

}

?>
