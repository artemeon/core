<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System\Authorization;

use Kajona\Api\System\AuthorizationInterface;
use Slim\Http\Request;

/**
 * Anonymous authorization strategy to explicit allow public access without authorization
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
class Anonymous implements AuthorizationInterface
{
    /**
     * @inheritdoc
     */
    public function isAuthorized(Request $request): bool
    {
        return true;
    }
}
