<?php

declare(strict_types=1);

namespace Kajona\System\System\Exceptions;

use Kajona\System\System\Exception;
use Kajona\System\System\Root;
use Throwable;

final class UnableToRetrieveControllerActionNameForModelException extends Exception
{
    public function __construct(Root $model, string $actionName, Throwable $previousException = null)
    {
        parent::__construct(
            \sprintf(
                'unable to retrieve name for action "%s" in controller for model of class "%s"',
                $actionName,
                \get_class($model)
            ),
            self::$level_FATALERROR,
            $previousException
        );
    }
}
