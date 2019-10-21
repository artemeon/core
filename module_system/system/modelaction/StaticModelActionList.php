<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\System\Model;

class StaticModelActionList implements ModelActionListInterface
{
    /**
     * @var ModelActionInterface[]
     */
    protected $modelActions;

    public function __construct(ModelActionInterface ...$modelActions)
    {
        $this->modelActions = $modelActions;
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        foreach ($this->modelActions as $modelAction) {
            if ($modelAction->supports($model, $context)) {
                return true;
            }
        }

        return false;
    }

    public function renderAll(Model $model, ModelActionContext $context): string
    {
        $renderedActions = [];

        foreach ($this->modelActions as $modelAction) {
            if ($modelAction->supports($model, $context)) {
                $renderedActions[] = $modelAction->render($model, $context);
            }
        }

        return \implode('', $renderedActions);
    }

    public function withAdditionalModelActions(
        ModelActionInterface $modelActionToBeAdded,
        ModelActionInterface ...$furtherModelActionsToBeAdded
    ): ModelActionListInterface {
        $insertIndex = 0;
        foreach ($this->modelActions as $index => $existingModelAction) {
            if ($existingModelAction instanceof EditModelAction) {
                $insertIndex = $index + 1;
                break;
            }
        }

        $newModelActions = $this->modelActions;
        \array_splice(
            $newModelActions,
            $insertIndex,
            0,
            \array_merge([$modelActionToBeAdded], $furtherModelActionsToBeAdded)
        );

        return new self(...$newModelActions);
    }

    public function withoutModelActionsOfType(
        string $modelActionClassNameToBeRemoved,
        string ...$furtherModelActionClassNamesToBeRemoved
    ): ModelActionListInterface {
        $modelActionClassNamesToBeRemoved = \array_merge(
            [$modelActionClassNameToBeRemoved],
            $furtherModelActionClassNamesToBeRemoved
        );

        return new self(
            ...\array_filter(
                $this->modelActions,
                static function (ModelActionInterface $modelAction) use ($modelActionClassNamesToBeRemoved): bool {
                    foreach ($modelActionClassNamesToBeRemoved as $modelActionClassNameToBeRemoved) {
                        if ($modelAction instanceof $modelActionClassNameToBeRemoved) {
                            return false;
                        }
                    }

                    return true;
                }
            )
        );
    }
}
