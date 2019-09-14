<?php

namespace Kajona\Dashboard\System;

use Kajona\Dashboard\Service\DashboardInitializerService;
use Kajona\Dashboard\System\Lifecycle\ConfigLifecycle;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * ServiceProvider for the dashboard module
 *
 * @package Kajona\System\System
 * @author sidler@mulchprod.de
 * @since 6.2
 */
class ServiceProvider implements ServiceProviderInterface
{
    const STR_DASHBOARD_INITIALIZER = "dashboard_initializer";

    const STR_DASHBOARD_LIFECYLCE_CONFIG = "dashboard_life_cycle_config";

    const DASHBOARD_ICAL_GENERATOR = "dashboard_ical_generator";


    public function register(Container $objContainer)
    {
        $objContainer[self::STR_DASHBOARD_INITIALIZER] = function ($c) {
            return new DashboardInitializerService();
        };

        $objContainer[self::STR_DASHBOARD_LIFECYLCE_CONFIG] = function ($c) {
            return new ConfigLifecycle(
                $c[\Kajona\System\System\ServiceProvider::STR_PERMISSION_HANDLER_FACTORY],
                $c[\Kajona\System\System\ServiceProvider::STR_SESSION]
            );
        };

        $objContainer[self::DASHBOARD_ICAL_GENERATOR] = function ($c) {
            return new ServiceICalGenerator();
        };
    }
}
