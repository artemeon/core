<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction\Renderer;

use Kajona\System\System\Exceptions\UnableToFindModelActionsProviderException;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Exceptions\UnableToRenderModelActionsException;
use Kajona\System\System\Exceptions\UnableToRetrieveActionsForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Kajona\System\System\Modelaction\Actionlist\ModelActionListInterface;
use Kajona\System\System\Modelaction\Provider\ModelActionsProviderInterface;
use Kajona\System\System\Modelaction\Provider\ModelActionsProviderLocatorInterface;
use Kajona\System\System\Modelaction\Renderer\DefaultModelActionsRenderer;
use Kajona\System\Tests\Unit\System\Modelaction\TestCase;
use Prophecy\Argument;

final class DefaultModelActionsRendererTest extends TestCase
{
    /**
     * @param string $renderingResult
     * @return ModelActionListInterface
     * @throws \Exception
     */
    private function createModelActionsWithTheGivenRenderingResult(string $renderingResult): ModelActionListInterface
    {
        $modelActions = $this->prophesize(ModelActionListInterface::class);
        /** @noinspection PhpParamsInspection */
        $modelActions->renderAll(Argument::cetera())
            ->willReturn($renderingResult);

        return $modelActions->reveal();
    }

    /**
     * @param ModelActionListInterface $modelActions
     * @return ModelActionsProviderInterface
     * @throws \Exception
     */
    private function createModelActionsProviderThatReturns(ModelActionListInterface $modelActions): ModelActionsProviderInterface
    {
        $modelActionsProvider = $this->prophesize(ModelActionsProviderInterface::class);
        /** @noinspection PhpParamsInspection */
        $modelActionsProvider->getActions(Argument::cetera())
            ->willReturn($modelActions);

        return $modelActionsProvider->reveal();
    }

    /**
     * @param ModelActionsProviderInterface $modelActionsProvider
     * @return ModelActionsProviderLocatorInterface
     * @throws \Exception
     */
    private function createModelActionsProviderFactoryThatReturns(ModelActionsProviderInterface $modelActionsProvider): ModelActionsProviderLocatorInterface
    {
        $modelActionsProviderFactory = $this->prophesize(ModelActionsProviderLocatorInterface::class);
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
     * @return ModelActionsProviderLocatorInterface
     * @throws \Exception
     */
    private function createModelActionsProviderFactoryThatThrowsExceptionOnSearch(): ModelActionsProviderLocatorInterface
    {
        $modelActionsProviderFactory = $this->prophesize(ModelActionsProviderLocatorInterface::class);
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
     * @return ModelActionListInterface
     * @throws \Exception
     */
    private function createModelActionsProviderThatThrowExceptionOnRendering(): ModelActionsProviderInterface
    {
        $modelActionsProvider = $this->prophesize(ModelActionsProviderInterface::class);
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
     * @return ModelActionListInterface
     * @throws \Exception
     */
    private function createModelActionsThatThrowExceptionOnRendering(): ModelActionListInterface
    {
        $modelActions = $this->prophesize(ModelActionListInterface::class);
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
