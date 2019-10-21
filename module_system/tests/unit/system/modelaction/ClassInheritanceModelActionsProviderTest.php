<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction;

use Kajona\System\System\Exceptions\InvalidInheritanceClassNameGivenException;
use Kajona\System\System\Exceptions\UnableToRetrieveActionsForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\ClassInheritanceModelActionsProvider;
use Kajona\System\System\Modelaction\ModelActionContext;
use Kajona\System\System\Modelaction\ModelActionListInterface;
use Kajona\System\System\Modelaction\ModelActionsProviderInterface;
use Kajona\System\System\Root;
use Kajona\System\System\SystemModule;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;

final class ClassInheritanceModelActionsProviderTest extends TestCase
{
    /**
     * @var ModelActionContext
     */
    private static $dummyContext;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$dummyContext = new ModelActionContext(null);
    }

    /**
     * @dataProvider provideModelClassNames
     * @param string $validInheritanceClassName
     * @throws \Exception
     */
    public function testAllowsInstantiationWhenGivenAClassNameThatInheritsFromModel(string $validInheritanceClassName): void
    {
        $modelActionsProvider = new ClassInheritanceModelActionsProvider(
            $validInheritanceClassName,
            $this->createModelActionListThatSupportsEverything()
        );
        $this->assertInstanceOf(ModelActionsProviderInterface::class, $modelActionsProvider);
    }

    public function provideInvalidInheritanceClassNames(): iterable
    {
        yield [\stdClass::class];
        yield [Root::class];
        yield [__CLASS__];
        yield [ModelActionContext::class];
    }

    /**
     * @dataProvider provideInvalidInheritanceClassNames
     * @param string $invalidInheritanceClassName
     * @throws \Exception
     */
    public function testPreventsInstantiationWhenGivenAClassNameThatDoesNotInheritFromModel(string $invalidInheritanceClassName): void
    {
        $this->expectException(InvalidInheritanceClassNameGivenException::class);
        new ClassInheritanceModelActionsProvider(
            $invalidInheritanceClassName,
            $this->createModelActionListThatSupportsEverything()
        );
    }

    /**
     * @dataProvider provideModelAndModelActionContextPairs
     * @param Model $model
     * @param ModelActionContext $context
     * @throws \Exception
     */
    public function testSupportsAnyModelInTheInheritanceChainOfTheGivenClass(Model $model, ModelActionContext $context): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $modelActionsProvider = new ClassInheritanceModelActionsProvider(
            Model::class,
            $this->createModelActionListThatSupportsEverything()
        );
        $this->assertTrue($modelActionsProvider->supports($model, $context));
    }

    public function provideModelsAndInvalidInheritanceClassNames(): iterable
    {
        yield [
            $this->prophesize(Model::class)->reveal(),
            UserUser::class,
        ];
        yield [
            $this->prophesize(UserUser::class)->reveal(),
            SystemModule::class,
        ];
        yield [
            $this->prophesize(UserGroup::class)->reveal(),
            UserUser::class,
        ];
    }

    /**
     * @dataProvider provideModelsAndInvalidInheritanceClassNames
     * @param Model $model
     * @param string $unrelatedClassName
     * @throws \Exception
     */
    public function testDeniesSupportForAModelThatDoesNotInheritTheGivenClass(Model $model, string $unrelatedClassName): void
    {
        $modelActionsProvider = new ClassInheritanceModelActionsProvider(
            $unrelatedClassName,
            $this->createModelActionListThatSupportsEverything()
        );
        $this->assertFalse($modelActionsProvider->supports($model, self::$dummyContext));
    }

    public function provideModelsAndValidInheritanceClassNames(): iterable
    {
        yield [
            $this->prophesize(Model::class)->reveal(),
            Model::class,
        ];
        yield [
            $this->prophesize(UserUser::class)->reveal(),
            Model::class,
        ];
        yield [
            $this->prophesize(UserGroup::class)->reveal(),
            Model::class,
        ];
        yield [
            $this->prophesize(SystemModule::class)->reveal(),
            Model::class,
        ];
        yield [
            $this->prophesize(UserUser::class)->reveal(),
            UserUser::class,
        ];
        yield [
            $this->prophesize(UserGroup::class)->reveal(),
            UserGroup::class,
        ];
        yield [
            $this->prophesize(SystemModule::class)->reveal(),
            SystemModule::class,
        ];
    }

    /**
     * @dataProvider provideModelsAndValidInheritanceClassNames
     * @param Model $model
     * @param string $inheritanceClassName
     * @throws \Exception
     */
    public function testRetrievesInitiallyGivenModelActionsForASupportedModel(Model $model, string $inheritanceClassName): void
    {
        $modelActionList = $this->createModelActionListThatSupportsEverything();
        $modelActionsProvider = new ClassInheritanceModelActionsProvider(
            $inheritanceClassName,
            $modelActionList
        );

        $this->assertEquals($modelActionList, $modelActionsProvider->getActions($model, self::$dummyContext));
    }

    /**
     * @dataProvider provideModelsAndInvalidInheritanceClassNames
     * @param Model $model
     * @param string $unrelatedClassName
     * @throws \Exception
     */
    public function testThrowsExceptionOnActionsRetrievalForAnUnsupportedModel(Model $model, string $unrelatedClassName): void
    {
        $modelActionsProvider = new ClassInheritanceModelActionsProvider(
            $unrelatedClassName,
            $this->createModelActionListThatSupportsNothing()
        );

        $this->expectException(UnableToRetrieveActionsForModelException::class);
        $modelActionsProvider->getActions($model, self::$dummyContext);
    }
}
