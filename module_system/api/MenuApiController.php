<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\Api\System\Http\JsonResponse;
use Kajona\System\Admin\AdminHelper;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Link;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemModule;
use PSX\Http\Environment\HttpContextInterface;

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
     * @authorization anonymous
     */
    public function getMenu(): JsonResponse
    {


        $strAllModules = "";

        $arrToggleEntries = [];
        $i = 1;
        foreach (SystemAspect::getActiveObjectList() as $objOneAspect) {
            if (!$objOneAspect->rightView()) {
                continue;
            }
            $aspectModule = [];
            echo "testi:".$i;
            $i++;

//            // Aspecte mit click funktion
//            $arrToggleEntries[] = ["name" => $objOneAspect->getStrDisplayName(), "onclick" => "ModuleNavigation.switchAspect('{$objOneAspect->getSystemid()}'); return false;"];


            $arrModules = SystemModule::getModulesInNaviAsArray($objOneAspect->getSystemid());

            /** @var $arrNaviInstances SystemModule[] */
            $arrNaviInstances = [];
            foreach ($arrModules as $arrModule) {
                $objModule = SystemModule::getModuleBySystemid($arrModule["module_id"]);
                if ($objModule->rightView()) {
                    $arrNaviInstances[] = $objModule;
                }
            }


            $strCombinedHeader = "";
            $strCombinedBody = "";

            $arrCombined = [
                "messaging" => "fa-envelope",
                "dashboard" => "fa-home",
                "tags" => "fa-tags",
            ];

            $strModules = "";
            foreach ($arrNaviInstances as $objOneInstance) {
                $arrActions = AdminHelper::getModuleActionNaviHelper($objOneInstance);
//                echo "arrActions";
//                echo '<pre>'; print_r($arrActions); echo '</pre>';

                $strActions = "";
//                foreach ($arrActions as $strOneAction) {
//                    if (trim($strOneAction) != "") {
//                        $arrActionEntries = [
//                            "action" => $strOneAction,
//                        ];
////                        $strActions .= $this->objTemplate->fillTemplateFile($arrActionEntries, "/admin/skins/kajona_v4/elements.tpl", "sitemap_action_entry");
//                    } else {
////                        $strActions .= $this->objTemplate->fillTemplateFile([], "/admin/skins/kajona_v4/elements.tpl", "sitemap_divider_entry");
//                    }
//                }

                $arrModuleLevel = [
                    "module" => Link::getLinkAdmin($objOneInstance->getStrName(), "", "", Carrier::getInstance()->getObjLang()->getLang("modul_titel", $objOneInstance->getStrName())),
                    "actions" => $arrActions,
                    "systemid" => $objOneInstance->getSystemid(),
                    "moduleTitle" => $objOneInstance->getStrName(),
                    "moduleName" => Carrier::getInstance()->getObjLang()->getLang("modul_titel", $objOneInstance->getStrName()),
                    "moduleHref" => Link::getLinkAdminHref($objOneInstance->getStrName(), ""),
                    "aspectId" => $objOneAspect->getSystemid(),
                ];
//                echo "arrModuleLevel";
//                echo '<pre>'; print_r($arrModuleLevel); echo '</pre>';
//                if (array_key_exists($objOneInstance->getStrName(), $arrCombined)) {
//                    $arrModuleLevel["faicon"] = $arrCombined[$objOneInstance->getStrName()];
//                    $strCombinedHeader .= $this->objTemplate->fillTemplateFile($arrModuleLevel, "/admin/skins/kajona_v4/elements.tpl", "sitemap_combined_entry_header");
//                    $strCombinedBody .= $this->objTemplate->fillTemplateFile($arrModuleLevel, "/admin/skins/kajona_v4/elements.tpl", "sitemap_combined_entry_body");
//                } else {
//                    $strModules .= $this->objTemplate->fillTemplateFile($arrModuleLevel, "/admin/skins/kajona_v4/elements.tpl", "sitemap_module_wrapper");
//                }
            }
            // Aspecte mit click funktion
            $arrToggleEntries[] = ["name" => $objOneAspect->getStrDisplayName(), "module" =>  $arrModuleLevel, "onclick" => "ModuleNavigation.switchAspect('{$objOneAspect->getSystemid()}'); return false;"];
            echo "aspecte";
            echo '<pre>'; print_r($arrModuleLevel); echo '</pre>';

//            if ($strCombinedHeader != "") {
//                $strModules = $this->objTemplate->fillTemplateFile(
//                        ["combined_header" => $strCombinedHeader, "combined_body" => $strCombinedBody],
//                        "/admin/skins/kajona_v4/elements.tpl",
//                        "sitemap_combined_entry_wrapper"
//                    ) . $strModules;
//            }

//            $strAllModules .= $this->objTemplate->fillTemplateFile(
//                array("aspectContent" => $strModules, "aspectId" => $objOneAspect->getSystemid(), "class" => ($strAllModules == "" ? "" : "hidden")),
//                "/admin/skins/kajona_v4/elements.tpl",
//                "sitemap_aspect_wrapper"
//            );

        }
//        echo 'arrToggleEntries';
//        echo '<pre>'; print_r($arrToggleEntries); echo '</pre>';

//        $strToggleDD = "";
//        if (!empty($arrToggleEntries)) {
//            $strToggle = $this->registerMenu("mainNav", $arrToggleEntries);
//            $strToggleDD =
//                "<span class='dropdown pull-left'><a href='#' data-toggle='dropdown' role='button'>" . AdminskinHelper::getAdminImage("icon_submenu") . "</a>{$strToggle}</span>"
//            ;
//        }

//         return $this->objTemplate->fillTemplateFile(array("level" => $strAllModules, "aspectToggle" => $strToggleDD), "/admin/skins/kajona_v4/elements.tpl", "sitemap_wrapper");
        return new JsonResponse([
        ]);
    }


}

