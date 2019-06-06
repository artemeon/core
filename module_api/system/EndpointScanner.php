<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System;

use Kajona\System\System\CacheManager;
use Kajona\System\System\Classloader;
use Kajona\System\System\Reflection;
use Kajona\System\System\Resourceloader;

/**
 * EndpointScanner
 *
 * @author christoph.kappestein@gmail.com
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
     * @throws \Kajona\System\System\Exception
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
}
