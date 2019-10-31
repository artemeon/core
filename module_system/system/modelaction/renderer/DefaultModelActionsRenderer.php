<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Renderer;

use Kajona\System\System\Exceptions\UnableToFindModelActionsContainerException;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Exceptions\UnableToRenderModelActionsException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Kajona\System\System\Modelaction\Register\ModelActionsContainerRegistryInterface;

final class DefaultModelActionsRenderer implements ModelActionsRendererInterface
{
    /**
     * @var ModelActionsContainerRegistryInterface
     */
    private $modelActionsContainerRegistry;

    public function __construct(ModelActionsContainerRegistryInterface $modelActionsContainerRegistry)
    {
        $this->modelActionsContainerRegistry = $modelActionsContainerRegistry;
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        try {
            return $this->modelActionsContainerRegistry->find($model)
                ->renderAll($model, $context);
        } catch (UnableToFindModelActionsContainerException|UnableToRenderActionForModelException $exception) {
            throw new UnableToRenderModelActionsException($model, $context, $exception);
        }
    }
}
