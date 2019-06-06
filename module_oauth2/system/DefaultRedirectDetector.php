<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Oauth2\System;

/**
 * @author stefan.idler@artemeon.de
 * @since 7.0
 */
class DefaultRedirectDetector implements RedirectDetectorInterface
{
    public function forceRedirect()
    {
        return false;
    }

}
