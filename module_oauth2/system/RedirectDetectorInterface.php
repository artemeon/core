<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Oauth2\System;

/**
 * Interface to implement in case you want to define a strategy to detect whether the user
 * should be redirected to the oauth2 sso page automatically or not
 *
 * @author stefan.idler@artemeon.de
 * @since 7.0
 */
interface RedirectDetectorInterface
{
    /**
     * @return bool
     */
    public function forceRedirect();
}
