<?php


namespace Kajona\System\Admin;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\System\System\CacheManager;
use Kajona\System\System\Classloader;
use Kajona\System\System\Exception;
use Kajona\System\System\Reflection;
use Kajona\System\System\Resourceloader;
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
     * @var CacheManager
     */
    private $localCache;
    private $cacheStore;
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
     *
     * @param CacheManager $localCache
     * @param string $storeType
     * @throws Exception
     */
    public function __construct(CacheManager $localCache, string $storeType)
    {
        $this->storeType = $storeType;
        $this->localCache = $localCache;
        $this->cacheStore = $this->initStore();
        $this->cacheableRoutes = $this->getCacheableRoutes();

    }

    public function get(SlimRequest $request): string
    {
        //check if requested end-point is cachable
        $path = '/' . $request->getUri()->getPath();
        if ($this->routeIsCacheable($request)) {
            $key = $path;
            return $this->cacheStore->get($key);
        }
        return '';
    }

    /**
     * @param SlimRequest $request
     * @param string $value
     */
    public function set(SlimRequest $request, string $value): void
    {
        if ($this->routeIsCacheable($request)) {
            $path = '/' . $request->getUri()->getPath();
            $key = $path;
            $this->cacheStore->set($key, $value);
        }

    }

    /**
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    private function getCacheableRoutes(): array
    {
        $routes = $this->localCache->getValue('cacheable_api_routes');
        if (!empty($routes)) {
            return $routes;
        }
        $routes = [];
        $apiControllers = $this->getAllApiControllers();
        foreach ($apiControllers as $class) {
            $reflection = new Reflection($class);
            $methods = $reflection->getMethodsWithAnnotation('@cacheable');
            if (!empty($methods)) {
                foreach ($methods as $methodName => $values) {
                    $path = $reflection->getMethodAnnotationValue($methodName, '@path');
                    if (empty($path)) {
                        throw new \RuntimeException("Provided an empty path at {$class}::{$methodName}");
                    }
                    $routes[] = $path;
                }
            }

        }
        if (!empty($routes)) {
            //save the found routes to cache
            $this->localCache->addValue('cacheable_api_routes', $routes);
            return $routes;
        }
        return [];
    }

    private function getAllApiControllers(): array
    {
        $filter = function (&$strOneFile, $strPath) {
            $instance = Classloader::getInstance()->getInstanceFromFilename($strPath, ApiControllerInterface::class);
            if ($instance instanceof ApiControllerInterface) {
                $strOneFile = get_class($instance);
            } else {
                $strOneFile = null;
            }
        };

        $classes = Resourceloader::getInstance()->getFolderContent("/api", array(".php"), false, null, $filter);
        $classes = array_filter($classes);
        $classes = array_values($classes);

        return $classes;
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

