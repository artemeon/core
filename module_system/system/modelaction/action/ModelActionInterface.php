<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Action;

use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContext;

interface ModelActionInterface
{
    public function supports(Model $model, ModelActionContext $context): bool;

    /**
     * @param Model $model
     * @param ModelActionContext $context
     * @return string
     * @throws UnableToRenderActionForModelException
     */
    public function render(Model $model, ModelActionContext $context): string;
}
