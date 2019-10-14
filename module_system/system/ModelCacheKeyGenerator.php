<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

interface ModelCacheKeyGenerator
{
    public function generate(Model $model, string ...$additionalData): string;
}
