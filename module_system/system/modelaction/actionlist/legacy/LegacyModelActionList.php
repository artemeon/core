<?php

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Actionlist\Legacy;

use Kajona\System\System\Modelaction\Action\Legacy\LegacyEditModelAction;
use Kajona\System\System\Modelaction\Action\ModelActionInterface;
use Kajona\System\System\Modelaction\Actionlist\ModelActionsContainerInterface;
use Kajona\System\System\Modelaction\Actionlist\InMemoryModelActionsContainer;

final class LegacyModelActionList extends InMemoryModelActionsContainer
{
    public function withAdditionalModelActions(
        ModelActionInterface $modelActionToBeAdded,
        ModelActionInterface ...$furtherModelActionsToBeAdded
    ): ModelActionsContainerInterface {
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
