<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System;

use Kajona\Api\System\Authorization\BasicAuth;
use Kajona\Api\System\Authorization\FileToken;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * ServiceProvider
 *
 * @author christoph.kappestein@gmail.com
 * @since 7.1
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @see AppBuilder
     */
    const STR_APP_BUILDER = "api_app_builder";

    /**
     * @see EndpointScanner
     */
    const STR_ENDPOINT_SCANNER = "api_endpoint_scanner";

    /**
     * @see FileToken
     */
    const STR_AUTHORIZATION_FILETOKEN = "api_authorization_filetoken";

    /**
     * @see BasicAuth
     */
    const STR_AUTHORIZATION_BASICAUTH = "api_authorization_basicauth";

    public function register(Container $objContainer)
    {
        $objContainer[self::STR_APP_BUILDER] = function ($c) {
            return new AppBuilder(
                $c[self::STR_ENDPOINT_SCANNER],
                $c[\Kajona\System\System\ServiceProvider::STR_OBJECT_BUILDER],
                $c
            );
        };

        $objContainer[self::STR_ENDPOINT_SCANNER] = function ($c) {
            return new EndpointScanner(
                $c[\Kajona\System\System\ServiceProvider::STR_CACHE_MANAGER]
            );
        };

        $objContainer[self::STR_AUTHORIZATION_FILETOKEN] = function ($c) {
            return new FileToken();
        };

        $objContainer[self::STR_AUTHORIZATION_BASICAUTH] = function ($c) {
            return new BasicAuth();
        };
    }
}
