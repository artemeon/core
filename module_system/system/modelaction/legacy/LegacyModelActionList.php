<?php

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Legacy;

use Kajona\System\System\Modelaction\ModelAction;
use Kajona\System\System\Modelaction\ModelActionList;
use Kajona\System\System\Modelaction\StaticModelActionList;

final class LegacyModelActionList extends StaticModelActionList
{
    public function withAdditionalModelActions(
        ModelAction $modelActionToBeAdded,
        ModelAction ...$furtherModelActionsToBeAdded
    ): ModelActionList {
        $insertIndex = 0;
        foreach ($this->modelActions as $index => $existingModelAction) {
            if ($existingModelAction instanceof LegacyEditModelAction) {
                $insertIndex = $index + 1;
                break;
            }
        }

        $newModelActions = $this->modelActions;
        \array_splice($newModelActions, $insertIndex, 0, [$modelActionToBeAdded] + $furtherModelActionsToBeAdded);

        return new self(...$newModelActions);
    }
}
