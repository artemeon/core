<?php

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Context;

interface ModelActionContextFactoryInterface
{
    public function empty(): ModelActionContext;

    public function forListIdentifier(?string $listIdentifier): ModelActionContext;
}
