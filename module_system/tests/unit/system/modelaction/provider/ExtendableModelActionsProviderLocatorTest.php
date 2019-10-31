<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction\Provider;

use Kajona\System\System\Exceptions\UnableToFindModelActionsProviderException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Action\ModelActionInterface;
use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Kajona\System\System\Modelaction\Actionlist\ModelActionListInterface;
use Kajona\System\System\Modelaction\Provider\ExtendableModelActionsProviderLocator;
use Kajona\System\System\Modelaction\Provider\ModelActionsProviderInterface;
use Kajona\System\Tests\Unit\System\Modelaction\TestCase;

final class ExtendableModelActionsProviderLocatorTest extends TestCase
{
    public function provideValidModelActionsProviderFactoryArguments(): iterable
    {
        yield [];
        yield [
            $this->prophesize(ModelActionsProviderInterface::class)->reveal(),
        ];
        yield [
            $this->prophesize(ModelActionsProviderInterface::class)->reveal(),
            $this->prophesize(ModelActionsProviderInterface::class)->reveal(),
        ];
        yield [
            $this->prophesize(ModelActionsProviderInterface::class)->reveal(),
            $this->prophesize(ModelActionsProviderInterface::class)->reveal(),
            $this->prophesize(ModelActionsProviderInterface::class)->reveal(),
        ];
    }

    /**
     * @dataProvider provideValidModelActionsProviderFactoryArguments
     * @param mixed[] $validArguments
     */
    public function testAllowsInstantiationUsingValidArguments(...$validArguments): void
    {
        $modelActionsProviderLocator = new ExtendableModelActionsProviderLocator(...$validArguments);
        $this->assertInstanceOf(ExtendableModelActionsProviderLocator::class, $modelActionsProviderLocator);
    }

    public function provideInvalidModelActionsProviderFactoryArguments(): iterable
    {
        yield [new \stdClass()];
        yield [$this->prophesize(ModelActionInterface::class)->reveal()];
        yield [$this->prophesize(ModelActionListInterface::class)->reveal()];
    }

    /**
     * @dataProvider provideInvalidModelActionsProviderFactoryArguments
     * @param mixed[] $invalidArguments
     */
    public function testPreventsInstantiationUsingInvalidArguments(...$invalidArguments): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessageRegExp('/' . \preg_quote(ExtendableModelActionsProviderLocator::class, '/') . '::__construct\(\)/');

        new ExtendableModelActionsProviderLocator(...$invalidArguments);
    }

    private function createModelActionsProviderThatDoesSupport(Model $model, ModelActionContext $context): ModelActionsProviderInterface
    {
        $modelActionsProvider = $this->prophesize(ModelActionsProviderInterface::class);
        $modelActionsProvider->supports($model, $context)
            ->willReturn(true);

        return $modelActionsProvider->reveal();
    }

    private function createModelActionsProviderThatDoesntSupport(Model $model, ModelActionContext $context): ModelActionsProviderInterface
    {
        $modelActionsProvider = $this->prophesize(ModelActionsProviderInterface::class);
        $modelActionsProvider->supports($model, $context)
            ->willReturn(false);

        return $modelActionsProvider->reveal();
    }

    /**
     * @dataProvider provideModelAndModelActionContextPairs
     * @param Model $model
     * @param ModelActionContext $context
     * @throws \Exception
     */
    public function testFindsFirstModelActionsProviderThatDeclaresSupportForTheGivenModelAndContext(Model $model, ModelActionContext $context): void
    {
        $modelActionsProviderWithSupport = $this->createModelActionsProviderThatDoesSupport($model, $context);

        $modelActionsProviderLocator = new ExtendableModelActionsProviderLocator();
        $modelActionsProviderLocator->add($this->createModelActionsProviderThatDoesntSupport($model, $context));
        $modelActionsProviderLocator->add($this->createModelActionsProviderThatDoesntSupport($model, $context));
        $modelActionsProviderLocator->add($modelActionsProviderWithSupport);

        $this->assertEquals($modelActionsProviderWithSupport, $modelActionsProviderLocator->find($model, $context));
    }

    /**
     * @dataProvider provideModelAndModelActionContextPairs
     * @param Model $model
     * @param ModelActionContext $context
     * @throws \Exception
     */
    public function testThrowsExceptionIfNoModelActionsProvidersHaveBeenAdded(Model $model, ModelActionContext $context): void
    {
        $modelActionsProviderLocator = new ExtendableModelActionsProviderLocator();

        $this->expectException(UnableToFindModelActionsProviderException::class);
        $modelActionsProviderLocator->find($model, $context);
    }

    /**
     * @dataProvider provideModelAndModelActionContextPairs
     * @param Model $model
     * @param ModelActionContext $context
     * @throws \Exception
     */
    public function testThrowsExceptionIfNoMatchingModelActionsProvidersCouldBeFound(Model $model, ModelActionContext $context): void
    {
        $modelActionsProviderLocator = new ExtendableModelActionsProviderLocator(
            $this->createModelActionsProviderThatDoesntSupport($model, $context)
        );

        $this->expectException(UnableToFindModelActionsProviderException::class);
        $modelActionsProviderLocator->find($model, $context);
    }
}
