<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\System\Exceptions\UnableToFindModelActionsProviderException;
use Kajona\System\System\Model;

final class ExtendableModelActionsProviderLocator implements ModelActionsProviderLocatorInterface
{
    /**
     * @var ModelActionsProviderInterface[]
     */
    private $modelActionsProviders;

    public function __construct(ModelActionsProviderInterface ...$modelActionsProviders)
    {
        $this->modelActionsProviders = $modelActionsProviders;
    }

    public function add(
        ModelActionsProviderInterface $modelActionsProvider,
        ModelActionsProviderInterface ...$additionalModelActionsProviders
    ): void {
        \array_unshift($this->modelActionsProviders, $modelActionsProvider, ...$additionalModelActionsProviders);
    }

    public function find(Model $model, ModelActionContext $context): ModelActionsProviderInterface
    {
        foreach ($this->modelActionsProviders as $modelActionsProvider) {
            if ($modelActionsProvider->supports($model, $context)) {
                return $modelActionsProvider;
            }
        }

        throw new UnableToFindModelActionsProviderException();
    }
}
