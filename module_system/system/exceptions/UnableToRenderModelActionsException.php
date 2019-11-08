<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Exceptions;

use Kajona\System\System\Exception;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Throwable;

final class UnableToRenderModelActionsException extends Exception
{
    public function __construct(Model $model, ModelActionContext $context, Throwable $previousException = null)
    {
        parent::__construct(
            \sprintf('unable to actions for model of class "%s" with context %s', \get_class($model), $context),
            self::$level_FATALERROR,
            $previousException
        );
    }
}