<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Renderer;

use Kajona\System\System\Exceptions\UnableToRenderModelActionsException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContext;

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
