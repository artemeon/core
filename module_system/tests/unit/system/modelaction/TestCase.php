<?php

declare(strict_types=1);

namespace Kajona\System\Tests\Unit\System\Modelaction;

use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\LanguagesLanguage;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Kajona\System\System\Modelaction\Actionlist\ModelActionsContainerInterface;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSession;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Prophecy\Argument;

abstract class TestCase extends BaseTestCase
{
    private const MODEL_CLASS_NAMES_AND_LIST_IDENTIFIERS = [
        Model::class => 'list',
        LanguagesLanguage::class => 'listLanguages',
        SystemModule::class => 'listSystemModules',
        SystemSession::class => 'listSystemSessions',
        SystemSetting::class => 'listSystemSettings',
        UserGroup::class => 'listUserGroups',
        UserUser::class => 'listUsers',
    ];

    public function provideModelAndModelActionContextPairs(): iterable
    {
        foreach (self::MODEL_CLASS_NAMES_AND_LIST_IDENTIFIERS as $modelClassName => $listIdentifier) {
            $model = $this->prophesize($modelClassName);
            $context = new ModelActionContext($listIdentifier);

            yield [$model->reveal(), $context];
        }
    }

    public function provideModelClassNames(): iterable
    {
        foreach (\array_keys(self::MODEL_CLASS_NAMES_AND_LIST_IDENTIFIERS) as $modelClassName) {
            yield [$modelClassName];
        }
    }

    public function provideModelActionListIdentifiers(): iterable
    {
        foreach (self::MODEL_CLASS_NAMES_AND_LIST_IDENTIFIERS as $listIdentifier) {
            yield [$listIdentifier];
        }
    }

    public function provideModelActionContexts(): iterable
    {
        foreach (self::MODEL_CLASS_NAMES_AND_LIST_IDENTIFIERS as $listIdentifier) {
            $context = new ModelActionContext($listIdentifier);
            yield [$context];
        }
    }

    protected function createModelActionListThatSupportsEverything(): ModelActionsContainerInterface
    {
        $modelActionList = $this->prophesize(ModelActionsContainerInterface::class);
        /** @noinspection PhpParamsInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        $modelActionList->supports(Argument::cetera())
            ->willReturn(true);
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpParamsInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        $modelActionList->renderAll(Argument::cetera())
            ->willReturn('rendered');

        return $modelActionList->reveal();
    }

    protected function createModelActionListThatSupportsNothing(): ModelActionsContainerInterface
    {
        $modelActionList = $this->prophesize(ModelActionsContainerInterface::class);
        /** @noinspection PhpParamsInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        $modelActionList->supports(Argument::cetera())
            ->willReturn(false);
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpParamsInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection PhpUndefinedMethodInspection */
        $modelActionList->renderAll(Argument::cetera())
            ->willThrow(UnableToRenderActionForModelException::class);

        return $modelActionList->reveal();
    }
}
