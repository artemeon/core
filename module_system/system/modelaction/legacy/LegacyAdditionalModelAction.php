<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Legacy;

use Kajona\System\System\ModelControllerProvider;

final class LegacyAdditionalModelAction extends LegacyModelAction
{
    public function __construct(ModelControllerProvider $modelControllerProvider)
    {
        parent::__construct($modelControllerProvider, 'renderAdditionalActions');
    }

    protected function normalizeControllerActionResult($result): string
    {
        if (\is_array($result)) {
            $result = \implode('', $result);
        }

        return parent::normalizeControllerActionResult($result);
    }
}
