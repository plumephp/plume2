<?php

/**
 * 提供默认的Redis操作
 *
 * @package PLume
 * @author renshan <1005110700@qq.com>
 */

namespace Plume\Provider;

use Pimple\Container;
use Symfony\Component\Yaml\Yaml;

class RedisProvider implements ProviderInterface
{
    private $redis;
    private $config;
    private $continer;

    /**
     * 向容器注册自己
     */
	public function register(Container $plume)
	{
        $this->container = $plume;
        $this->boot();
		$plume['plume.redis'] = $this;
    }

    /**
     * 配置 Redis, 但不连接
     */
    private function boot()
    {
        $redis = new \Redis();
        $config = $this->container->getConfiguration('redis');
        $this->config = count($config) === 0 ? $this->getDefaultConfiguration() : $config;

        $this->redis = $redis;
    }

	/**
	 * 获取默认Redis配置
	 * @return array
	 */
	private function getDefaultConfiguration()
	{
		return [ 'host' => '127.0.0.1', 'port' => 6379 ];
	}

    /**
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        /**
         * 保证在操作之前 Redis 是连接的
         */
        try {
            $this->redis->ping();
        } catch (\Exception $e) {
            if (array_key_exists('pconnect', $this->config)) {
                $this->redis->pconnect($this->config['host'], $this->config['port']);
            } else {
                $this->redis->connect($this->config['host'], $this->config['port']);
            }
            if (array_key_exists('auth', $this->config)) {
                $this->redis->auth($this->config['auth']);
            }
        }

        return call_user_func_array([$this->redis, $method], $arguments);
    }
}

?>
