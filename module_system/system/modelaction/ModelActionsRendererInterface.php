<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\System\Exceptions\UnableToRenderModelActionsException;
use Kajona\System\System\Model;

interface ModelActionsRendererInterface
{
    /**
     * @param Model $model
     * @param ModelActionContext $context
     * @return string
     * @throws UnableToRenderModelActionsException
     */
    public function render(Model $model, ModelActionContext $context): string;
}
