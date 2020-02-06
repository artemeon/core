<?php

namespace Kajona\System\System;

use Artemeon\HttpClient\Client\HttpClient;
use Artemeon\HttpClient\Client\HttpClientFactory;
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

    public function register(Container $objContainer)
    {
        $objContainer[self::STR_DB] = function ($c) {
            return Database::getInstance();
        };

        $objContainer[HttpClient::class] = function ($c) {
            return HttpClientFactory::withLogger($c[self::STR_LOGGER]);
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

        $objContainer[self::STR_OBJECT_FACTORY] = function ($c) {
            return Objectfactory::getInstance();
        };

        $objContainer[self::STR_OBJECT_BUILDER] = function ($c) {
            return new ObjectBuilder($c);
        };

        $objContainer[self::STR_LOGGER] = function ($c) {
            return Logger::getInstance();
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

        $objContainer[LoginProtocolCleanerInterface::class] = function (Container $c): LoginProtocolCleanerInterface {
            return new LoginProtocolCleaner(180, Logger::getInstance('loginprotocolclean.log'));
        };

        $objContainer[HttpClient::class] = function (Container $c): HttpClient {
            $logger = $c[self::STR_LOGGER];
            return HttpClientFactory::withLogger($logger);
        };
    }
}
