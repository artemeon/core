<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Kajona\System\System\Exceptions\UnableToLoadCurrentUserException;

interface CurrentUserProviderInterface
{
    /**
     * @return UserUser
     * @throws UnableToLoadCurrentUserException
     */
    public function load(): UserUser;
}
