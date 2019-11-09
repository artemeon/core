<?php


namespace Kajona\System\Admin;

use Kajona\Api\System\EndpointScanner;
use Kajona\System\System\CacheManager;
use Kajona\System\System\Exception;
use Slim\Http\Request as SlimRequest;


/**
 * Class to manage all cache operations
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.2
 */
class BackendCacheManager
{
    /**
     * @var CacheStore
     */

    private $cacheStore;
    /**
     * @var string
     */
    private $keyGenerator;
    private $keyInvalidator;
    /**
     * @var array
     */
    private $cacheableRoutes;
    /**
     * @var string
     */
    private $storeType;
    /**
     * @var EndpointScanner
     */
    private $endpointScanner;

    /**
     *
     * @param EndpointScanner $endpointScanner
     * @param string $storeType
     * @throws Exception
     */
    public function __construct(EndpointScanner $endpointScanner, string $storeType)
    {
        $this->storeType = $storeType;
        $this->endpointScanner = $endpointScanner;
        $this->cacheStore = $this->initStore();
        $this->cacheableRoutes = $this->endpointScanner->getCacheableRoutes();

    }

    /**
     * @param SlimRequest $request
     * @return string
     * @throws Exception
     */
    public function getCache(SlimRequest $request): string
    {

        //check if requested end-point is cachable
        $path = '/' . $request->getUri()->getPath();

        if ($this->routeIsCacheable($request)) {
            $this->keyGenerator = $this->endpointScanner->getKeyGeneratorForPath($path);
            $key = call_user_func($this->keyGenerator, $request);
            return $this->cacheStore->get($key);
        }
        return '';
    }

    /**
     * @param SlimRequest $request
     * @param string $value
     */
    public function setCache(SlimRequest $request, string $value): void
    {
        //todo set only if value isnt in store
        if ($this->routeIsCacheable($request)) {
            $key = call_user_func($this->keyGenerator, $request);
            $this->cacheStore->set($key, $value);
        }

    }


    /**
     * @param SlimRequest $request
     * @return bool
     */
    public function routeIsCacheable(SlimRequest $request): bool
    {
        $path = '/' . $request->getUri()->getPath();
        return in_array($path, $this->cacheableRoutes);
    }

    /**
     * @return CacheManager
     */
    private function initStore()
    {
        return new $this->storeType();
    }


}

