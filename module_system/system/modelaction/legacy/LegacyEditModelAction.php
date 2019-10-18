<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Legacy;

use Kajona\System\Admin\AdminSimple;
use Kajona\System\System\Model;

final class LegacyEditModelAction extends LegacyModelAction
{
    protected function invokeControllerAction(AdminSimple $modelController, Model $model)
    {
        return $this->invokeProtectedMethod($modelController, 'renderEditAction', $model);
    }
}
