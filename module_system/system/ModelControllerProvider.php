<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Kajona\System\Admin\AdminSimple;
use Kajona\System\System\Exceptions\UnableToRetrieveControllerForModelException;

interface ModelControllerProvider
{
    /**
     * @param Model $model
     * @return AdminSimple
     * @throws UnableToRetrieveControllerForModelException
     */
    public function getControllerForModel(Model $model): AdminSimple;
}
