<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\V4skin\Admin;

use Kajona\System\Admin\AdminController;
use Kajona\System\Admin\AdminEvensimpler;
use Kajona\System\Admin\AdminInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Classloader;
use Kajona\System\System\Session;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserUser;

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
     * @inject system_template_engine
     * @var \Twig_Environment
     */
    protected $templateEngine;

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
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     * @permissions view
     */
    public function actionGenerateMainTemplate($strContent)
    {
        $arrTemplate = ["content" => $strContent];

        $arrTemplate["login"] = $this->getOutputLogin();
        $arrTemplate["quickhelp"] = $this->getQuickHelp();
        $arrTemplate["webpathTitle"] = urldecode(str_replace(["http://", "https://"], ["", ""], _webpath_));
        $arrTemplate["head"] = $this->getJsHead();

        return $this->templateEngine->render("core/module_v4skin/admin/skins/kajona_v4/main.twig", $arrTemplate);
    }

    /**
     * @param $strContent
     * @permissions view
     * @return string
     * @throws \Kajona\System\System\Exception
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function actionGenerateFolderviewTemplate($strContent)
    {
        return $this->renderTemplate("core/module_v4skin/admin/skins/kajona_v4/main.twig", $strContent);
    }

    /**
     * @param $strContent
     * @permissions view
     * @return string
     * @throws \Kajona\System\System\Exception
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function actionGenerateLoginTemplate($strContent)
    {
        return $this->renderTemplate("core/module_v4skin/admin/skins/kajona_v4/login.twig", $strContent);
    }

    /**
     * @param $strContent
     * @permissions view
     * @return string
     * @throws \Kajona\System\System\Exception
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function actionGenerateAnonymousTemplate($strContent)
    {
        return $this->renderTemplate("core/module_v4skin/admin/skins/kajona_v4/anonymous.twig", $strContent);
    }

    /**
     * Internal helper to render the backend template
     * @param $strTemplate
     * @param $strContent
     * @return string
     * @throws \Kajona\System\System\Exception
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function renderTemplate($strTemplate, $strContent)
    {
        $arrTemplate = ["content" => $strContent];

        $arrTemplate["webpathTitle"] = urldecode(str_replace(["http://", "https://"], ["", ""], _webpath_));
        $arrTemplate["head"] = $this->getJsHead();

        return $this->templateEngine->render($strTemplate, $arrTemplate);
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

    /**
     * @return string
     * @throws \Kajona\System\System\Exception
     */
    private function getJsHead()
    {
        $user = Session::getInstance()->getUser();

        $values = [
            'KAJONA_DEBUG' => $this->objConfig->getDebug("debuglevel"),
            'KAJONA_WEBPATH' => _webpath_,
            'KAJONA_BROWSER_CACHEBUSTER' => SystemSetting::getConfigValue("_system_browser_cachebuster_"),
            'KAJONA_LANGUAGE' => Carrier::getInstance()->getObjSession()->getAdminLanguage(),
            'KAJONA_PHARMAP' => array_values(Classloader::getInstance()->getArrPharModules()),
            'KAJONA_ACCESS_TOKEN' => $user instanceof UserUser ? $user->getStrAccessToken() : null,
            'KAJONA_USER_ITEMS_PAGE' => $user instanceof UserUser ? $user->getIntItemsPerPage() : null,
        ];

        $parts = [];
        foreach ($values as $name => $value) {
            $parts[] = $name . ' = ' . json_encode($value) . ';';
        }

        return "<script type=\"text/javascript\">" . implode("\n", $parts) . "</script>";
    }
}
