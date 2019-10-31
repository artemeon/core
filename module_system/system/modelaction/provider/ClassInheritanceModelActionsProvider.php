<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Provider;

use Kajona\System\System\Exceptions\InvalidInheritanceClassNameGivenException;
use Kajona\System\System\Exceptions\UnableToRetrieveActionsForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Actionlist\ModelActionListInterface;
use Kajona\System\System\Modelaction\Context\ModelActionContext;

class ClassInheritanceModelActionsProvider implements ModelActionsProviderInterface
{
    /**
     * @var string
     */
    private $inheritanceClassName;

    /**
     * @var ModelActionListInterface
     */
    private $modelActions;

    /**
     * @param string $inheritanceClassName
     * @param ModelActionListInterface $modelActions
     * @throws InvalidInheritanceClassNameGivenException
     */
    public function __construct(string $inheritanceClassName, ModelActionListInterface $modelActions)
    {
        if (!\is_a($inheritanceClassName, Model::class, true)) {
            throw new InvalidInheritanceClassNameGivenException($inheritanceClassName);
        }

        $this->inheritanceClassName = $inheritanceClassName;
        $this->modelActions = $modelActions;
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        return $model instanceof $this->inheritanceClassName
            && $this->modelActions->supports($model, $context);
    }

    public function getActions(Model $model, ModelActionContext $context): ModelActionListInterface
    {
        if (!$this->supports($model, $context)) {
            throw new UnableToRetrieveActionsForModelException($model);
        }

        return $this->modelActions;
    }
}
