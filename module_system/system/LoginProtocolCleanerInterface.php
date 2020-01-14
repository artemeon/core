<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System;


/**
 * A userlog cleaner removes old / unused entries from the login protocol
 */
interface LoginProtocolCleanerInterface
{

    public function cleanUserlog();

}
