<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction\Action;

use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Action\PermissionsModelAction;
use Kajona\System\System\ModelInterface;

final class PermissionsModelActionTest extends ModelActionTestCase
{
    /**
     * @return Model
     * @throws Exception
     */
    private function createModelThatIsNotDeletedAndCanBeEdited(): Model
    {
        $model = $this->prophesize(Model::class);
        $model->willImplement(ModelInterface::class);
        $model->getStrSystemid()
            ->willReturn('');
        /** @noinspection PhpUndefinedMethodInspection */
        $model->getStrDisplayName()
            ->willReturn('');
        $model->getIntRecordDeleted()
            ->willReturn(0);
        $model->rightEdit()
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
            yield [$this->createModelThatIsNotDeletedAndCanBeEdited()];
        }
    }

    /**
     * @dataProvider provideModelsInAValidState
     * @param Model $modelInAValidState
     */
    public function testConfirmsAvailabilityForModelThatIsInAValidState(Model $modelInAValidState): void
    {
        $permissionsModelAction = new PermissionsModelAction(
            $this->createDummyModelControllerLocator(),
            $this->createDummyToolkit(),
            $this->createDummyLang()
        );

        $this->assertTrue(
            $permissionsModelAction->supports($modelInAValidState, $this->createDummyContext())
        );
    }

    /**
     * @return Model
     * @throws Exception
     */
    private function createModelThatIsDeletedButCanBeEdited(): Model
    {
        $model = $this->prophesize(Model::class);
        $model->getIntRecordDeleted()
            ->willReturn(1);
        $model->rightEdit()
            ->willReturn(true);

        return $model->reveal();
    }

    /**
     * @return Model
     * @throws Exception
     */
    private function createModelThatIsNotDeletedButCanNotBeEdited(): Model
    {
        $model = $this->prophesize(Model::class);
        $model->getIntRecordDeleted()
            ->willReturn(0);
        $model->rightEdit()
            ->willReturn(false);

        return $model->reveal();
    }

    /**
     * @return Model
     * @throws Exception
     */
    private function createModelThatIsDeletedAndCanNotBeEdited(): Model
    {
        $model = $this->prophesize(Model::class);
        $model->getIntRecordDeleted()
            ->willReturn(1);
        $model->rightEdit()
            ->willReturn(false);

        return $model->reveal();
    }

    /**
     * @return iterable
     * @throws Exception
     */
    public function provideModelsInAnInvalidState(): iterable
    {
        yield [$this->createModelThatIsDeletedButCanBeEdited()];
        yield [$this->createModelThatIsNotDeletedButCanNotBeEdited()];
        yield [$this->createModelThatIsDeletedAndCanNotBeEdited()];
    }

    /**
     * @dataProvider provideModelsInAnInvalidState
     * @param Model $modelInAnInvalidState
     */
    public function testDeniesAvailabilityForModelThatIsInAnInvalidState(Model $modelInAnInvalidState): void
    {
        $permissionsModelAction = new PermissionsModelAction(
            $this->createDummyModelControllerLocator(),
            $this->createDummyToolkit(),
            $this->createDummyLang()
        );

        $this->assertFalse(
            $permissionsModelAction->supports($modelInAnInvalidState, $this->createDummyContext())
        );
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
        $modelWhoseDeletedStateCanNotBeDetermined->rightEdit()
            ->willReturn(true);

        yield [$modelWhoseDeletedStateCanNotBeDetermined->reveal()];

        $modelWhoseEditRightCanNotBeDetermined = $this->prophesize(Model::class);
        $modelWhoseEditRightCanNotBeDetermined->getIntRecordDeleted()
            ->willReturn(0);
        $modelWhoseEditRightCanNotBeDetermined->rightEdit()
            ->willThrow(Exception::class);

        yield [$modelWhoseEditRightCanNotBeDetermined->reveal()];
    }

    /**
     * @dataProvider provideModelsThatThrowExceptionsOnStateDetermination
     * @param Model $modelThatThrowsExceptions
     */
    public function testCatchesExceptionsThrownDuringAvailabilityDeterminationAndDeniesAvailability(Model $modelThatThrowsExceptions): void
    {
        $permissionsModelAction = new PermissionsModelAction(
            $this->createDummyModelControllerLocator(),
            $this->createDummyToolkit(),
            $this->createDummyLang()
        );

        $this->assertFalse(
            $permissionsModelAction->supports($modelThatThrowsExceptions, $this->createDummyContext())
        );
    }

    /**
     * @dataProvider provideModelsInAValidState
     * @param Model $modelInAValidState
     * @throws \Exception
     */
    public function testDelegatesRenderingOfModelToToolkitClass(Model $modelInAValidState): void
    {
        $permissionsModelAction = new PermissionsModelAction(
            $this->createModelControllerLocatorThatReturnsAModelController(),
            $this->createDummyToolkit(),
            $this->createDummyLang()
        );

        $this->assertNotEquals('', $permissionsModelAction->render($modelInAValidState, $this->createDummyContext()));
    }

    /**
     * @dataProvider provideModelsInAnInvalidState
     * @param Model $modelInAnInvalidState
     * @throws Exception
     */
    public function testThrowsExceptionWhenRenderingModelItIsNotAvailableFor(Model $modelInAnInvalidState): void
    {
        $permissionsModelAction = new PermissionsModelAction(
            $this->createDummyModelControllerLocator(),
            $this->createDummyToolkit(),
            $this->createDummyLang()
        );

        $this->expectException(UnableToRenderActionForModelException::class);
        $permissionsModelAction->render($modelInAnInvalidState, $this->createDummyContext());
    }

    /**
     * @dataProvider provideModelsInAValidState
     * @param Model $modelInAValidState
     * @throws \Exception
     */
    public function testWrapsExceptionThrownDuringRenderingOfModel(Model $modelInAValidState): void
    {
        $permissionsModelAction = new PermissionsModelAction(
            $this->createModelControllerLocatorThatReturnsAModelController(),
            $this->createToolkitThatThrowsExceptions(),
            $this->createDummyLang()
        );

        $this->expectException(UnableToRenderActionForModelException::class);
        $permissionsModelAction->render($modelInAValidState, $this->createDummyContext());
    }
}
