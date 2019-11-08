<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Exceptions;

use Kajona\System\System\Exception;
use Throwable;

final class InvalidInheritanceClassNameGivenException extends Exception
{
    public function __construct(string $className, Throwable $previousException = null)
    {
        parent::__construct(
            \sprintf('invalid inheritance class name "%s" given', $className),
            self::$level_FATALERROR,
            $previousException
        );
    }
}
