<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\System\Model;

class StaticModelActionsProvider implements ModelActionsProvider
{
    /**
     * @var ModelActionList
     */
    private $modelActions;

    public function __construct(ModelAction ...$modelActions)
    {
        $this->modelActions = new StaticModelActionList(...$modelActions);
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        return $this->modelActions->isAnyAvailable($model, $context);
    }

    public function getActions(Model $model, ModelActionContext $context): ModelActionList
    {
        return $this->modelActions;
    }
}
