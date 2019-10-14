<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\System\Exceptions\UnableToRetrieveActionsForModelException;
use Kajona\System\System\Model;

class ClassInheritanceModelActionsProvider implements ModelActionsProvider
{
    /**
     * @var string
     */
    private $inheritanceClassName;

    /**
     * @var ModelActionList
     */
    private $modelActions;

    public function __construct(string $inheritanceClassName, ModelAction ...$modelActions)
    {
        $this->inheritanceClassName = $inheritanceClassName;
        $this->modelActions = new StaticModelActionList(...$modelActions);
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        return $model instanceof $this->inheritanceClassName;
    }

    public function getActions(Model $model, ModelActionContext $context): ModelActionList
    {
        if (!$this->supports($model, $context)) {
            throw new UnableToRetrieveActionsForModelException($model);
        }

        return $this->modelActions;
    }
}
