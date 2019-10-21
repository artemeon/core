<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction;

use Kajona\System\Admin\AdminSimple;
use Kajona\System\Admin\ToolkitAdmin;
use Kajona\System\System\Exception;
use Kajona\System\System\FeatureDetectorInterface;
use Kajona\System\System\Lang;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\ModelActionContext;
use Kajona\System\System\ModelControllerLocatorInterface;
use Kajona\System\System\ModelInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

abstract class ModelActionTestCase extends TestCase
{
    protected function createDummyContext(): ModelActionContext
    {
        return new ModelActionContext(null);
    }

    protected function createDummyModelControllerLocator(): ModelControllerLocatorInterface
    {
        $modelControllerProvider = $this->prophesize(ModelControllerLocatorInterface::class);

        return $modelControllerProvider->reveal();
    }

    /**
     * @return ModelControllerLocatorInterface
     * @throws \Exception
     */
    protected function createModelControllerLocatorThatReturnsAModelController(): ModelControllerLocatorInterface
    {
        $modelController = $this->prophesize(AdminSimple::class);
        $modelControllerLocator = $this->prophesize(ModelControllerLocatorInterface::class);
        /** @noinspection PhpParamsInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        $modelControllerLocator->getControllerForModel(Argument::cetera())
            ->willReturn($modelController->reveal());

        return $modelControllerLocator->reveal();
    }

    private function getToolkitMethodNames(): iterable
    {
        $classReflection = new \ReflectionClass(ToolkitAdmin::class);

        foreach ($classReflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodReflection) {
            if (
                !$methodReflection->isConstructor() && !$methodReflection->isDestructor() &&
                !$methodReflection->isInternal() && !$methodReflection->isAbstract() &&
                !$methodReflection->isStatic()
            ) {
                yield $methodReflection->getName();
            }
        }
    }

    protected function createDummyToolkit(): ToolkitAdmin
    {
        $toolkit = $this->prophesize(ToolkitAdmin::class);

        foreach ($this->getToolkitMethodNames() as $methodName) {
            $toolkit->{$methodName}(Argument::cetera())
                ->willReturn('dummy');
        }

        return $toolkit->reveal();
    }

    protected function createToolkitThatThrowsExceptions(): ToolkitAdmin
    {
        $toolkit = $this->prophesize(ToolkitAdmin::class);

        foreach ($this->getToolkitMethodNames() as $methodName) {
            $toolkit->{$methodName}(Argument::cetera())
                ->willThrow(Exception::class);
        }

        return $toolkit->reveal();
    }

    protected function createDummyLang(): Lang
    {
        $lang = $this->prophesize(Lang::class);

        return $lang->reveal();
    }

    private function getFeatureDetectorMethodNames(): iterable
    {
        $classReflection = new \ReflectionClass(FeatureDetectorInterface::class);

        foreach ($classReflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodReflection) {
            if (
                !$methodReflection->isConstructor() && !$methodReflection->isDestructor() &&
                !$methodReflection->isInternal() && !$methodReflection->isStatic()
            ) {
                yield $methodReflection->getName();
            }
        }
    }

    protected function createPositiveFeatureDetector(): FeatureDetectorInterface
    {
        $featureDetector = $this->prophesize(FeatureDetectorInterface::class);

        foreach ($this->getFeatureDetectorMethodNames() as $methodName) {
            $featureDetector->{$methodName}()
                ->willReturn(true);
        }

        return $featureDetector->reveal();
    }

    protected function createNegativeFeatureDetector(): FeatureDetectorInterface
    {
        $featureDetector = $this->prophesize(FeatureDetectorInterface::class);

        foreach ($this->getFeatureDetectorMethodNames() as $methodName) {
            $featureDetector->{$methodName}()
                ->willReturn(false);
        }

        return $featureDetector->reveal();
    }

    protected function createModelProphecy(): ObjectProphecy
    {
        $modelProphecy = $this->prophesize();
        $modelProphecy->willExtend(Model::class);
        $modelProphecy->willImplement(ModelInterface::class);

        /** @noinspection PhpUndefinedMethodInspection */
        $modelProphecy->getStrSystemId()
            ->willReturn('');
        /** @noinspection PhpUndefinedMethodInspection */
        $modelProphecy->getStrDisplayName()
            ->willReturn('');

        return $modelProphecy;
    }
}
