<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\System\Model;

class StaticModelActionList implements ModelActionList
{
    /**
     * @var ModelAction[]
     */
    private $modelActions;

    public function __construct(ModelAction ...$modelActions)
    {
        $this->modelActions = $modelActions;
    }

    public function isAnyAvailable(Model $model, ModelActionContext $context): bool
    {
        foreach ($this->modelActions as $modelAction) {
            if ($modelAction->isAvailable($model, $context)) {
                return true;
            }
        }

        return false;
    }

    public function renderAll(Model $model, ModelActionContext $context): string
    {
        $renderedActions = [];

        foreach ($this->modelActions as $modelAction) {
            if ($modelAction->isAvailable($model, $context)) {
                $renderedActions[] = $modelAction->render($model, $context);
            }
        }

        return \implode('', $renderedActions);
    }
}
