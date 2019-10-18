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
    protected $modelActions;

    public function __construct(ModelActionList $modelActions)
    {
        $this->modelActions = $modelActions;
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        return $this->modelActions->supports($model, $context);
    }

    public function getActions(Model $model, ModelActionContext $context): ModelActionList
    {
        return $this->modelActions;
    }
}
