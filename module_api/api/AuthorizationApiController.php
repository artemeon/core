<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\System\System\Session;
use Kajona\System\System\UserUser;

/**
 * AuthorizationApiController
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
class AuthorizationApiController implements ApiControllerInterface
{
    /**
     * Returns the current assigned access token for the user. In the future this should become an actual OAuth2
     * endpoint
     *
     * @see https://tools.ietf.org/html/rfc6749
     * @api
     * @method POST
     * @path /v1/authorization/token
     */
    public function getAccessToken(): array
    {
        $user = Session::getInstance()->getUser();

        if (!$user instanceof UserUser) {
            throw new \RuntimeException('User not authorized');
        }

        if (!Session::getInstance()->isLoggedin()) {
            throw new \RuntimeException('User not authorized');
        }

        return [
            'access_token' => $user->getStrAccessToken(),
        ];
    }
}
