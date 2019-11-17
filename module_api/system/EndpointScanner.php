<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System;

use Kajona\System\Admin\KeyGeneratorInterface;
use Kajona\System\Admin\KeyInvalidatorInterface;
use Kajona\System\System\CacheManager;
use Kajona\System\System\Classloader;
use Kajona\System\System\Exception;
use Kajona\System\System\Reflection;
use Kajona\System\System\Resourceloader;

/**
 * EndpointScanner
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
class EndpointScanner
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @param CacheManager $cacheManager
     */
    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Parses all API controller classes for specific annotations and builds an array containing all available routes
     * It caches the result so that this process is only executed once. If you add a new endpoint you need to clear the
     * cache
     *
     * @return array
     * @throws Exception
     */
    public function getEndpoints()
    {
        $routes = $this->cacheManager->getValue("api_routes");
        if (!empty($routes)) {
            return $routes;
        }

        $routes = [];
        $classes = $this->getAllApiController();
        foreach ($classes as $class) {
            $reflection = new Reflection($class);
            $methods = $reflection->getMethodsWithAnnotation("@api");

            foreach ($methods as $methodName => $values) {
                $method = array_map("trim", explode(",", $reflection->getMethodAnnotationValue($methodName, "@method")));
                $path = $reflection->getMethodAnnotationValue($methodName, "@path");
                $authorization = $reflection->getMethodAnnotationValue($methodName, "@authorization");

                if (empty($path)) {
                    throw new \RuntimeException("Provided an empty path at {$class}::{$methodName}");
                }

                if (empty($authorization)) {
                    throw new \RuntimeException("Provided no authorization at {$class}::{$methodName}");
                }

                $routes[] = [
                    "httpMethod" => $method,
                    "path" => $path,
                    "class" => $class,
                    "methodName" => $methodName,
                    "authorization" => $authorization,
                ];
            }
        }

        $this->cacheManager->addValue("api_routes", $routes);

        return $routes;
    }

    /**
     * Returns all available API controller classes
     *
     * @return array
     */
    private function getAllApiController()
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
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    public function getCacheableRoutes(): array
    {
        $routes = $this->cacheManager->getValue('cacheable_api_routes');
        if (!empty($routes)) {
            return $routes;
        }
        $routes = [];
        $apiControllers = $this->getAllApiController();
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
            $this->cacheManager->addValue('cacheable_api_routes', $routes);
            return $routes;
        }
        return [];
    }

    /**
     * Returns an instance of the keyGenerator
     * @param string $path
     * @return KeyGeneratorInterface
     * @throws Exception
     */
    public function getKeyGeneratorForPath(string $path): KeyGeneratorInterface
    {
        $apiControllers = $this->getAllApiController();
        foreach ($apiControllers as $class) {
            $reflection = new Reflection($class);
            $methods = $reflection->getMethodsWithAnnotation('@path');
            if (!empty($methods)) {
                foreach ($methods as $methodName => $values) {
                    $methodPath = $reflection->getMethodAnnotationValue($methodName, '@path');
                    if (empty($methodPath)) {
                        throw new \RuntimeException("Provided an empty path at {$class}::{$methodName}");
                    } else if ($methodPath === $path) {
                        $keyGenerator = $reflection->getMethodAnnotationValue($methodName, '@keyGenerator');
                        if (empty($keyGenerator)) {
                            throw new \RuntimeException("Provided an empty keyGenerator at {$class}::{$methodName}");
                        }
                        return new $keyGenerator();
                    }
                }
            }
        }
    }

    /**
     * @param string $path
     * @return KeyInvalidatorInterface|null
     * @throws Exception
     */
    public function getKeyInvalidatorForPath(string $path)
    {
        $apiControllers = $this->getAllApiController();
        foreach ($apiControllers as $class) {
            $reflection = new Reflection($class);
            $methods = $reflection->getMethodsWithAnnotation('@path');
            if (!empty($methods)) {
                foreach ($methods as $methodName => $values) {
                    $methodPath = $reflection->getMethodAnnotationValue($methodName, '@path');
                    if (empty($methodPath)) {
                        throw new \RuntimeException("Provided an empty path at {$class}::{$methodName}");
                    } else if ($methodPath === $path) {
                        $keyInvalidator = $reflection->getMethodAnnotationValue($methodName, '@keyInvalidator');
                        if (!empty($keyInvalidator)) {
//                            throw new \RuntimeException("Provided an empty keyInvalidator at {$class}::{$methodName}");
                            return new $keyInvalidator();
                        }
                        return null;
                    }
                }
            }
        }
    }
}
