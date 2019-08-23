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
    public function __construct(array $config = [])
    {
        BeanInjector::inject($this, $config);
    }

    /**
     * 获取连接
     * @return RedisConnectionInterface
     */
    protected function getConnection()
    {
        return $this->pool ? $this->pool->getConnection() : $conn;
    }

    /**
     * 释放连接
     * @param $connection
     * @return bool
     */
    protected function release($connection)
    {
        if (!method_exists($conn, 'release')) {
            return false;
        }
        return call_user_func([$conn, 'release']);
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
        $conn     = $this->getConnection();
        $value    = $conn->get($cacheKey);
        $this->release($conn);
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
        $conn     = $this->getConnection();
        if (is_null($ttl)) {
            $success = $conn->set($cacheKey, serialize($value));
        } else {
            $success = $conn->setex($cacheKey, $ttl, serialize($value));
        }
        $this->release($conn);
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
        $conn     = $this->getConnection();
        $success  = $conn->del($cacheKey);
        $this->release($conn);
        return $success ? true : false;
    }

    /**
     * 清除缓存
     * @return bool
     */
    public function clear()
    {
        $iterator = null;
        $conn     = $this->getConnection();
        while (true) {
            $keys = $conn->scan($iterator, "{$this->keyPrefix}*");
            if ($keys === false) {
                return true;
            }
            foreach ($keys as $key) {
                $conn->del($key);
            }
        }
        $this->release($conn);
        return true;
    }

    /**
     * 判断缓存是否存在
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        $cacheKey = $this->keyPrefix . $key;
        $conn     = $this->getConnection();
        $success  = $conn->exists($cacheKey);
        $this->release($conn);
        return $success ? true : false;
    }

}
