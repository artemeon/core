<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System;

/**
 * AuthorizationInterface
 *
 * @author christoph.kappestein@gmail.com
 * @since 7.1
 */
interface AuthorizationInterface
{
    /**
     * Validates the authorization header and returns whether the access is allowed or not
     *
     * @param string $header
     * @return bool
     */
    public function authorize(string $header) : bool;
}
