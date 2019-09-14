<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System;

use Kajona\Api\System\Authorization\Anonymous;
use Kajona\Api\System\Authorization\Composite;
use Kajona\Api\System\Authorization\FileToken;
use Kajona\Api\System\Authorization\HeaderToken;
use Kajona\Api\System\Authorization\QueryToken;
use Kajona\Api\System\Authorization\UserTokenAbstract;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * ServiceProvider
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @see AppBuilder
     */
    const APP_BUILDER = "api_app_builder";

    /**
     * @see EndpointScanner
     */
    const ENDPOINT_SCANNER = "api_endpoint_scanner";

    /**
     * @see ProjectSecret
     */
    const PROJECT_SECRET = "api_project_secret";

    /**
     * @see JWTManager
     */
    const JWT_MANAGER = "api_jwt_manager";

    /**
     * @see FileToken
     */
    const AUTHORIZATION_FILETOKEN = "api_authorization_filetoken";

    /**
     * @see UserTokenAbstract
     */
    const AUTHORIZATION_USERTOKEN = "api_authorization_usertoken";

    /**
     * @see Anonymous
     */
    const AUTHORIZATION_ANONYMOUS = "api_authorization_anonymous";

    /**
     * @see TokenRefresher
     */
    const TOKEN_REFRESHER = "api_token_refresher";

    public function register(Container $container)
    {
        $container[self::APP_BUILDER] = static function ($c) {
            return new AppBuilder(
                $c[self::ENDPOINT_SCANNER],
                $c[\Kajona\System\System\ServiceProvider::STR_OBJECT_BUILDER],
                $c[\Kajona\System\System\ServiceProvider::EVENT_DISPATCHER],
                $c
            );
        };

        $container[self::ENDPOINT_SCANNER] = static function ($c) {
            return new EndpointScanner(
                $c[\Kajona\System\System\ServiceProvider::STR_CACHE_MANAGER]
            );
        };

        $container[self::PROJECT_SECRET] = static function ($c) {
            return new ProjectSecret();
        };

        $container[self::JWT_MANAGER] = static function ($c) {
            return new JWTManager(
                $c[self::PROJECT_SECRET]
            );
        };

        $container[self::AUTHORIZATION_FILETOKEN] = static function ($c) {
            return new FileToken(
                $c[self::PROJECT_SECRET]
            );
        };

        $container[self::AUTHORIZATION_USERTOKEN] = static function ($c) {
            $headerToken = new HeaderToken(
                $c[\Kajona\System\System\ServiceProvider::STR_DB],
                $c[self::JWT_MANAGER],
                $c[\Kajona\System\System\ServiceProvider::STR_SESSION],
                $c[\Kajona\System\System\ServiceProvider::STR_LANG]
            );

            $queryToken = new QueryToken(
                $c[\Kajona\System\System\ServiceProvider::STR_DB],
                $c[self::JWT_MANAGER],
                $c[\Kajona\System\System\ServiceProvider::STR_SESSION],
                $c[\Kajona\System\System\ServiceProvider::STR_LANG]
            );

            return new Composite($headerToken, $queryToken);
        };

        $container[self::AUTHORIZATION_ANONYMOUS] = static function ($c) {
            return new Anonymous();
        };

        $container[self::TOKEN_REFRESHER] = static function ($c) {
            return new TokenRefresher(
                $c[\Kajona\System\System\ServiceProvider::STR_DB],
                $c[self::JWT_MANAGER],
                $c[\Kajona\System\System\ServiceProvider::STR_OBJECT_FACTORY]
            );
        };

    }
}
