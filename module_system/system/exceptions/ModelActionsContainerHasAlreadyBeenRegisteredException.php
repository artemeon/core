<?php

declare(strict_types=1);

namespace Kajona\System\System\Exceptions;

use Kajona\System\System\Exception;
use Throwable;

final class ModelActionsContainerHasAlreadyBeenRegisteredException extends Exception
{
    public function __construct(Throwable $previousException = null)
    {
        parent::__construct(
            'model actions container has already been registered',
            self::$level_FATALERROR,
            $previousException
        );
    }
}
