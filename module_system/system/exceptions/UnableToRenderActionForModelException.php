<?php

declare(strict_types=1);

namespace Kajona\System\System\Exceptions;

use Kajona\System\System\Exception;
use Kajona\System\System\Root;
use Throwable;

final class UnableToRenderActionForModelException extends Exception
{
    public function __construct(string $actionName, Root $model, Throwable $previousException = null)
    {
        parent::__construct(
            \sprintf('unable to render %s action for model of class "%s"', $actionName, \get_class($model)),
            self::$level_FATALERROR,
            $previousException
        );
    }
}
