<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Action\Legacy;

use Kajona\System\Admin\AdminSimple;
use Kajona\System\System\Model;

final class LegacyUnlockModelAction extends LegacyModelAction
{
    protected function invokeControllerAction(AdminSimple $modelController, Model $model)
    {
        return $this->invokeProtectedMethod($modelController, 'renderUnlockAction', $model);
    }
}
