<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Renderer;

use Kajona\System\System\Exceptions\UnableToFindModelActionsProviderException;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Exceptions\UnableToRenderModelActionsException;
use Kajona\System\System\Exceptions\UnableToRetrieveActionsForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Kajona\System\System\Modelaction\Provider\ModelActionsProviderLocatorInterface;

final class DefaultModelActionsRenderer implements ModelActionsRendererInterface
{
    /**
     * @var ModelActionsProviderLocatorInterface
     */
    private $modelActionsProviderFactory;

    public function __construct(ModelActionsProviderLocatorInterface $modelActionsProviderFactory)
    {
        $this->modelActionsProviderFactory = $modelActionsProviderFactory;
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        try {
            return $this->modelActionsProviderFactory->find($model, $context)
                ->getActions($model, $context)
                ->renderAll($model, $context);
        } catch (UnableToFindModelActionsProviderException|UnableToRetrieveActionsForModelException|UnableToRenderActionForModelException $exception) {
            throw new UnableToRenderModelActionsException($model, $context, $exception);
        }
    }
}
