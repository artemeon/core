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
     * Fetches the list of actions for a single module, saved to the session for performance reasons
     *
     * @param SystemModule $objModule
     *
     * @return array
     * @throws \Kajona\System\System\Exception
     * @static
     *
     */
    public static function getModuleActionNaviHelper(SystemModule $objModule)
    {
        if (Carrier::getInstance()->getObjSession()->isLoggedin()) {
            $strKey = __CLASS__."adminNaviEntries".$objModule->getSystemid().SystemAspect::getCurrentAspectId();

            $arrFinalItems = Carrier::getInstance()->getObjSession()->getSession($strKey);
            if ($arrFinalItems !== false) {
                return $arrFinalItems;
            }

            $objAdminInstance = $objModule->getAdminInstanceOfConcreteModule();
            if ($objAdminInstance == null) {
                return array();
            }

            $arrItems = $objAdminInstance->getOutputModuleNavi();
            $arrItems = array_merge($arrItems, $objAdminInstance->getModuleRightNaviEntry());
            $arrFinalItems = array();
            //build array of final items
            $intI = 0;
            foreach ($arrItems as $arrOneItem) {
                if ($arrOneItem[0] == "") {
                    $bitAdd = true;
                } else {
                    $bitAdd = Carrier::getInstance()->getObjRights()->validatePermissionString($arrOneItem[0], $objModule);
                }

                if ($bitAdd || $arrOneItem[1] == "") {
                    if ($arrOneItem[1] != "" || (!isset($arrFinalItems[$intI - 1]) || $arrFinalItems[$intI - 1] != "")) {
                        $arrFinalItems[] = $arrOneItem[1];
                        $intI++;
                    }
                }
            }

            //if the last one is a divider, remove it
            if ($arrFinalItems[count($arrFinalItems) - 1] == "") {
                unset($arrFinalItems[count($arrFinalItems) - 1]);
            }

            Carrier::getInstance()->getObjSession()->setSession($strKey, $arrFinalItems);
            return $arrFinalItems;
        }
        return array();
    }

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

    /**
     * Method which generates the global requirejs config
     *
     * @return string
     */
    public function generateRequireJsConfig()
    {
        $arrRequireConf = BootstrapCache::getInstance()->getCacheContent(BootstrapCache::CACHE_REQUIREJS);
        if (empty($arrRequireConf)) {
            //base config
            $arrRequireConf = array(
                "baseUrl" => '_webpath_',
                "paths" => array(),
                "shim" => array(),
            );

            foreach (Classloader::getInstance()->getArrModules() as $strFolder => $strModule) {
                $strJsonFile = Resourceloader::getInstance()->getAbsolutePathForModule($strModule)."/scripts/provides.json";
                if (is_file($strJsonFile)) {
                    $arrProvidesJs = json_decode(file_get_contents($strJsonFile), true);
                    $strBasePath = StringUtil::substring(Resourceloader::getInstance()->getWebPathForModule($strModule), 1)."/scripts/";


                    if (isset($arrProvidesJs["paths"]) && is_array($arrProvidesJs["paths"])) {
                        foreach ($arrProvidesJs["paths"] as $strUniqueName => $strPath) {
                            $arrRequireConf["paths"][$strUniqueName] = $strBasePath.$strPath;
                        }
                    }

                    if (isset($arrProvidesJs["shim"]) && is_array($arrProvidesJs["shim"])) {
                        foreach ($arrProvidesJs["shim"] as $strUniqueName => $strValue) {
                            $arrRequireConf["shim"][$strUniqueName] = $strValue;
                        }
                    }
                }
            }

            BootstrapCache::getInstance()->updateCache(BootstrapCache::CACHE_REQUIREJS, $arrRequireConf);
        }

        return json_encode($arrRequireConf);
    }
}
