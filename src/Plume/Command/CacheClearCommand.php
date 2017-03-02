<?php

/**
 * @package Plume
 * @author renshan <1005110700@qq.com>
 */

namespace Plume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheClearCommand extends Command
{
	public function configure()
	{
		$this->setName('plume:cache:clear');
		$this->setDescription('清除plume缓存');
		$this->setHelp('清除plume缓存');
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$cache_dir = CONSOLE_PATH.'/../var/cache';

		if (is_dir($cache_dir)) {
			$this->removeDirectory($cache_dir);
		}

		$style = new SymfonyStyle($input, $output);

		$style->success("缓存清除完成");
	}

	private function removeDirectory($path) {

		$files = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $path).'/{,.}*', GLOB_BRACE);
		foreach ($files as $file) {
			if ($file == $path.'/.' || $file == $path.'/..') { continue; }
			is_dir($file) ? $this->removeDirectory($file) : unlink($file);
		}
		rmdir($path);
		return;
	}
}

?>
