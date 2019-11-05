<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction\Action;

use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Action\StatusModelAction;

final class StatusModelActionTest extends ModelActionTestCase
{
    /**
     * @return Model
     * @throws Exception
     */
    private function createModelThatIsNotDeletedAndCanBeViewed(): Model
    {
        $model = $this->prophesize(Model::class);
        $model->getIntRecordDeleted()
            ->willReturn(0);
        $model->rightView()
            ->willReturn(true);

        return $model->reveal();
    }

    /**
     * @return iterable
     * @throws Exception
     */
    public function provideModelsInAValidState(): iterable
    {
        $index = 0;

        while (++$index <= 10) {
            yield [$this->createModelThatIsNotDeletedAndCanBeViewed()];
        }
    }

    /**
     * @dataProvider provideModelsInAValidState
     * @param Model $modelInAValidState
     */
    public function testConfirmsAvailabilityForModelThatIsInAValidState(Model $modelInAValidState): void
    {
        $statusModelAction = new StatusModelAction(
            $this->createDummyToolkit()
        );

        $this->assertTrue($statusModelAction->supports($modelInAValidState, $this->createDummyContext()));
    }

    /**
     * @return Model
     * @throws Exception
     */
    private function createModelThatIsDeletedButCanBeViewed(): Model
    {
        $model = $this->prophesize(Model::class);
        $model->getIntRecordDeleted()
            ->willReturn(1);
        $model->rightView()
            ->willReturn(true);

        return $model->reveal();
    }

    /**
     * @return Model
     * @throws Exception
     */
    private function createModelThatIsNotDeletedButCanNotBeViewed(): Model
    {
        $model = $this->prophesize(Model::class);
        $model->getIntRecordDeleted()
            ->willReturn(0);
        $model->rightView()
            ->willReturn(false);

        return $model->reveal();
    }

    /**
     * @return Model
     * @throws Exception
     */
    private function createModelThatIsDeletedAndCanNotBeViewed(): Model
    {
        $model = $this->prophesize(Model::class);
        $model->getIntRecordDeleted()
            ->willReturn(1);
        $model->rightView()
            ->willReturn(false);

        return $model->reveal();
    }

    /**
     * @return iterable
     * @throws Exception
     */
    public function provideModelsInAnInvalidState(): iterable
    {
        yield [$this->createModelThatIsDeletedButCanBeViewed()];
        yield [$this->createModelThatIsNotDeletedButCanNotBeViewed()];
        yield [$this->createModelThatIsDeletedAndCanNotBeViewed()];
    }

    /**
     * @dataProvider provideModelsInAnInvalidState
     * @param Model $modelInAnInvalidState
     */
    public function testDeniesAvailabilityForModelThatIsInAnInvalidState(Model $modelInAnInvalidState): void
    {
        $statusModelAction = new StatusModelAction(
            $this->createDummyToolkit()
        );

        $this->assertFalse($statusModelAction->supports($modelInAnInvalidState, $this->createDummyContext()));
    }

    /**
     * @return iterable
     * @throws Exception
     */
    public function provideModelsThatThrowExceptionsOnStateDetermination(): iterable
    {
        $modelWhoseDeletedStateCanNotBeDetermined = $this->prophesize(Model::class);
        $modelWhoseDeletedStateCanNotBeDetermined->getIntRecordDeleted()
            ->willThrow(Exception::class);
        $modelWhoseDeletedStateCanNotBeDetermined->rightView()
            ->willReturn(true);

        yield [$modelWhoseDeletedStateCanNotBeDetermined->reveal()];

        $modelWhoseViewRightCanNotBeDetermined = $this->prophesize(Model::class);
        $modelWhoseViewRightCanNotBeDetermined->getIntRecordDeleted()
            ->willReturn(0);
        $modelWhoseViewRightCanNotBeDetermined->rightView()
            ->willThrow(Exception::class);

        yield [$modelWhoseViewRightCanNotBeDetermined->reveal()];
    }

    /**
     * @dataProvider provideModelsThatThrowExceptionsOnStateDetermination
     * @param Model $modelThatThrowsExceptions
     */
    public function testCatchesExceptionsThrownDuringAvailabilityDeterminationAndDeniesAvailability(Model $modelThatThrowsExceptions): void
    {
        $statusModelAction = new StatusModelAction(
            $this->createDummyToolkit()
        );

        $this->assertFalse($statusModelAction->supports($modelThatThrowsExceptions, $this->createDummyContext()));
    }

    /**
     * @dataProvider provideModelsInAValidState
     * @param Model $modelInAValidState
     * @throws Exception
     */
    public function testDelegatesRenderingOfModelToToolkitClass(Model $modelInAValidState): void
    {
        $statusModelAction = new StatusModelAction(
            $this->createDummyToolkit()
        );

        $this->assertNotEquals('', $statusModelAction->render($modelInAValidState, $this->createDummyContext()));
    }

    /**
     * @dataProvider provideModelsInAnInvalidState
     * @param Model $modelInAnInvalidState
     * @throws Exception
     */
    public function testThrowsExceptionWhenRenderingModelItIsNotAvailableFor(Model $modelInAnInvalidState): void
    {
        $statusModelAction = new StatusModelAction(
            $this->createDummyToolkit()
        );

        $this->expectException(UnableToRenderActionForModelException::class);
        $statusModelAction->render($modelInAnInvalidState, $this->createDummyContext());
    }

    /**
     * @dataProvider provideModelsInAValidState
     * @param Model $modelInAValidState
     * @throws Exception
     */
    public function testWrapsExceptionThrownDuringRenderingOfModel(Model $modelInAValidState): void
    {
        $statusModelAction = new StatusModelAction(
            $this->createToolkitThatThrowsExceptions()
        );

        $this->expectException(UnableToRenderActionForModelException::class);
        $statusModelAction->render($modelInAValidState, $this->createDummyContext());
    }
}
