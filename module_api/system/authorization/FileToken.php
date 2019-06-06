<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System\Authorization;

use Kajona\Api\System\AuthorizationInterface;

/**
 * Simple authorization service which reads a static token on the filesystem and requires this token for every request
 *
 * @author christoph.kappestein@gmail.com
 * @since 7.1
 */
class FileToken implements AuthorizationInterface
{
    /**
     * @inheritdoc
     */
    public function authorize(string $header): bool
    {
        $header = explode(" ", $header, 2);
        $type   = $header[0] ?? null;
        $token  = $header[1] ?? null;

        return $type == "Bearer" && $token == $this->getAccessToken();
    }

    private function getAccessToken()
    {
        $tokenFile = _realpath_ . "/project/token.key";

        if (!is_file($tokenFile)) {
            throw new \RuntimeException("Token file not available");
        }

        $token = trim(file_get_contents($tokenFile));

        if (empty($token)) {
            throw new \RuntimeException("No token was specified");
        }

        return $token;
    }
}