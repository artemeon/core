<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction;

use Kajona\System\System\Exceptions\UnableToFindModelActionsProviderException;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Exceptions\UnableToRenderModelActionsException;
use Kajona\System\System\Exceptions\UnableToRetrieveActionsForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\DefaultModelActionsRenderer;
use Kajona\System\System\Modelaction\ModelActionContext;
use Kajona\System\System\Modelaction\ModelActionList;
use Kajona\System\System\Modelaction\ModelActionsProvider;
use Kajona\System\System\Modelaction\ModelActionsProviderFactory;
use Prophecy\Argument;

final class DefaultModelActionsRendererTest extends TestCase
{
    /**
     * @param string $renderingResult
     * @return ModelActionList
     * @throws \Exception
     */
    private function createModelActionsWithTheGivenRenderingResult(string $renderingResult): ModelActionList
    {
        $modelActions = $this->prophesize(ModelActionList::class);
        /** @noinspection PhpParamsInspection */
        $modelActions->renderAll(Argument::cetera())
            ->willReturn($renderingResult);

        return $modelActions->reveal();
    }

    /**
     * @param ModelActionList $modelActions
     * @return ModelActionsProvider
     * @throws \Exception
     */
    private function createModelActionsProviderThatReturns(ModelActionList $modelActions): ModelActionsProvider
    {
        $modelActionsProvider = $this->prophesize(ModelActionsProvider::class);
        /** @noinspection PhpParamsInspection */
        $modelActionsProvider->getActions(Argument::cetera())
            ->willReturn($modelActions);

        return $modelActionsProvider->reveal();
    }

    /**
     * @param ModelActionsProvider $modelActionsProvider
     * @return ModelActionsProviderFactory
     * @throws \Exception
     */
    private function createModelActionsProviderFactoryThatReturns(ModelActionsProvider $modelActionsProvider): ModelActionsProviderFactory
    {
        $modelActionsProviderFactory = $this->prophesize(ModelActionsProviderFactory::class);
        /** @noinspection PhpParamsInspection */
        $modelActionsProviderFactory->find(Argument::cetera())
            ->willReturn($modelActionsProvider);

        return $modelActionsProviderFactory->reveal();
    }

    /**
     * @dataProvider provideModelAndModelActionContextPairs
     * @param Model $model
     * @param ModelActionContext $context
     * @throws \Exception
     */
    public function testDelegatesRenderingToMatchingModelActions(Model $model, ModelActionContext $context): void
    {
        $expectedRenderingResult = \bin2hex(\random_bytes(16));

        $defaultModelActionsRenderer = new DefaultModelActionsRenderer(
            $this->createModelActionsProviderFactoryThatReturns(
                $this->createModelActionsProviderThatReturns(
                    $this->createModelActionsWithTheGivenRenderingResult($expectedRenderingResult)
                )
            )
        );

        $renderedModelActions = $defaultModelActionsRenderer->render($model, $context);
        $this->assertEquals($expectedRenderingResult, $renderedModelActions);
    }

    /**
     * @return ModelActionsProviderFactory
     * @throws \Exception
     */
    private function createModelActionsProviderFactoryThatThrowsExceptionOnSearch(): ModelActionsProviderFactory
    {
        $modelActionsProviderFactory = $this->prophesize(ModelActionsProviderFactory::class);
        /** @noinspection PhpParamsInspection */
        $modelActionsProviderFactory->find(Argument::cetera())
            ->willThrow(UnableToFindModelActionsProviderException::class);

        return $modelActionsProviderFactory->reveal();
    }

    /**
     * @dataProvider provideModelAndModelActionContextPairs
     * @param Model $model
     * @param ModelActionContext $context
     * @throws \Exception
     */
    public function testCatchesExceptionsThrownDuringModelActionsProviderSearch(Model $model, ModelActionContext $context): void
    {
        $defaultModelActionsRenderer = new DefaultModelActionsRenderer(
            $this->createModelActionsProviderFactoryThatThrowsExceptionOnSearch()
        );

        $this->expectException(UnableToRenderModelActionsException::class);
        $defaultModelActionsRenderer->render($model, $context);
    }

    /**
     * @return ModelActionList
     * @throws \Exception
     */
    private function createModelActionsProviderThatThrowExceptionOnRendering(): ModelActionsProvider
    {
        $modelActionsProvider = $this->prophesize(ModelActionsProvider::class);
        /** @noinspection PhpParamsInspection */
        $modelActionsProvider->getActions(Argument::cetera())
            ->willThrow(UnableToRetrieveActionsForModelException::class);

        return $modelActionsProvider->reveal();
    }

    /**
     * @dataProvider provideModelAndModelActionContextPairs
     * @param Model $model
     * @param ModelActionContext $context
     * @throws \Exception
     */
    public function testCatchesExceptionsThrownDuringModelActionsRetrieval(Model $model, ModelActionContext $context): void
    {
        $defaultModelActionsRenderer = new DefaultModelActionsRenderer(
            $this->createModelActionsProviderFactoryThatReturns(
                $this->createModelActionsProviderThatThrowExceptionOnRendering()
            )
        );

        $this->expectException(UnableToRenderModelActionsException::class);
        $defaultModelActionsRenderer->render($model, $context);
    }

    /**
     * @return ModelActionList
     * @throws \Exception
     */
    private function createModelActionsThatThrowExceptionOnRendering(): ModelActionList
    {
        $modelActions = $this->prophesize(ModelActionList::class);
        /** @noinspection PhpParamsInspection */
        $modelActions->renderAll(Argument::cetera())
            ->willThrow(UnableToRenderActionForModelException::class);

        return $modelActions->reveal();
    }

    /**
     * @dataProvider provideModelAndModelActionContextPairs
     * @param Model $model
     * @param ModelActionContext $context
     * @throws \Exception
     */
    public function testCatchesExceptionsThrownDuringModelActionsRendering(Model $model, ModelActionContext $context): void
    {
        $defaultModelActionsRenderer = new DefaultModelActionsRenderer(
            $this->createModelActionsProviderFactoryThatReturns(
                $this->createModelActionsProviderThatReturns(
                    $this->createModelActionsThatThrowExceptionOnRendering()
                )
            )
        );

        $this->expectException(UnableToRenderModelActionsException::class);
        $defaultModelActionsRenderer->render($model, $context);
    }
}
