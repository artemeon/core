<?php

declare(strict_types=1);

namespace Kajona\System\System\Exceptions;

use Kajona\System\System\Exception;
use Throwable;

final class UnableToFindModelActionsContainerException extends Exception
{
    public function __construct(Throwable $previousException = null)
    {
        parent::__construct('unable to find model actions provider', self::$level_FATALERROR, $previousException);
    }
}
