<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Actionlist;

use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Action\ModelActionInterface;
use Kajona\System\System\Modelaction\Context\ModelActionContext;

interface ModelActionsContainerInterface
{
    /**
     * @param Model $model
     * @param ModelActionContext $context
     * @return string
     * @throws UnableToRenderActionForModelException
     */
    public function renderAll(Model $model, ModelActionContext $context): string;

    /**
     * Creates a new model actions container instance with the given model actions added. They will be added either at
     * the start or, if the container includes an {@see EditModelAction}, immediately following this edit model action.
     *
     * @param ModelActionInterface $modelActionToBeAdded
     * @param ModelActionInterface[] $furtherModelActionsToBeAdded
     * @return ModelActionsContainerInterface
     */
    public function withAdditionalModelActions(
        ModelActionInterface $modelActionToBeAdded,
        ModelActionInterface ...$furtherModelActionsToBeAdded
    ): self;

    /**
     * Creates a new model actions container instance without model actions of the given class(es).
     *
     * @param string $modelActionClassNameToBeRemoved
     * @param string[] $furtherModelActionClassNamesToBeRemoved
     * @return ModelActionsContainerInterface
     */
    public function withoutModelActionsOfType(
        string $modelActionClassNameToBeRemoved,
        string ...$furtherModelActionClassNamesToBeRemoved
    ): self;
}
