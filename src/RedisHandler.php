<?php

namespace Mix\Cache;

use Mix\Bean\BeanInjector;
use Mix\Pool\ConnectionPoolInterface;
use Mix\Redis\RedisConnectionInterface;

/**
 * Class RedisHandler
 * @package Mix\Cache
 * @author liu,jian <coder.keda@gmail.com>
 */
class RedisHandler implements CacheHandlerInterface
{

    /**
     * 连接池
     * @var ConnectionPoolInterface
     */
    public $pool;

    /**
     * 连接
     * @var RedisConnectionInterface
     */
    public $connection;

    /**
     * Key前缀
     * @var string
     */
    public $keyPrefix = 'CACHE:';

    /**
     * Authorization constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        BeanInjector::inject($this, $config);
        $this->init();
    }

    /**
     * 初始化
     */
    public function init()
    {
        // 从连接池获取连接
        if (isset($this->pool)) {
            $this->connection = $this->pool->getConnection();
        }
    }

    /**
     * 析构
     */
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        // 释放连接
        if (isset($this->pool) && isset($this->connection)) {
            $this->connection->release();
            $this->connection = null;
        }
    }

    /**
     * 获取缓存
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $cacheKey = $this->keyPrefix . $key;
        $value    = $this->connection->get($cacheKey);
        if (empty($value)) {
            return $default;
        }
        $value = unserialize($value);
        if ($value === false) {
            return $default;
        }
        return $value;
    }

    /**
     * 设置缓存
     * @param $key
     * @param $value
     * @param null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        $cacheKey = $this->keyPrefix . $key;
        if (is_null($ttl)) {
            $success = $this->connection->set($cacheKey, serialize($value));
        } else {
            $success = $this->connection->setex($cacheKey, $ttl, serialize($value));
        }
        return $success ? true : false;
    }

    /**
     * 删除缓存
     * @param $key
     * @return bool
     */
    public function delete($key)
    {
        $cacheKey = $this->keyPrefix . $key;
        $success  = $this->connection->del($cacheKey);
        return $success ? true : false;
    }

    /**
     * 清除缓存
     * @return bool
     */
    public function clear()
    {
        $iterator = null;
        while (true) {
            $keys = $this->connection->scan($iterator, "{$this->keyPrefix}*");
            if ($keys === false) {
                return true;
            }
            foreach ($keys as $key) {
                $this->connection->del($key);
            }
        }
    }

    /**
     * 判断缓存是否存在
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        $cacheKey = $this->keyPrefix . $key;
        $success  = $this->connection->exists($cacheKey);
        return $success ? true : false;
    }

}
