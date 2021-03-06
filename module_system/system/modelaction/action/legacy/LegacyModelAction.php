<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Action\Legacy;

use Kajona\System\Admin\AdminSimple;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Action\ModelActionInterface;
use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Kajona\System\System\ModelControllerLocatorInterface;
use ReflectionMethod;
use Throwable;

abstract class LegacyModelAction implements ModelActionInterface
{
    /**
     * @var ModelControllerLocatorInterface
     */
    private $modelControllerLocator;

    public function __construct(ModelControllerLocatorInterface $modelControllerProvider)
    {
        $this->modelControllerLocator = $modelControllerProvider;
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
            $modelController = $this->modelControllerLocator->getControllerForModel($model);
            $controllerActionResult = $this->invokeControllerAction($modelController, $model);

            return $this->normalizeControllerActionResult($controllerActionResult);
        } catch (Throwable $exception) {
            throw new UnableToRenderActionForModelException($model, $exception);
        }
    }
}
