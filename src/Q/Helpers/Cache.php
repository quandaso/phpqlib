<?php
/**
 * @author quantm
 * @date: 4/3/2017 9:22 PM
 */

namespace Q\Helpers;


class Cache
{
    const KEY_PREFIX = 'book.';
    const DEFAULT_EXPIRE = 1000;
    const CACHE_ENGINE_FILE = 'file';
    const CACHE_ENGINE_REDIS = 'redis';
    const CACHE_ENGINE_DATABASE = 'database';
    private static $cache;
    private $engine = 'file';
    private $enabled = false;

    /**
     * Cache constructor.
     * @throws \Exception
     * @param $engine
     */
    public function __construct($engine)
    {
        if ($engine !== 'redis' && $engine !== 'file' && $engine !== 'database') {
            throw new \Exception("Unsupported cache engine $engine");
        }
        $this->engine = $engine;
        $this->enabled = config('app')['cacheEnabled'];

    }

    /**
     * @param $key
     * @param $value
     * @param int $expire
     */
    protected function fileWrite($key, $value, $expire = 86400) {
        $data = [
            'value' => $value,
            'expire' => time() + $expire
        ];

        file_put_contents(ROOT_DIR . '/tmp/cache/' . $key, serialize($data));
    }

    /**
     * @param $key
     * @param $value
     * @param int $expire
     */
    protected function redisWrite($key, $value, $expire = 86400) {
        redis_get_instance()->setex($key, $expire, serialize($value));
    }

    /**
     * @param $key
     * @param $value
     * @param $expire
     */
    protected function databaseWrite($key, $value, $expire = 86400) {
        $db = db();
        $cacheEntry = $db->table('__cache')->where('key', $key)->first();
        if ($cacheEntry) {
            $db->table('__cache')->where('id', $cacheEntry->id)->update(['value' => serialize($value), 'expire' => time() + $expire]);
        } else {
            $db->table('__cache')->insert([
                'key' => $key,
                'value' => serialize($value),
                'expire' => time() + $expire,
                'created' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * @param $key
     * @return bool|mixed
     */
    protected function redisRead($key) {
        $cacheResult = redis_get_instance()->get($key);
        if ($cacheResult) {
            return unserialize($cacheResult);
        }

        return false;
    }

    /**
     * @param $key
     * @return bool
     */
    protected function fileRead($key) {
        $content = @file_get_contents(ROOT_DIR . '/tmp/cache/' . $key);
        if ($content !== false) {
            $cacheResult = unserialize($content);

            if ($cacheResult['expire'] < time()) {
                return false;
            }

            return $cacheResult['value'];
        }

        return false;
    }

    /**
     * @param $key
     * @return bool|mixed
     */
    protected function databaseRead($key) {
        $cacheEntry = db()->table('__cache')->where('key', $key)->where('expire', '>=', time())->first();
        if (!$cacheEntry) {
            return false;
        }

        return unserialize($cacheEntry->value);
    }

    /**
     * @param $key
     */
    protected function fileDelete($key) {
        @unlink(ROOT_DIR . '/tmp/cache/' . $key);
    }

    /**
     * @param $key
     */
    protected function redisDelete($key) {
        redis_get_instance()->del($key);
    }

    /**
     * @param $key
     */
    protected function databaseDelete($key) {
        db()->table('__cache')->where('key', $key)->delete();
    }

    /**
     * @param $key
     * @return mixed
     */
    public function read($key) {
        $method = $this->engine . 'Read';
        return $this->$method(self::KEY_PREFIX . $key);
    }

    /**
     * @param $key
     * @param $value
     * @param int $expire
     */
    public function write($key, $value, $expire = null) {
        if ($expire === null) {
            $expire = self::DEFAULT_EXPIRE;
        }

        $method = $this->engine . 'Write';
        $this->$method(self::KEY_PREFIX . $key, $value, $expire);
    }

    /**
     * @param $key
     * @param $value
     * @param int $expire
     */
    public function delete($key) {
        $method = $this->engine . 'Delete';
        $this->$method(self::KEY_PREFIX . $key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public static function get($key) {
        if (!isset(self::$cache)) {
            self::$cache = new Cache(config('app')['cacheEngine']);
        }

        if (self::$cache->enabled) {
            return self::$cache->read($key);
        }

        return false;
    }

    /**
     * @param $key
     * @param $value
     * @param int $expire
     */
    public static function set($key, $value, $expire = null) {
        if (!isset(self::$cache)) {
            self::$cache = new Cache(config('app')['cacheEngine']);
        }

        if (self::$cache->enabled) {
            self::$cache->write($key, $value, $expire);
        }

    }

    public static function del($key) {
        if (!isset(self::$cache)) {
            self::$cache = new Cache(config('app')['cacheEngine']);
        }
        if (self::$cache->enabled) {
            self::$cache->delete($key);
        }

    }
}