<?php


namespace Kajona\System\Admin;

use Predis\Client as Redis;

/**
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.2
 */
class CacheStore
{
    /**
     * @var Redis
     */
    protected $store;

    /**
     * CacheStore constructor.
     * @param $store
     */
    public function __construct($store)
    {
        $this->store = $store;
    }

    /**
     * @param string $key
     */
    public function get(string $key)
    {

    }

    /**
     * @param string $key
     * @param string $value
     */
    public function set(string $key, string $value)
    {

    }

    /**
     * @param string $pattern
     * @return array
     */
    public function getKeysForPattern(string $pattern): array
    {
    }

    public function delete(array $keys): void
    {

    }
}

