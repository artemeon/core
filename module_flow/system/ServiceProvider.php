<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Flow\System;

use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\ServiceProvider as SystemServiceProvider;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * @author christoph.kappestein@artemeon.de
 * @module flow
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @see FlowManager
     */
    const STR_MANAGER = "flow_manager";

    /**
     * @see FlowHandlerFactory
     */
    const STR_HANDLER_FACTORY = "flow_handler_factory";

    public function register(Container $c)
    {
        $c[self::STR_MANAGER] = function($c){
            return new FlowManager();
        };

        $c[self::STR_HANDLER_FACTORY] = function($c){
            return new FlowHandlerFactory(
                $c[self::STR_MANAGER],
                $c[\Kajona\System\System\ServiceProvider::STR_LIFE_CYCLE_FACTORY]
            );
        };
        $c[FlowInstallerHelper::class] = static function (Container $container): FlowInstallerHelper {
            /** @var ServiceLifeCycleFactory $lifeCycleFactory */
            $lifeCycleFactory = $container[SystemServiceProvider::STR_LIFE_CYCLE_FACTORY];

            return new FlowInstallerHelper(
                $container[self::STR_MANAGER],
                $lifeCycleFactory->factory(FlowConfig::class),
                $lifeCycleFactory->factory(FlowStatus::class),
                $lifeCycleFactory->factory(FlowTransition::class),
                $lifeCycleFactory->factory(FlowConditionAbstract::class),
                $lifeCycleFactory->factory(FlowActionAbstract::class)
            );
        };
    }
}
