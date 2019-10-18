<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction;

use Kajona\System\System\Exceptions\UnableToFindModelActionsProviderException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\ExtendableModelActionsProviderFactory;
use Kajona\System\System\Modelaction\ModelAction;
use Kajona\System\System\Modelaction\ModelActionContext;
use Kajona\System\System\Modelaction\ModelActionList;
use Kajona\System\System\Modelaction\ModelActionsProvider;

final class ExtendableModelActionsProviderFactoryTest extends TestCase
{
    public function provideValidModelActionsProviderFactoryArguments(): iterable
    {
        yield [];
        yield [
            $this->prophesize(ModelActionsProvider::class)->reveal(),
        ];
        yield [
            $this->prophesize(ModelActionsProvider::class)->reveal(),
            $this->prophesize(ModelActionsProvider::class)->reveal(),
        ];
        yield [
            $this->prophesize(ModelActionsProvider::class)->reveal(),
            $this->prophesize(ModelActionsProvider::class)->reveal(),
            $this->prophesize(ModelActionsProvider::class)->reveal(),
        ];
    }

    /**
     * @dataProvider provideValidModelActionsProviderFactoryArguments
     * @param mixed[] $validArguments
     */
    public function testAllowsInstantiationUsingValidArguments(...$validArguments): void
    {
        $factory = new ExtendableModelActionsProviderFactory(...$validArguments);
        $this->assertInstanceOf(ExtendableModelActionsProviderFactory::class, $factory);
    }

    public function provideInvalidModelActionsProviderFactoryArguments(): iterable
    {
        yield [new \stdClass()];
        yield [$this->prophesize(ModelAction::class)->reveal()];
        yield [$this->prophesize(ModelActionList::class)->reveal()];
    }

    /**
     * @dataProvider provideInvalidModelActionsProviderFactoryArguments
     * @param mixed[] $invalidArguments
     */
    public function testPreventsInstantiationUsingInvalidArguments(...$invalidArguments): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessageRegExp('/' . \preg_quote(ExtendableModelActionsProviderFactory::class, '/') . '::__construct\(\)/');

        new ExtendableModelActionsProviderFactory(...$invalidArguments);
    }

    private function createModelActionsProviderThatDoesSupport(Model $model, ModelActionContext $context): ModelActionsProvider
    {
        $modelActionsProvider = $this->prophesize(ModelActionsProvider::class);
        $modelActionsProvider->supports($model, $context)
            ->willReturn(true);

        return $modelActionsProvider->reveal();
    }

    private function createModelActionsProviderThatDoesntSupport(Model $model, ModelActionContext $context): ModelActionsProvider
    {
        $modelActionsProvider = $this->prophesize(ModelActionsProvider::class);
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

        $factory = new ExtendableModelActionsProviderFactory();
        $factory->add($this->createModelActionsProviderThatDoesntSupport($model, $context));
        $factory->add($this->createModelActionsProviderThatDoesntSupport($model, $context));
        $factory->add($modelActionsProviderWithSupport);

        $this->assertEquals($modelActionsProviderWithSupport, $factory->find($model, $context));
    }

    /**
     * @dataProvider provideModelAndModelActionContextPairs
     * @param Model $model
     * @param ModelActionContext $context
     * @throws \Exception
     */
    public function testThrowsExceptionIfNoModelActionsProvidersHaveBeenAdded(Model $model, ModelActionContext $context): void
    {
        $factory = new ExtendableModelActionsProviderFactory();

        $this->expectException(UnableToFindModelActionsProviderException::class);
        $factory->find($model, $context);
    }

    /**
     * @dataProvider provideModelAndModelActionContextPairs
     * @param Model $model
     * @param ModelActionContext $context
     * @throws \Exception
     */
    public function testThrowsExceptionIfNoMatchingModelActionsProvidersCouldBeFound(Model $model, ModelActionContext $context): void
    {
        $factory = new ExtendableModelActionsProviderFactory(
            $this->createModelActionsProviderThatDoesntSupport($model, $context)
        );

        $this->expectException(UnableToFindModelActionsProviderException::class);
        $factory->find($model, $context);
    }
}
