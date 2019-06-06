<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System\Authorization;

use Kajona\Api\System\AuthorizationInterface;
use Kajona\System\System\Session;

/**
 * BasicAuth
 *
 * @author christoph.kappestein@gmail.com
 * @since 7.1
 */
class BasicAuth implements AuthorizationInterface
{
    /**
     * @inheritdoc
     */
    public function authorize(string $header): bool
    {
        $header = explode(" ", $header, 2);
        $type   = $header[0] ?? null;
        $auth   = $header[1] ?? null;

        $parts    = explode(":", base64_decode($auth));
        $username = $parts[0] ?? null;
        $password = $parts[1] ?? null;

        return $type == "Basic" && Session::getInstance()->login($username, $password);
    }
}