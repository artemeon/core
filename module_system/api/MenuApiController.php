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
        $toggleEntries = [];
        foreach (SystemAspect::getActiveObjectList() as $oneAspect) {
            if (!$oneAspect->rightView()) {
                continue;
            }

            $modules = SystemModule::getModulesInNaviAsArray($oneAspect->getSystemid());

            /** @var $naviInstances SystemModule[] */
            $naviInstances = [];
            foreach ($modules as $module) {
                $objModule = SystemModule::getModuleBySystemid($module["module_id"]);
                if ($objModule->rightView()) {
                    $naviInstances[] = $objModule;
                }
            }

            $menuHeader = [];
            $menuBody = [];
            $combined = [
                "messaging" => "fa-envelope",
                "dashboard" => "fa-home",
                "tags" => "fa-tags",
            ];

            foreach ($naviInstances as $oneInstance) {
                $actions = $this->getModuleActionNaviHelper($oneInstance);

                $moduleLevel = [
                    "module" => Link::getLinkAdmin($oneInstance->getStrName(), "", "", Carrier::getInstance()->getObjLang()->getLang("modul_titel", $oneInstance->getStrName())),
                    "actions" => $actions,
                    "systemid" => $oneInstance->getSystemid(),
                    "moduleTitle" => $oneInstance->getStrName(),
                    "moduleName" => Carrier::getInstance()->getObjLang()->getLang("modul_titel", $oneInstance->getStrName()),
                    "moduleHref" => Link::getLinkAdminHref($oneInstance->getStrName(), ""),
                    "aspectId" => $oneAspect->getSystemid(),
                ];

                if (array_key_exists($oneInstance->getStrName(), $combined)) {
                    $moduleLevel["faicon"] = $combined[$oneInstance->getStrName()];
                    $menuHeader[] = ["module" => $moduleLevel];
                } else {
                    $menuBody[] = ["module" => $moduleLevel];
                }
            }

            $toggleEntries[] = ["Aspect_name" => $oneAspect->getStrDisplayName(),"header" => $menuHeader, "body" => $menuBody, "onclick" => "ModuleNavigation.switchAspect('{$oneAspect->getSystemid()}'); return false;"];
        }

        return new JsonResponse([
            "aspects" => $toggleEntries
        ]);
    }

    /**
     * Fetches the list of actions for a single module, saved to the session for performance reasons
     *
     * @param SystemModule $module
     *
     * @return array
     * @throws \Kajona\System\System\Exception
     *
     */
    private function getModuleActionNaviHelper(SystemModule $module)
    {
        if (Carrier::getInstance()->getObjSession()->isLoggedin()) {
            $key = __CLASS__."adminNaviEntries".$module->getSystemid().SystemAspect::getCurrentAspectId();

            $finalItems = Carrier::getInstance()->getObjSession()->getSession($key);
            if ($finalItems !== false) {
                return $finalItems;
            }

            $adminInstance = $module->getAdminInstanceOfConcreteModule();
            if ($adminInstance == null) {
                return array();
            }

            $items = $adminInstance->getOutputModuleNavi();
            $items = array_merge($items, $adminInstance->getModuleRightNaviEntry());
            $finalItems = array();

            //build array of final items
            $i = 0;
            foreach ($items as $oneItem) {
                if ($oneItem instanceof MenuItem) {
                    if ($oneItem->getRight() == "") {
                        $add = true;
                    } else {
                        $add = Carrier::getInstance()->getObjRights()->validatePermissionString($oneItem->getRight(), $module);
                    }

                    if ($add || $oneItem->getHref() == "") {
                        if ($oneItem->getHref() != "" || (!isset($finalItems[$i - 1]) || $finalItems[$i - 1] != "")) {
                            $finalItems[] = $oneItem->toArray();
                            $i++;
                        }
                    }
                } else if (is_array($oneItem)) {
                    if ($oneItem[0] == "") {
                        $add = true;
                    } else {
                        $add = Carrier::getInstance()->getObjRights()->validatePermissionString($oneItem[0], $module);
                    }

                    if ($add || $oneItem[1] == "") {
                        if ($oneItem[1] != "" || (!isset($finalItems[$i - 1]) || $finalItems[$i - 1] != "")) {
                            $splitOneItem = splitUpLink($oneItem[1]);
                            $finalItems[] = $splitOneItem;
                            $i++;
                        }
                    }
                }
            }

            //if the last one is a divider, remove it
            if ($finalItems[count($finalItems) - 1]["name"] == "" && $finalItems[count($finalItems) - 1]["href"] == "") {
                unset($finalItems[count($finalItems) - 1]);
            }

            Carrier::getInstance()->getObjSession()->setSession($key, $finalItems);
            return $finalItems;
        }
        return array();
    }
}
