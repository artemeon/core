<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Exceptions;

use Kajona\System\System\Exception;
use Throwable;

final class UnableToLoadCurrentUserException extends Exception
{
    public function __construct(Throwable $previousException = null)
    {
        parent::__construct('unable to load current user', self::$level_FATALERROR, $previousException);
    }
}
