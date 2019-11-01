<?php


namespace Kajona\System\Admin;

use Predis\Client as Redis;

/**
 * Class to manage memory-caching
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.2
 */
class MemoryCacheManager
{
    protected $cache;

    public function __construct()
    {
        $this->cache = new Redis();
    }

    /**
     * returns value of the given key
     * @param string $key
     * @return string
     */
    public function get(string $key): string
    {
        $value = $this->cache->get($key);
        if ($value) {
            return $value;
        }
        return "";

    }

    /**
     * sets the key and value in redis cache
     * @param string $key
     * @param string $value
     */
    public function set(string $key, string $value): void
    {
        $this->cache->set($key, $value);
    }

}
