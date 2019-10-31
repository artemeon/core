<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Action\Legacy;

use Kajona\System\Admin\AdminSimple;
use Kajona\System\System\Model;

final class LegacyStatusModelAction extends LegacyModelAction
{
    private function getStatusActionMethodName(AdminSimple $modelController): string
    {
        if (\method_exists($modelController, 'renderFlowStatusAction')) {
            return 'renderFlowStatusAction';
        }

        return 'renderStatusAction';
    }

    protected function invokeControllerAction(AdminSimple $modelController, Model $model)
    {
        return $this->invokeProtectedMethod(
            $modelController,
            $this->getStatusActionMethodName($modelController),
            $model
        );
    }
}
