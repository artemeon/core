<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Provider;

use Kajona\System\System\Exceptions\UnableToRetrieveActionsForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Actionlist\ModelActionListInterface;
use Kajona\System\System\Modelaction\Context\ModelActionContext;

interface ModelActionsProviderInterface
{
    public function supports(Model $model, ModelActionContext $context): bool;

    /**
     * @param Model $model
     * @param ModelActionContext $context
     * @return ModelActionListInterface
     * @throws UnableToRetrieveActionsForModelException
     */
    public function getActions(Model $model, ModelActionContext $context): ModelActionListInterface;
}
