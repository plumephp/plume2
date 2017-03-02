<?php

/**
 * @package Plume
 * @author renshan <1005110700@qq.com>
 */

namespace Plume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ModelGenerateCommand extends Command
{
	public function configure()
	{
		$this->setName('plume:model:generate');
		$this->setDescription('创建一个数据库Model');
		$this->setHelp('例如：plume:model:generate Ace:User(大小写敏感)');

		$this->addArgument('name', InputArgument::REQUIRED, '需要参数: name');

		if (!defined('CONSOLE_PATH')) {
			define('CONSOLE_PATH', dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
		}
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$name = $input->getArgument('name');

		$model_info = explode(':', $name);

		if (count($model_info) != 2) {
			throw new InvalidArgumentException('参数格式错误');
		}

		$source_path = realpath(CONSOLE_PATH.'/../src');

		$bundle_path = $source_path . '/' . $model_info[0];
		$model_path = $bundle_path . '/Model';
		$model_file = $model_path . '/' . $model_info[1] . '.php';

		if (!is_dir($bundle_path)) {
			throw new \Exception("目录\"{$source_path}/{$model_info[0]}\"不存在");
		}

		if (!is_dir($model_path)) {
			@mkdir("{$source_path}/{$model_info[0]}/Model", 0755, true);
		}

		if (is_file($model_file)) {
			throw new \Exception("文件\"{$model_file}\"已存在");
		}

		$class_content = $this->generateContent($model_info[0], $model_info[1]);

		$style = new SymfonyStyle($input, $output);


		if (file_put_contents($model_file, $class_content) !== false) {
			$style->success("创建成功");
		} else {
			$style->error("创建失败,无法创建文件\"{$model_file}\"");
		}

	}

	private function generateContent($bundle, $class)
	{
		$table = strtolower($class);

		return
<<<php
<?php
/**
 * Plume 自动生成
 */
 
namespace $bundle\Model;

use Illuminate\Database\Eloquent\Model;

class $class extends Model
{
	protected \$table = "$table";
	
	// 在这里写你的逻辑
}

?>
php;

	}
}

?>
