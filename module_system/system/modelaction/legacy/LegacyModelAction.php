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
use ReflectionMethod;
use Throwable;

abstract class LegacyModelAction implements ModelAction
{
    /**
     * @var ModelControllerProvider
     */
    private $modelControllerProvider;

    public function __construct(ModelControllerProvider $modelControllerProvider)
    {
        $this->modelControllerProvider = $modelControllerProvider;
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        return true;
    }

    protected function invokeProtectedMethod(object $object, string $methodName, ...$arguments)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $methodReflection = new ReflectionMethod($object, $methodName);
        $methodReflection->setAccessible(true);

        return $methodReflection->invokeArgs($object, $arguments);
    }

    /**
     * @param AdminSimple $modelController
     * @param Model $model
     * @return mixed
     */
    abstract protected function invokeControllerAction(AdminSimple $modelController, Model $model);

    protected function normalizeControllerActionResult($result): string
    {
        return $result;
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        try {
            $modelController = $this->modelControllerProvider->getControllerForModel($model);
            $controllerActionResult = $this->invokeControllerAction($modelController, $model);

            return $this->normalizeControllerActionResult($controllerActionResult);
        } catch (Throwable $exception) {
            throw new UnableToRenderActionForModelException($model, $exception);
        }
    }
}
