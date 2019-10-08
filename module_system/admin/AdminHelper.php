<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                                            *
********************************************************************************************************/

namespace Kajona\System\Admin;

use Kajona\System\System\BootstrapCache;
use Kajona\System\System\Carrier;
use Kajona\System\System\Classloader;
use Kajona\System\System\Link;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemModule;


/**
 * A class holding common helper-methods for the backend.
 * The main purpose is to reduce the code stored at AdminController
 *
 * @package module_system
 * @author  sidler@mulchprod.de
 * @since   4.0
 */
class AdminHelper
{

    /**
     * Static helper to flush the complete backend navigation cache for the current session
     * May be used during language-changes or user-switches
     *
     * @return void
     */
    public static function flushActionNavigationCache()
    {

        $arrAspects = SystemAspect::getObjectListFiltered();

        foreach (SystemModule::getModulesInNaviAsArray() as $arrOneModule) {
            $objOneModule = SystemModule::getModuleByName($arrOneModule["module_name"]);
            foreach ($arrAspects as $objOneAspect) {
                Carrier::getInstance()->getObjSession()->sessionUnset(__CLASS__."adminNaviEntries".$objOneModule->getSystemid().$objOneAspect->getSystemid());
            }
        }
    }
}
