<?php

namespace Mix\Cache;

use Mix\Core\Component;
use Mix\Helpers\FileSystemHelper;

/**
 * Class FileCache
 * @package Mix\Cache
 * @author LIUJIAN <coder.keda@gmail.com>
 */
class FileCache extends Component
{

    /**
     * 缓存目录
     * @var string
     */
    public $dir = 'cache';

    /**
     * 分区
     * @var int
     */
    public $partitions = 64;

    /**
     * 获取缓存
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $filename = $this->getCacheFileName($key);
        $data     = file_get_contents($filename);
        if (empty($data)) {
            return $default;
        }
        $data = unserialize($data);
        if (!is_array($data) || count($data) !== 2) {
            return $default;
        }
        list($value, $expire) = $data;
        if ($expire > 0 && $expire < time()) {
            $this->delete($key);
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
        $filename = $this->getCacheFileName($key);
        $expire   = is_null($ttl) ? 0 : time() + $ttl;
        $data     = [
            $value,
            $expire,
        ];
        $bytes    = file_put_contents($filename, serialize($data), FILE_APPEND | LOCK_EX);
        return $bytes ? true : false;
    }

    /**
     * 删除缓存
     * @param $key
     * @return bool
     */
    public function delete($key)
    {
        $filename = $this->getCacheFileName($key);
        return unlink($filename);
    }

    /**
     * 清除缓存
     * @return bool
     */
    public function clear()
    {
        $dir = $this->getCacheDir();
        return FileSystemHelper::deleteFolder($dir);
    }

    /**
     * 批量获取
     * @param $keys
     * @param null $default
     * @return array
     */
    public function getMultiple($keys, $default = null)
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }
        return $results;
    }

    /**
     * 批量设置
     * @param $values
     * @param null $ttl
     * @return bool
     */
    public function setMultiple($values, $ttl = null)
    {
        $results = [];
        foreach ($values as $key => $value) {
            $results[] = $this->set($key, $value, $ttl);
        }
        foreach ($results as $result) {
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    /**
     * 批量删除
     * @param $keys
     * @return bool
     */
    public function deleteMultiple($keys)
    {
        $results = [];
        foreach ($keys as $key) {
            $results[] = $this->delete($key);
        }
        foreach ($results as $result) {
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    /**
     * 判断缓存是否存在
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        $value = $this->get($key);
        return is_null($value) ? false : true;
    }

    /**
     * 获取缓存目录
     * @return string
     */
    protected function getCacheDir()
    {
        $cacheDir = $this->dir;
        if (!FileSystemHelper::isAbsolute($cacheDir)) {
            $cacheDir = \Mix::$app->getRuntimePath() . DIRECTORY_SEPARATOR . $this->dir;
        }
        return $cacheDir;
    }

    /**
     * 获取缓存文件
     * @param $key
     * @return string
     */
    protected function getCacheFileName($key)
    {
        $dir      = $this->getCacheDir();
        $subDir   = crc32($key) / $this->partitions;
        $name     = md5($key);
        $filename = $dir . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . $name;
        return $filename;
    }

}
