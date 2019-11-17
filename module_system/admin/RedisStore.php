<?php


namespace Kajona\System\Admin;

use Predis\Client as Redis;

/**
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.2
 */
class RedisStore extends CacheStore
{

    public function __construct()
    {
        parent::__construct(new Redis());
    }

    /**
     * returns value of the given key
     * @param string $key
     * @return string
     */
    public function get(string $key): string
    {
        $value = $this->store->get($key);
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
        $this->store->set($key, $value);
    }

    /**
     * @param string $pattern
     * @return array
     */
    public function getKeysForPattern(string $pattern): array
    {
        return $this->store->keys($pattern);
    }

    public function delete(array $keys): void
    {
        $this->store->del($keys);
    }

}
