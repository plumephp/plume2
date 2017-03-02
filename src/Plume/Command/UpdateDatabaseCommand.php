<?php

/**
 * @package Plume
 * @author renshan <1005110700@qq.com>
 */

namespace Plume\Command;

use Plume\Plume;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;


class UpdateDatabaseCommand extends Command
{
	public function configure()
	{
		$this->setName('plume:database:update');
		$this->setDescription('更新数据库中的表');
		$this->setHelp("例如：plume:database:update --all\t更新所有表\n或者: plume:database:update Ace\t更新Ace包下的所有表");

		$this->addOption('all', InputArgument::OPTIONAL);
		$this->addArgument('name', InputArgument::OPTIONAL, '需要参数: name');

		if (!defined('CONSOLE_PATH')) {
			define('CONSOLE_PATH', dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
		}
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$update_all = boolval($input->getOption('all'));

		$classes = [];
		$source_path = realpath(CONSOLE_PATH.'/../src');
		$entity = 'Entity';

		if ($update_all) {
			$classes = $this->updateAll();
		} else {
			$name = $input->getArgument('name');
			$classes = $this->updateSingle($name);
		}

		$db_config = $this->getDbConfiguration();

		$capsule = $this->bootORM($db_config);

		foreach ($classes as $class) {

			/**
			 * @var $class_instance \Plume\Entity
			 */
			$class_instance = new $class;
			@$class_instance->create();
		}

	}

	private function getDbConfiguration()
	{
		$config_path = realpath(CONSOLE_PATH.'/../config');
		$config_file = $config_path . '/app.yml';

		if (!is_file($config_file)) {
			throw new \Exception("数据库配置文件不存在");
		}

		$config = Yaml::parse(file_get_contents($config_file));

		return $config['mysql'];
	}

	private function bootORM($config)
	{
		$capsule = new Capsule;

		$capsule->addConnection($config);

		$capsule->setEventDispatcher(new Dispatcher(new Container));

		$capsule->setAsGlobal();

		$capsule->bootEloquent();

		return $capsule;

	}


	private function updateAll()
	{

		$classes = [];
		$source_path = realpath(CONSOLE_PATH.'/../src');
		$entity = 'Entity';

		$source_dir = dir($source_path);

		while ($dir = $source_dir->read()) {
			if ($dir == '.' || $dir == '..') {
				continue;
			}

			/**
			 * 屏蔽不符合命名空间规范的文件夹
			 */
			if (!preg_match('/^[A-Z]([a-zA-Z]+)?$/', $dir)) {
				continue;
			}

			$entity_path = $source_path . '/' . $dir . '/' . $entity;

			/**
			 * 排除没有Entity的包
			 */
			if (!is_dir($entity_path)) {
				continue;
			}

			$namespaces = $this->readEntities($entity_path);
			foreach ($namespaces as $namespace) {
				array_push($classes, $namespace);
			}
		}

		return $classes;
	}

	public function updateSingle($bundle)
	{

		$source_path = realpath(CONSOLE_PATH.'/../src');

		if (!$bundle) {
			throw new \Exception("请指定要更新的包");
		}

		if (!preg_match('/^[A-Z]([a-zA-Z]+)?$/', $bundle)) {
			throw new \Exception("目录包名 \"{$bundle}\" 不符合规范");
		}

		if (!is_dir($source_path.'/'.$bundle)) {
			throw new \Exception("目录{$source_path}/{$bundle}不存在");
		}


		$classes = [];
		$entity = 'Entity';

		$entity_path = $source_path.'/'. $bundle . '/' .$entity;

		if (!is_dir($entity_path)) {
			return $classes;
		}

		return $this->readEntities($entity_path);

	}


	/**
	 * @param $path
	 * @return array
	 */
	private function readEntities($path)
	{
		$entities = [];


		$entity_dir = dir($path);

		$source_path = realpath(CONSOLE_PATH.'/../src');

		while ($entity = $entity_dir->read()) {
			if (!preg_match('/^[A-Z]([a-zA-Z]+)?\.php$/', $entity)) {
				continue;
			}

			$namespace = str_replace('/', '\\', ltrim( $path, $source_path));
			$class = rtrim($namespace . '\\' .$entity, '\.php');

			array_push($entities, $class);
		}

		return $entities;

	}

}

?>
