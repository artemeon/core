<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Kajona\System\System\Lifecycle\User\GroupLifecycle;
use Kajona\System\System\Lifecycle\User\UserLifecycle;
use Kajona\System\System\Messagequeue\Consumer;
use Kajona\System\System\Messagequeue\Executor\CallEventExecutor;
use Kajona\System\System\Messagequeue\Executor\SendMessageExecutor;
use Kajona\System\System\Messagequeue\Executor\SetRecursiveRightsExecutor;
use Kajona\System\System\Messagequeue\ExecutorFactory;
use Kajona\System\System\Messagequeue\Producer;
use Kajona\System\System\Modelaction\Action\ChangeHistoryModelAction;
use Kajona\System\System\Modelaction\Action\CopyModelAction;
use Kajona\System\System\Modelaction\Action\DeleteModelAction;
use Kajona\System\System\Modelaction\Action\EditModelAction;
use Kajona\System\System\Modelaction\Action\Legacy\LegacyAdditionalModelAction;
use Kajona\System\System\Modelaction\Action\Legacy\LegacyChangeHistoryModelAction;
use Kajona\System\System\Modelaction\Action\Legacy\LegacyCopyModelAction;
use Kajona\System\System\Modelaction\Action\Legacy\LegacyDeleteModelAction;
use Kajona\System\System\Modelaction\Action\Legacy\LegacyEditModelAction;
use Kajona\System\System\Modelaction\Action\Legacy\LegacyPermissionsModelAction;
use Kajona\System\System\Modelaction\Action\Legacy\LegacyStatusModelAction;
use Kajona\System\System\Modelaction\Action\Legacy\LegacyTagModelAction;
use Kajona\System\System\Modelaction\Action\Legacy\LegacyUnlockModelAction;
use Kajona\System\System\Modelaction\Action\PermissionsModelAction;
use Kajona\System\System\Modelaction\Action\StatusModelAction;
use Kajona\System\System\Modelaction\Action\TagModelAction;
use Kajona\System\System\Modelaction\Action\UnlockModelAction;
use Kajona\System\System\Modelaction\Actionlist\DefaultModelActionList;
use Kajona\System\System\Modelaction\Actionlist\Legacy\LegacyModelActionList;
use Kajona\System\System\Modelaction\Actionlist\ModelActionsContainerInterface;
use Kajona\System\System\Modelaction\Context\ModelActionContextFactory;
use Kajona\System\System\Modelaction\Context\ModelActionContextFactoryInterface;
use Kajona\System\System\Modelaction\Register\InMemoryModelActionsContainerRegistry;
use Kajona\System\System\Modelaction\Register\ModelActionsContainerRegistryInterface;
use Kajona\System\System\Modelaction\Renderer\CachedModelActionsRenderer;
use Kajona\System\System\Modelaction\Renderer\DefaultModelActionsRenderer;
use Kajona\System\System\Modelaction\Renderer\ModelActionsRendererInterface;
use Kajona\System\System\Permissions\PermissionHandlerFactory;
use Kajona\System\System\Security\PasswordRotator;
use Kajona\System\System\Security\PasswordValidator;
use Kajona\System\System\Security\Policy;
use Kajona\System\System\Template\Loader;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * ServiceProvider
 *
 * @package Kajona\System\System
 * @author christoph.kappestein@gmail.com
 * @since 4.6
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @see \Kajona\System\System\Database
     */
    const STR_DB = "system_db";

    /**
     * @see \Kajona\System\System\Rights
     */
    const STR_RIGHTS = "system_rights";

    /**
     * @see \Kajona\System\System\Config
     */
    const STR_CONFIG = "system_config";

    /**
     * @see \Kajona\System\System\Session
     */
    const STR_SESSION = "system_session";

    /**
     * @see \Kajona\System\Admin\ToolkitAdmin
     */
    const STR_ADMINTOOLKIT = "system_admintoolkit";

    /**
     * @see \Kajona\System\System\Resourceloader
     */
    const STR_RESOURCE_LOADER = "system_resource_loader";

    /**
     * @see \Kajona\System\System\Classloader
     */
    const STR_CLASS_LOADER = "system_class_loader";

    /**
     * @see \Kajona\System\System\Template
     */
    const STR_TEMPLATE = "system_template";

    /**
     * @see \Twig_Environment
     */
    const STR_TEMPLATE_ENGINE = "system_template_engine";

    /**
     * @see \Kajona\System\System\Lang
     */
    const STR_LANG = "system_lang";

    /**
     * @see \Kajona\System\System\Objectfactory
     */
    const STR_OBJECT_FACTORY = "system_object_factory";

    /**
     * @see \Kajona\System\System\ObjectBuilder
     */
    const STR_OBJECT_BUILDER = "system_object_builder";

    /**
     * @see \Psr\Log\LoggerInterface
     */
    const STR_LOGGER = "system_logger";

    /**
     * @see \Kajona\System\System\CacheManager
     */
    const STR_CACHE_MANAGER = "system_cache_manager";

    /**
     * @see \Kajona\System\System\Lifecycle\ServiceLifeCycleFactory
     */
    const STR_LIFE_CYCLE_FACTORY = "system_life_cycle_factory";

    /**
     * @see \Kajona\System\System\Lifecycle\ServiceLifeCycleImpl
     */
    const STR_LIFE_CYCLE_DEFAULT = "system_life_cycle_default";

    /**
     * @see \Kajona\System\System\MessagingAlertLifeCycle
     */
    const STR_LIFE_CYCLE_MESSAGES_ALERT = "system_life_cycle_messages_alert";

    /**
     * @see \Kajona\System\System\Lifecycle\User\UserLifecycle
     */
    const LIFE_CYLE_USER_USER = "system_life_cycle_user_user";

    /**
     * @see \Kajona\System\System\Lifecycle\User\GroupLifecycle
     */
    const LIFE_CYLE_USER_GROUP = "system_life_cycle_user_group";

    /**
     * @see \Kajona\System\System\MessagingMessagehandler
     */
    const STR_MESSAGE_HANDLER = "system_message_handler";

    /**
     * @see \Kajona\System\System\Security\PasswordRotator
     */
    const STR_PASSWORD_ROTATOR = "system_password_rotator";

    /**
     * @see \Kajona\System\System\Security\PasswordValidatorInterface
     */
    const STR_PASSWORD_VALIDATOR = "system_password_validator";

    /**
     * @see \Kajona\System\System\Permissions\PermissionHandlerFactory
     */
    const STR_PERMISSION_HANDLER_FACTORY = "system_permission_handler_factory";

    /**
     * @see \Kajona\System\System\DropdownLoaderInterface
     */
    const STR_DROPDOWN_LOADER = "system_dropdown_loader";

    /**
     * @see \Kajona\System\System\CoreEventdispatcher
     */
    const EVENT_DISPATCHER = "system_event_dispatcher";

    /**
     * @see \Kajona\System\System\Messagequeue\Producer
     */
    const MESSAGE_QUEUE_PRODUCER = "system_message_queue_producer";

    /**
     * @see \Kajona\System\System\Messagequeue\Consumer
     */
    const MESSAGE_QUEUE_CONSUMER = "system_message_queue_consumer";

    /**
     * @see \Kajona\System\System\Messagequeue\ExecutorFactory
     */
    const MESSAGE_QUEUE_EXECUTOR_FACTORY = "system_message_queue_executor_factory";

    /**
     * @see \Kajona\System\System\Messagequeue\Executor\CallEventExecutor
     */
    const MESSAGE_QUEUE_EXECUTOR_CALL_EVENT = "system_message_queue_executor_call_event";

    /**
     * @see \Kajona\System\System\Messagequeue\Executor\SendMessageExecutor
     */
    const MESSAGE_QUEUE_EXECUTOR_SEND_MESSAGE = "system_message_queue_executor_send_message";

    /**
     * @see SetRecursiveRightsExecutor
     */
    const RECURSIVE_RIGHT_EXECUTOR = "system_recursive_right_executor";

    private const USE_LEGACY_MODEL_ACTIONS = true;

    private const CACHE_RENDERED_MODEL_ACTIONS = true;

    private function registerModelActions(Container $container): void
    {
        $container[DefaultModelActionList::class] = static function (Container $container): ModelActionsContainerInterface {
            $modelControllerProvider = $container[ModelControllerLocatorInterface::class];
            $featureDetector = $container[FeatureDetectorInterface::class];
            $toolkit = $container[self::STR_ADMINTOOLKIT];
            $lang = $container[self::STR_LANG];

            return new DefaultModelActionList(
                new UnlockModelAction($toolkit, $lang),
                new EditModelAction($modelControllerProvider, $toolkit, $lang),
                new DeleteModelAction($modelControllerProvider, $toolkit, $lang),
                new CopyModelAction($modelControllerProvider, $toolkit, $lang),
                new StatusModelAction($toolkit),
                new TagModelAction($featureDetector, $toolkit, $lang),
                new ChangeHistoryModelAction($featureDetector, $toolkit, $lang),
                new PermissionsModelAction($modelControllerProvider, $toolkit, $lang)
            );
        };

        // The default model actions container can only be registered after removing the legacy model actions container
        if (!self::USE_LEGACY_MODEL_ACTIONS) {
            $container->extend(
                ModelActionsContainerRegistryInterface::class,
                static function (ModelActionsContainerRegistryInterface $modelActionsContainerRegistry, Container $container): ModelActionsContainerRegistryInterface {
                    $modelActionsContainerRegistry->register(
                        Model::class,
                        $container[DefaultModelActionList::class]
                    );

                    return $modelActionsContainerRegistry;
                }
            );
        }
    }

    private function registerLegacyModelActions(Container $container): void
    {
        $container[LegacyModelActionList::class] = static function (Container $container): ModelActionsContainerInterface {
            $modelControllerProvider = $container[ModelControllerLocatorInterface::class];

            return new LegacyModelActionList(
                new LegacyUnlockModelAction($modelControllerProvider),
                new LegacyEditModelAction($modelControllerProvider),
                new LegacyAdditionalModelAction($modelControllerProvider),
                new LegacyDeleteModelAction($modelControllerProvider),
                new LegacyCopyModelAction($modelControllerProvider),
                new LegacyStatusModelAction($modelControllerProvider),
                new LegacyTagModelAction($modelControllerProvider),
                new LegacyChangeHistoryModelAction($modelControllerProvider),
                new LegacyPermissionsModelAction($modelControllerProvider)
            );
        };
        $container->extend(
            ModelActionsContainerRegistryInterface::class,
            static function (ModelActionsContainerRegistryInterface $modelActionsContainerRegistry, Container $container): ModelActionsContainerRegistryInterface {
                $modelActionsContainerRegistry->register(
                    Model::class,
                    $container[LegacyModelActionList::class]
                );

                return $modelActionsContainerRegistry;
            }
        );
    }

    private function cacheModelActionsRenderer(Container $container): void
    {
        $container->extend(
            ModelActionsRendererInterface::class,
            static function (ModelActionsRendererInterface $modelActionsRenderer, Container $container): ModelActionsRendererInterface {
                return new CachedModelActionsRenderer(
                    $modelActionsRenderer,
                    $container[self::STR_CACHE_MANAGER],
                    $container[ModelCacheKeyGeneratorInterface::class]
                );
            }
        );
    }

    public function register(Container $objContainer): void
    {
        $objContainer[self::STR_DB] = function ($c) {
            return Database::getInstance();
        };

        $objContainer[self::STR_RIGHTS] = function ($c) {
            return Rights::getInstance();
        };

        $objContainer[self::STR_CONFIG] = function ($c) {
            return Config::getInstance();
        };

        $objContainer[self::STR_SESSION] = function ($c) {
            return Session::getInstance();
        };

        $objContainer[self::STR_ADMINTOOLKIT] = function ($c) {
            // decide which class to load
            $strAdminToolkitClass = $c[self::STR_CONFIG]->getConfig("admintoolkit");
            if ($strAdminToolkitClass == "") {
                $strAdminToolkitClass = "ToolkitAdmin";
            }

            $strPath = Resourceloader::getInstance()->getPathForFile("/admin/".$strAdminToolkitClass.".php");
            return Classloader::getInstance()->getInstanceFromFilename($strPath);
        };

        $objContainer[self::STR_RESOURCE_LOADER] = function ($c) {
            return Resourceloader::getInstance();
        };

        $objContainer[self::STR_CLASS_LOADER] = function ($c) {
            return Classloader::getInstance();
        };

        $objContainer[self::STR_TEMPLATE] = function ($c) {
            return new Template(
                new TemplateFileParser(),
                new TemplateSectionParser(),
                new TemplatePlaceholderParser(),
                new TemplateBlocksParser()
            );
        };

        $objContainer[self::STR_TEMPLATE_ENGINE] = function ($c) {
            $debug = $c[self::STR_CONFIG]->getDebug("debuglevel") == 1;
            $loader = new Loader(_realpath_);

            $twig = new \Twig_Environment($loader, array(
                'cache' => _realpath_ . 'project/temp/cache',
                'debug' => $debug,
            ));

            $twig->addFilter(new \Twig_Filter('lang', [$c[self::STR_LANG], "getLang"]));
            $twig->addFilter(new \Twig_Filter('date_to_string', 'dateToString'));
            $twig->addFilter(new \Twig_Filter('number_format', 'numberFormat'));
            $twig->addFilter(new \Twig_Filter('webpath', function ($module) {
                return Resourceloader::getInstance()->getWebPathForModule($module);
            }));

            return $twig;
        };

        $objContainer[self::STR_LANG] = function ($c) {
            return Lang::getInstance();
        };

        $objContainer[self::STR_OBJECT_FACTORY] = static function (Container $container): Objectfactory {
            return new Objectfactory(
                $container[self::STR_DB],
                BootstrapCache::getInstance()
            );
        };

        $objContainer[self::STR_OBJECT_BUILDER] = function ($c) {
            return new ObjectBuilder($c);
        };

        $objContainer[self::STR_LOGGER] = function ($c) {
            return Logger::getInstance();
        };

        $objContainer[self::EVENT_DISPATCHER] = function ($c) {
            return CoreEventdispatcher::getInstance();
        };

        $objContainer[self::STR_CACHE_MANAGER] = function ($c) {
            return new CacheManager();
        };

        $objContainer[self::STR_MESSAGE_HANDLER] = function ($c) {
            return new MessagingMessagehandler(
                $c[self::STR_LIFE_CYCLE_FACTORY]
            );
        };

        $objContainer[self::STR_LIFE_CYCLE_FACTORY] = function ($c) {
            return new Lifecycle\ServiceLifeCycleFactory($c);
        };

        $objContainer[self::STR_LIFE_CYCLE_DEFAULT] = function ($c) {
            return new Lifecycle\ServiceLifeCycleImpl(
                $c[ServiceProvider::STR_PERMISSION_HANDLER_FACTORY]
            );
        };

        $objContainer[self::STR_LIFE_CYCLE_MESSAGES_ALERT] = function ($c) {
            return new MessagingAlertLifeCycle(
                $c[ServiceProvider::STR_PERMISSION_HANDLER_FACTORY]
            );
        };

        $objContainer[self::LIFE_CYLE_USER_USER] = function ($c) {
            return new UserLifecycle(
                $c[ServiceProvider::STR_PERMISSION_HANDLER_FACTORY],
                Logger::getInstance(Logger::USERSOURCES)
            );
        };

        $objContainer[self::LIFE_CYLE_USER_GROUP] = function ($c) {
            return new GroupLifecycle(
                $c[ServiceProvider::STR_PERMISSION_HANDLER_FACTORY],
                Logger::getInstance(Logger::USERSOURCES)
            );
        };

        $objContainer[self::STR_PASSWORD_VALIDATOR] = function ($c) {
            $arrConfig = $c[self::STR_CONFIG]->getConfig("password_validator");

            $arrMinLength = $arrConfig["minlength"] ?? [];
            $arrComplexity = $arrConfig["complexity"] ?? [];
            $arrPasswordHistory = $arrConfig["passwordhistory"] ?? [];
            $arrBlacklist = $arrConfig["blacklist"] ?? [];

            $objValidator = new PasswordValidator($c[self::STR_LANG]);
            $objValidator->addPolicy(new Policy\UserName());
            $objValidator->addPolicy(new Policy\MinLength(...$arrMinLength));
            $objValidator->addPolicy(new Policy\Complexity(...$arrComplexity));
            $objValidator->addPolicy(new Policy\PasswordHistory(...$arrPasswordHistory));
            $objValidator->addPolicy(new Policy\Blacklist($arrBlacklist));

            return $objValidator;
        };

        $objContainer[self::STR_PASSWORD_ROTATOR] = function ($c) {
            return new PasswordRotator(
                $c[self::STR_LANG],
                $c[\Kajona\System\System\ServiceProvider::STR_LIFE_CYCLE_FACTORY],
                $c[self::STR_CONFIG]->getConfig("password_rotation_days")
            );
        };

        $objContainer[self::STR_PERMISSION_HANDLER_FACTORY] = function ($c) {
            return new PermissionHandlerFactory($c);
        };

        $objContainer[self::STR_DROPDOWN_LOADER] = function ($c) {
            return new DropdownConfigLoader();
        };

        $objContainer[self::MESSAGE_QUEUE_PRODUCER] = function ($c) {
            return new Producer(
                $c[self::STR_DB]
            );
        };

        $objContainer[self::MESSAGE_QUEUE_CONSUMER] = function ($c) {
            return new Consumer(
                $c[self::STR_DB],
                $c[self::MESSAGE_QUEUE_EXECUTOR_FACTORY],
                $c[self::STR_LOGGER]
            );
        };

        $objContainer[self::MESSAGE_QUEUE_EXECUTOR_FACTORY] = function ($c) {
            return new ExecutorFactory($c);
        };

        $objContainer[self::MESSAGE_QUEUE_EXECUTOR_CALL_EVENT] = function ($c) {
            return new CallEventExecutor(
                $c[self::EVENT_DISPATCHER]
            );
        };

        $objContainer[self::MESSAGE_QUEUE_EXECUTOR_SEND_MESSAGE] = function ($c) {
            return new SendMessageExecutor();
        };

        $objContainer[self::RECURSIVE_RIGHT_EXECUTOR] = function ($c) {
            return new SetRecursiveRightsExecutor(
                $c[self::STR_RIGHTS]
            );
        };

        $objContainer[FeatureDetectorInterface::class] = static function (Container $container): FeatureDetectorInterface {
            return new CachedFeatureDetector(
                new SystemFeatureDetector(),
                $container[self::STR_CACHE_MANAGER],
                $container[self::STR_SESSION]
            );
        };
        $objContainer[ModelControllerLocatorInterface::class] = static function (): ModelControllerLocatorInterface {
            return new AnnotationBasedModelControllerLocator();
        };
        $objContainer[ModelCacheKeyGeneratorInterface::class] = static function (): ModelCacheKeyGeneratorInterface {
            return new DefaultModelCacheKeyGenerator();
        };

        $objContainer[ModelActionContextFactoryInterface::class] = static function (): ModelActionContextFactoryInterface {
            return new ModelActionContextFactory();
        };
        $objContainer[ModelActionsContainerRegistryInterface::class] = static function (): ModelActionsContainerRegistryInterface {
            return new InMemoryModelActionsContainerRegistry();
        };
        $objContainer[ModelActionsRendererInterface::class] = static function (Container $container): ModelActionsRendererInterface {
            return new DefaultModelActionsRenderer(
                $container[ModelActionsContainerRegistryInterface::class]
            );
        };

        $objContainer['use_legacy_model_actions'] = self::USE_LEGACY_MODEL_ACTIONS;

        $this->registerLegacyModelActions($objContainer);
        $this->registerModelActions($objContainer);

        if (self::CACHE_RENDERED_MODEL_ACTIONS) {
            $this->cacheModelActionsRenderer($objContainer);
        }
    }
}
