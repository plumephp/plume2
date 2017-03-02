<?php

/**
 * @package Plume
 * @author renshan <1005110700@qq.com>
 */

namespace Plume;

use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
	public static function bootDatabase($config)
	{
		$capsule = new Capsule;
		$capsule->addConnection($config);
		$capsule->setEventDispatcher(new Dispatcher(new Container()));
		$capsule->setAsGlobal();
		$capsule->bootEloquent();
	}
}

?>
