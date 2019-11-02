<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System;

use Kajona\System\Admin\Exceptions\ModelNotFoundException;
use Kajona\System\Admin\MemoryCacheManager;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\Exception;
use Kajona\System\System\ObjectBuilder;
use Kajona\System\System\RequestEntrypointEnum;
use Kajona\System\System\Session;
use Kajona\System\System\SystemEventidentifier;
use Pimple\Container;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Environment\HttpResponse;
use PSX\Http\Exception\StatusCodeException;
use PSX\Http\Exception\UnauthorizedException;
use PSX\Http\Request;
use PSX\Uri\Uri;
use Slim\App;
use Slim\Container as SlimContainer;
use Slim\Exception\MethodNotAllowedException;
use Slim\Exception\NotFoundException;
use Slim\Http\Request as SlimRequest;
use Slim\Http\Response as SlimResponse;
use Throwable;

/**
 * AppBuilder
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
class AppBuilder
{
    /**
     * @var EndpointScanner
     */
    private $endpointScanner;

    /**
     * @var ObjectBuilder
     */
    private $objectBuilder;

    /**
     * @var CoreEventdispatcher
     */
    private $eventDispatcher;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param EndpointScanner $endpointScanner
     * @param ObjectBuilder $objectBuilder
     * @param CoreEventdispatcher $eventDispatcher
     * @param Container $container
     */
    public function __construct(EndpointScanner $endpointScanner, ObjectBuilder $objectBuilder, CoreEventdispatcher $eventDispatcher, Container $container)
    {
        $this->endpointScanner = $endpointScanner;
        $this->objectBuilder = $objectBuilder;
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container;
    }

    /**
     * Creates a new app, attaches all available routes and executes the app
     *
     * @throws Exception
     * @throws Throwable
     */
    public function run()
    {
        define("_autotesting_", false);

        $this->build()->run();

        $this->eventDispatcher->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_ENDPROCESSING, []);
        $this->eventDispatcher->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_AFTERCONTENTSEND, [RequestEntrypointEnum::XML()]);
    }

    /**
     * @return App
     * @throws Exception
     */
    public function build(): App
    {
        $app = new App($this->newContainer());
        $objectBuilder = $this->objectBuilder;
        $container = $this->container;
        $routes = $this->endpointScanner->getEndpoints();
        $cacheableRoutes = $this->endpointScanner->getCacheableEndPoints();
        // add CORS middleware
        $app->add(function (SlimRequest $request, SlimResponse $response, callable $next) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, DELETE, PATCH');

            if ($request->getMethod() === 'OPTIONS') {
                // for options requests we always return a 200 to handle preflight requests properly
                return $response->withStatus(200);
            } else {
                return $next($request, $response);
            }
        });
        // add Caching middleware
        $endpointScanner = $this->endpointScanner;
        $app->add(function (SlimRequest $request, SlimResponse $response, callable $next) use ($cacheableRoutes) {
            //check if requested end-point is cachable
            $path = '/' . $request->getUri()->getPath();
            if (in_array($path, $cacheableRoutes)) {
                $userId = Session::getInstance()->getUserID();
                $key = $path . '/' . $userId;
                $value = (new MemoryCacheManager)->get($key);
                if ($value !== '') {
                    return $response->withJson(json_decode($value));
                }
            }

            return $next($request, $response);
        });
        foreach ($routes as $route) {
            $app->map($route["httpMethod"], $route["path"], function (SlimRequest $request, SlimResponse $response, array $args) use ($route, $objectBuilder, $container, $cacheableRoutes) {
                $instance = $objectBuilder->factory($route["class"]);

                try {
                    $auth = $route["authorization"] ?? null;
                    if (!empty($auth)) {
                        /** @var AuthorizationInterface $authorization */
                        $authorization = $container->offsetGet("api_authorization_" . $auth);

                        if (!$authorization->isAuthorized($request)) {
                            throw new UnauthorizedException("Request not authorized", "Bearer");
                        }
                    } else {
                        throw new \RuntimeException('No authorization defined');
                    }

                    $body = $request->getParsedBody();
                    $httpContext = new HttpContext(new Request(new Uri($request->getUri()->__toString()), $request->getMethod(), $request->getHeaders()), $args);

                    if (in_array($request->getMethod(), ["GET", "HEAD", 'DELETE'])) {
                        $data = call_user_func_array([$instance, $route["methodName"]], [$httpContext]);
                    } else {
                        $data = call_user_func_array([$instance, $route["methodName"]], [$body, $httpContext]);
                    }

                    if ($data instanceof HttpResponse) {
                        $response = $response->withStatus($data->getStatusCode());

                        $headers = $data->getHeaders();
                        foreach ($headers as $name => $value) {
                            $response = $response->withHeader($name, $value);
                        }
                        //if end-point is cacheable set the payload to cache
                        $path = '/' . $request->getUri()->getPath();
                        if (in_array($path, $cacheableRoutes)) {
                            $userId = Session::getInstance()->getUserID();
                            $key = $path . '/' . $userId;
                            (new MemoryCacheManager)->set($key, $data->getBody());
                        }
                        $response = $response->write($data->getBody());

                    } else {
                        $response = $response->withHeader("Content-Type", "application/json")
                            ->write(json_encode($data, JSON_PRETTY_PRINT));
                    }
                } catch (ModelNotFoundException $e) {
                    $response = $response->withStatus(404)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode(["error" => $e->getMessage()]));
                } catch (StatusCodeException $e) {
                    $response = $response->withStatus($e->getStatusCode())
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode(["error" => $e->getMessage()]));
                } catch (Throwable $e) {
                    $response = $response->withStatus(500)
                        ->withHeader("Content-Type", "application/json")
                        ->write(json_encode(["error" => $e->getMessage()]));
                }

                return $response;
            });
        }

        return $app;
    }

    /**
     * @return SlimContainer
     */
    private function newContainer(): SlimContainer
    {
        $container = new SlimContainer();
        $container['notFoundHandler'] = function ($c) {
            return function (SlimRequest $request, SlimResponse $response) use ($c) {
                $data = [
                    "error" => "Endpoint not found"
                ];

                return $response->withStatus(404)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($data, JSON_PRETTY_PRINT));
            };
        };

        $container['notAllowedHandler'] = function ($c) {
            return function (SlimRequest $request, SlimResponse $response, $methods) use ($c) {
                $data = [
                    "error" => "Method not allowed"
                ];

                return $response->withStatus(405)
                    ->withHeader('Allow', implode(', ', $methods))
                    ->withHeader('Content-type', 'application/json')
                    ->write(json_encode($data, JSON_PRETTY_PRINT));
            };
        };

        return $container;
    }
}
