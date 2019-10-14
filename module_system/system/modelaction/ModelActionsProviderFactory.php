<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\System\Exceptions\UnableToFindModelActionsProviderException;
use Kajona\System\System\Model;

interface ModelActionsProviderFactory
{
    /**
     * @param Model $model
     * @param ModelActionContext $context
     * @return ModelActionsProvider
     * @throws UnableToFindModelActionsProviderException
     */
    public function find(Model $model, ModelActionContext $context): ModelActionsProvider;
}
