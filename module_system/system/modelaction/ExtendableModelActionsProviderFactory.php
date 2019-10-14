<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\System\Exceptions\UnableToFindModelActionsProviderException;
use Kajona\System\System\Model;

final class ExtendableModelActionsProviderFactory implements ModelActionsProviderFactory
{
    /**
     * @var ModelActionsProvider[]
     */
    private $modelActionsProviders;

    public function __construct(ModelActionsProvider ...$modelActionsProviders)
    {
        $this->modelActionsProviders = $modelActionsProviders;
    }

    public function add(
        ModelActionsProvider $modelActionsProvider,
        ModelActionsProvider ...$additionalModelActionsProviders
    ): void {
        \array_unshift($this->modelActionsProviders, $modelActionsProvider, ...$additionalModelActionsProviders);
    }

    public function find(Model $model, ModelActionContext $context): ModelActionsProvider
    {
        foreach ($this->modelActionsProviders as $modelActionsProvider) {
            if ($modelActionsProvider->supports($model, $context)) {
                return $modelActionsProvider;
            }
        }

        throw new UnableToFindModelActionsProviderException();
    }
}
