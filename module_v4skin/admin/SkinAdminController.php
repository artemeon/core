<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\V4skin\Admin;

use Kajona\System\Admin\AdminController;
use Kajona\System\Admin\AdminEvensimpler;
use Kajona\System\Admin\AdminHelper;
use Kajona\System\Admin\AdminInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Classloader;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;

/**
 * Backend Controller to handle various, general actions / callbacks
 *
 * @author sidler@mulchprod.de
 *
 * @module v4skin
 * @moduleId _v4skin_module_id_
 */
class SkinAdminController extends AdminEvensimpler implements AdminInterface
{
    /**
     * @param AdminController $objAdminModule
     * @permissions view
     * @return string
     */
    public function actionGetPathNavigation(AdminController $objAdminModule)
    {
        return Carrier::getInstance()->getObjToolkit("admin")->getPathNavigation($objAdminModule->getArrOutputNaviEntries());
    }

    /**
     * @param AdminController $objAdminModule
     * @permissions view
     * @return string
     */
    public function actionGetQuickHelp(AdminController $objAdminModule)
    {
        return $objAdminModule->getQuickHelp();
    }

    /**
     * @param $strContent
     * @permissions view
     * @return string
     * @throws \Kajona\System\System\Exception
     */
    public function actionGenerateMainTemplate($strContent)
    {
        $arrTemplate = ["content" => $strContent];

        $arrTemplate["login"] = $this->getOutputLogin();
        $arrTemplate["quickhelp"] = $this->getQuickHelp();

        $objAdminHelper = new AdminHelper();
        $arrTemplate["webpathTitle"] = urldecode(str_replace(["http://", "https://"], ["", ""], _webpath_));
        $arrTemplate["head"] = "<script type=\"text/javascript\">KAJONA_DEBUG = ".$this->objConfig->getDebug("debuglevel")."; KAJONA_WEBPATH = '"._webpath_."'; KAJONA_BROWSER_CACHEBUSTER = ".SystemSetting::getConfigValue("_system_browser_cachebuster_")."; KAJONA_LANGUAGE = '".Carrier::getInstance()->getObjSession()->getAdminLanguage()."';KAJONA_PHARMAP = ".json_encode(array_values(Classloader::getInstance()->getArrPharModules()))."; var require = {$objAdminHelper->generateRequireJsConfig()};</script>";

        $strTemplate = AdminskinHelper::getPathForSkin()."/main.tpl";
        return $this->objTemplate->fillTemplateFile($arrTemplate, $strTemplate);
    }

    /**
     * @param $strContent
     * @permissions view
     * @return string
     * @throws \Kajona\System\System\Exception
     */
    public function actionGenerateFolderviewTemplate($strContent)
    {
        return $this->renderTemplate("/main.tpl", $strContent);
    }

    /**
     * @param $strContent
     * @permissions view
     * @return string
     * @throws \Kajona\System\System\Exception
     */
    public function actionGenerateLoginTemplate($strContent)
    {
        return $this->renderTemplate("/login.tpl", $strContent);
    }

    /**
     * @param $strContent
     * @permissions view
     * @return string
     * @throws \Kajona\System\System\Exception
     */
    public function actionGenerateAnonymousTemplate($strContent)
    {
        return $this->renderTemplate("/anonymous.tpl", $strContent);
    }

    /**
     * Internal helper to render the backend template
     * @param $strTemplate
     * @param $strContent
     * @return string
     * @throws \Kajona\System\System\Exception
     */
    private function renderTemplate($strTemplate, $strContent)
    {
        $arrTemplate = ["content" => $strContent];


        $objAdminHelper = new AdminHelper();
        $arrTemplate["webpathTitle"] = urldecode(str_replace(["http://", "https://"], ["", ""], _webpath_));
        $arrTemplate["head"] = "<script type=\"text/javascript\">KAJONA_DEBUG = ".$this->objConfig->getDebug("debuglevel")."; KAJONA_WEBPATH = '"._webpath_."'; KAJONA_BROWSER_CACHEBUSTER = ".SystemSetting::getConfigValue("_system_browser_cachebuster_")."; KAJONA_LANGUAGE = '".Carrier::getInstance()->getObjSession()->getAdminLanguage()."';KAJONA_PHARMAP = ".json_encode(array_values(Classloader::getInstance()->getArrPharModules()))."; var require = {$objAdminHelper->generateRequireJsConfig()};</script>";

        return $this->objTemplate->fillTemplateFile($arrTemplate, $strTemplate);
    }

    /**
     * @permissions view
     * @responseType html
     */
    protected function actionGetBackendNavi()
    {
        return $this->objToolkit->getAdminSitemap();
    }

    /**
     * @permissions view
     * @responseType html
     */
    protected function actionGetLanguageswitch()
    {
        return (SystemModule::getModuleByName("languages") != null ? "<span>".SystemModule::getModuleByName("languages")->getAdminInstanceOfConcreteModule()->getLanguageSwitch()."</span>" : "<span/>");
    }

}
