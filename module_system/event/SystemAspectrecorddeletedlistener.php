<?php
/*"******************************************************************************************************
*   (c) 2015-2016 by Kajona, www.kajona.de                                                         *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Event;

use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\GenericeventListenerInterface;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemEventidentifier;


/**
 * Updates the default aspect in case the default one was deleted
 *
 * @package module_system
 * @author sidler@mulchprod.de
 *
 */
class SystemAspectrecorddeletedlistener implements GenericeventListenerInterface {


    /**
     *
     * @param string $strEventName
     * @param array $arrArguments
     *
     * @return bool
     */
    public function handleEvent($strEventName, array $arrArguments) {
        //unwrap arguments
        list($strSystemid, $strSourceClass) = $arrArguments;

        if($strSourceClass == 'Kajona\System\System\SystemAspect') {

            //if we have just one aspect remaining, set this one as default
            if(SystemAspect::getObjectCountFiltered() == 1) {
                /** @var SystemAspect[] $arrObjAspects */
                $arrObjAspects = SystemAspect::getObjectListFiltered();
                $objOneAspect = $arrObjAspects[0];
                $objOneAspect->setBitDefault(1);
                try {
                    ServiceLifeCycleFactory::getLifeCycle(get_class($objOneAspect))->update($objOneAspect);
                } catch (ServiceLifeCycleUpdateException $e) {
                    return false;
                }
            }
        }

        return true;
    }

}

//static inits
CoreEventdispatcher::getInstance()->removeAndAddListener(SystemEventidentifier::EVENT_SYSTEM_RECORDDELETED_LOGICALLY, new SystemAspectrecorddeletedlistener());
