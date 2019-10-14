<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\Api\System\Http\JsonResponse;
use Kajona\System\System\Carrier;
use Kajona\System\System\Link;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemModule;
use Kajona\System\System\MenuItem;

/**
 * MenuApiController
 *
 * @author laura.albersmann@artemeon.de
 * @since 7.2
 */
class MenuApiController implements ApiControllerInterface
{

    /**
     * Returns the menu
     *
     * @api
     * @method GET
     * @path /v1/system/menu
     * @authorization usertoken
     */
    public function getMenu(): JsonResponse
    {
        $arrToggleEntries = [];
        foreach (SystemAspect::getActiveObjectList() as $objOneAspect) {
            if (!$objOneAspect->rightView()) {
                continue;
            }

            $arrModules = SystemModule::getModulesInNaviAsArray($objOneAspect->getSystemid());

            /** @var $arrNaviInstances SystemModule[] */
            $arrNaviInstances = [];
            foreach ($arrModules as $arrModule) {
                $objModule = SystemModule::getModuleBySystemid($arrModule["module_id"]);
                if ($objModule->rightView()) {
                    $arrNaviInstances[] = $objModule;
                }
            }

            $arrMenuHeader = [];
            $arrMenuBody = [];
            $arrCombined = [
                "messaging" => "fa-envelope",
                "dashboard" => "fa-home",
                "tags" => "fa-tags",
            ];

            foreach ($arrNaviInstances as $objOneInstance) {
                $arrActions = self::getModuleActionNaviHelper($objOneInstance);

                $arrModuleLevel = [
                    "module" => Link::getLinkAdmin($objOneInstance->getStrName(), "", "", Carrier::getInstance()->getObjLang()->getLang("modul_titel", $objOneInstance->getStrName())),
                    "actions" => $arrActions,
                    "systemid" => $objOneInstance->getSystemid(),
                    "moduleTitle" => $objOneInstance->getStrName(),
                    "moduleName" => Carrier::getInstance()->getObjLang()->getLang("modul_titel", $objOneInstance->getStrName()),
                    "moduleHref" => Link::getLinkAdminHref($objOneInstance->getStrName(), ""),
                    "aspectId" => $objOneAspect->getSystemid(),
                ];

                if (array_key_exists($objOneInstance->getStrName(), $arrCombined)) {
                    $arrModuleLevel["faicon"] = $arrCombined[$objOneInstance->getStrName()];
                    $arrMenuHeader[] = ["module" => $arrModuleLevel];
                } else {
                    $arrMenuBody[] = ["module" => $arrModuleLevel];
                }
            }

            $arrToggleEntries[] = ["Aspect_name" => $objOneAspect->getStrDisplayName(),"header" => $arrMenuHeader, "body" => $arrMenuBody, "onclick" => "ModuleNavigation.switchAspect('{$objOneAspect->getSystemid()}'); return false;"];
        }

        return new JsonResponse([
            "aspects" => $arrToggleEntries
        ]);
    }

    /**
     * Fetches the list of actions for a single module, saved to the session for performance reasons
     *
     * @param SystemModule $objModule
     *
     * @return array
     * @throws \Kajona\System\System\Exception
     *
     */
    private function getModuleActionNaviHelper(SystemModule $objModule)
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
                        if ($arrOneItem[1] instanceof MenuItem) {
                            $arrFinalItems[] = $arrOneItem[1]->toArray();
                        } else {
                            $arrSplitOneItem = splitUpLink($arrOneItem[1]);
                            $arrFinalItems[] = $arrSplitOneItem;
                        }
                        $intI++;
                    }
                }
            }

            //if the last one is a divider, remove it
            if ($arrFinalItems[count($arrFinalItems) - 1]["name"] == "" && $arrFinalItems[count($arrFinalItems) - 1]["href"] == "") {
                unset($arrFinalItems[count($arrFinalItems) - 1]);
            }

            Carrier::getInstance()->getObjSession()->setSession($strKey, $arrFinalItems);
            return $arrFinalItems;
        }
        return array();
    }
}
