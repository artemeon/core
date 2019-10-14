<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Legacy;

use Kajona\System\Admin\AdminSimple;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\ModelAction;
use Kajona\System\System\Modelaction\ModelActionContext;
use Kajona\System\System\ModelControllerProvider;
use ReflectionException;
use ReflectionMethod;
use Throwable;

abstract class LegacyModelAction implements ModelAction
{
    /**
     * @var ModelControllerProvider
     */
    private $modelControllerProvider;

    /**
     * @var string
     */
    private $renderMethodName;

    public function __construct(ModelControllerProvider $modelControllerProvider, string $renderMethodName)
    {
        $this->modelControllerProvider = $modelControllerProvider;
        $this->renderMethodName = $renderMethodName;
    }

    public function isAvailable(Model $model, ModelActionContext $context): bool
    {
        return true;
    }

    protected function normalizeControllerActionResult($result): string
    {
        return $result;
    }

    /**
     * @param AdminSimple $modelController
     * @param string $actionName
     * @param Model $model
     * @param string|null $listIdentifier
     * @return string
     * @throws ReflectionException
     */
    private function invokeControllerAction(
        AdminSimple $modelController,
        string $actionName,
        Model $model,
        ?string $listIdentifier
    ): string {
        $actionMethod = new ReflectionMethod($modelController, $actionName);
        $actionMethod->setAccessible(true);
        $actionResult = $actionMethod->invoke($modelController, $model, $listIdentifier ?? '');

        return $this->normalizeControllerActionResult($actionResult);
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        try {
            $modelController = $this->modelControllerProvider->getControllerForModel($model);

            return $this->invokeControllerAction(
                $modelController,
                $this->renderMethodName,
                $model,
                $context->getListIdentifier()
            );
        } catch (Throwable $exception) {
            throw new UnableToRenderActionForModelException($this->renderMethodName, $model, $exception);
        }
    }
}
