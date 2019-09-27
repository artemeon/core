<?php

/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                                        *
********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

/**
 * The objectfactory is a central place to create instances of common objects.
 * Therefore, a systemid is passed and the system returns the matching business object.
 *
 * Instantiations are cached, so recreating instances is a rather cheap operation.
 * To ensure a proper caching, the factory itself reflects the singleton pattern.
 *
 * In addition, common helper-methods regarding objects are placed right here.
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 4.0
 */
class Objectfactory
{
    /**
     * @var Objectfactory
     */
    private static $instance;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var BootstrapCache
     */
    private $bootstrapCache;

    /**
     * @var Model[]
     */
    private $objectCache = [];

    /**
     * @deprecated use dependency injection instead
     * @return Objectfactory
     */
    public static function getInstance(): self
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        self::$instance = new self(
            Carrier::getInstance()->getObjDB(),
            BootstrapCache::getInstance()
        );

        return self::$instance;
    }

    public function __construct(Database $database, BootstrapCache $bootstrapCache)
    {
        $this->database = $database;
        $this->bootstrapCache = $bootstrapCache;
    }

    /**
     * Creates a new object-instance. Therefore, the passed system-id
     * is searched in the cache, afterwards the instance is created - as long
     * as the matching class could be found, otherwise null
     *
     * @param string $systemId
     * @param bool $ignoreCache
     * @return Root|null
     */
    public function getObject(?string $systemId, bool $ignoreCache = false): ?Root
    {
        if ($systemId === null) {
            return null;
        }

        if (!$ignoreCache && isset($this->objectCache[$systemId])) {
            return $this->objectCache[$systemId];
        }

        $className = $this->getClassNameForId($systemId);

        //load the object itself
        if ($className !== '') {
            $object = new $className($systemId);
            $this->objectCache[$systemId] = $object;
            return $object;
        }

        return null;
    }

    /**
     * Returns an object from the cache if previously set, otherwise null
     *
     * @param string $systemId
     * @return Root|null
     */
    public function getObjectFromCache(string $systemId): ?Root
    {
        return $this->objectCache[$systemId] ?? null;
    }

    /**
     * Get the class name for a system-id.
     *
     * @param string $systemId
     * @return string
     */
    public function getClassNameForId(string $systemId): string
    {
        $cachedClassName = $this->bootstrapCache->getCacheRow(BootstrapCache::CACHE_OBJECTS, $systemId);
        if (\is_string($cachedClassName)) {
            return $cachedClassName;
        }

        $row = OrmRowcache::getCachedInitRow($systemId);
        if (!\is_array($row)) {
            $row = $this->database->getPRow('SELECT * FROM agp_system WHERE system_id = ?', [$systemId]);
        }

        if (!isset($row['system_class']) || !\is_string($row['system_class'])) {
            return '';
        }

        $this->bootstrapCache->addCacheRow(BootstrapCache::CACHE_OBJECTS, $systemId, $row['system_class']);

        return $row['system_class'];
    }

    /**
     * Flushes the internal instance cache
     */
    public function flushCache(): void
    {
        $this->objectCache = [];
    }

    /**
     * Removes a single entry from the instance cache
     *
     * @param string $systemId
     */
    public function removeFromCache(string $systemId): void
    {
        unset($this->objectCache[$systemId]);
        $this->bootstrapCache->removeCacheRow(BootstrapCache::CACHE_OBJECTS, $systemId);
    }

    /**
     * Adds a single object to the cache
     *
     * @param Root $object
     */
    public function addObjectToCache(Root $object): void
    {
        $this->objectCache[$object->getSystemid()] = $object;
    }
}
