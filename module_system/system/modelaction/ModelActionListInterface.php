<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Model;

interface ModelActionListInterface
{
    public function supports(Model $model, ModelActionContext $context): bool;

    /**
     * @param Model $model
     * @param ModelActionContext $context
     * @return string
     * @throws UnableToRenderActionForModelException
     */
    public function renderAll(Model $model, ModelActionContext $context): string;

    public function withAdditionalModelActions(
        ModelActionInterface $modelActionToBeAdded,
        ModelActionInterface ...$furtherModelActionsToBeAdded
    ): self;

    public function withoutModelActionsOfType(
        string $modelActionClassNameToBeRemoved,
        string ...$furtherModelActionClassNamesToBeRemoved
    ): self;
}
