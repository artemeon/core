<?php

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

final class ModelActionContextFactory implements ModelActionContextFactoryInterface
{
    public function empty(): ModelActionContext
    {
        return new ModelActionContext(null);
    }

    public function forListIdentifier(?string $listIdentifier): ModelActionContext
    {
        return new ModelActionContext($listIdentifier);
    }
}
