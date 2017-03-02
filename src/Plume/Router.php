<?php

/**
 * 路由解析类
 * @package plume
 * @author renshan <1005110700@qq.com>
 */

namespace Plume;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request as BaseRequest;
use Plume\Exception\RouteNotFoundException;

class Router
{
	/**
	 * 通过path_info匹配controller和action
	 * @return array
	 */
	public static function parse(Plume $container, BaseRequest $request)
	{
		$path_info = trim($request->getPathInfo());

		$paths = explode('/', $path_info);

		$maps = [];



		if (count($paths) < 3) {

			$conf = $container->getConfiguration('defaults');
			$def = $conf['router'];

			$bundle = $def['bundle'];
			$controller = $def['controller'];
			$action = $def['action'];

			if (!$bundle || !$controller) {
				throw new RouteNotFoundException("路由不存在");
			}
			$map['bundle'] = ucfirst($bundle);
			$map['controller'] = ucfirst($controller);
			$map['action'] = $action ? $action . 'Action' : 'indexAction';

			return $map;
		}

		$maps['bundle'] = ucfirst($paths[1]);
		$maps['controller'] = ucfirst($paths[2]) . 'Controller';

		if (count($paths) === 3) {
			$maps['action'] = 'index' . 'Action';
		} else {
			$maps['action'] = $paths[3] . 'Action';
		}

		return $maps;
	}

}

?>
