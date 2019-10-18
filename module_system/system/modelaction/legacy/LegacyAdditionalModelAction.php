<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Legacy;

use Kajona\System\Admin\AdminSimple;
use Kajona\System\System\Model;

final class LegacyAdditionalModelAction extends LegacyModelAction
{
    protected function invokeControllerAction(AdminSimple $modelController, Model $model)
    {
        return $this->invokeProtectedMethod($modelController, 'renderAdditionalActions', $model);
    }

    protected function normalizeControllerActionResult($result): string
    {
        return \implode('', $result);
    }
}
