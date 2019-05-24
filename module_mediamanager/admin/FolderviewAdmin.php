<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 ********************************************************************************************************/

namespace Kajona\Mediamanager\Admin;

use Kajona\System\Admin\AdminController;
use Kajona\System\Admin\AdminInterface;
use Kajona\System\System\Link;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;

/**
 * This class provides a list-view of the folders created in the database / filesystem.
 * Since Kajona 3.4.1 this class is deprecated. All methods have been moved to the appropriate source-modules.
 * It only remains as a switch between different browsers.
 *
 * @package module_mediamanager
 * @author sidler@mulchprod.de
 * @deprecated
 *
 * @module mediamanager
 * @moduleId _mediamanager_module_id_
 */
class FolderviewAdmin extends AdminController implements AdminInterface
{

    /**
     * Constructor, doing nothing but a few inits
     */
    public function __construct()
    {
        parent::__construct();
        $this->setStrLangBase("mediamanager");
    }

    /**
     * @return string
     */
    protected function getOutputModuleTitle()
    {
        return $this->getLang("moduleFolderviewTitle");
    }

    /**
     * @return string
     * @autoTestable
     * @permissions view
     */
    protected function actionBrowserChooser()
    {
        $strReturn = "";

        if ($this->getParam("CKEditorFuncNum") != "") {
            $strReturn .= "<script type=\"text/javascript\">window.opener.Folderview.selectCallbackCKEditorFuncNum = " . (int) $this->getParam("CKEditorFuncNum") . ";</script>";
        }

        $strReturn .= $this->objToolkit->listHeader();

        $strRepoId = SystemSetting::getConfigValue("_mediamanager_default_filesrepoid_");
        if (validateSystemid($strRepoId) && SystemModule::getModuleByName("mediamanager") !== null && Objectfactory::getInstance()->getObject($strRepoId) !== null) {
            $strAction = $this->objToolkit->listButton(
                Link::getLinkAdmin(
                    "mediamanager",
                    "folderContentFolderviewMode",
                    "&systemid=" . $strRepoId . "&form_element=" . $this->getParam("form_element") . "&bit_link=1&download=" . $this->getParam("download"),
                    $this->getLang("wysiwygFilesBrowser"),
                    $this->getLang("wysiwygFilesBrowser"),
                    "icon_folderActionOpen"
                )
            );
            $strReturn .= $this->objToolkit->genericAdminList(generateSystemid(), $this->getLang("wysiwygFilesBrowser"), "", $strAction);
        }

        $strRepoId = SystemSetting::getConfigValue("_mediamanager_default_imagesrepoid_");
        if (validateSystemid($strRepoId) && SystemModule::getModuleByName("mediamanager") !== null && Objectfactory::getInstance()->getObject($strRepoId) !== null) {
            $strAction = $this->objToolkit->listButton(
                Link::getLinkAdmin(
                    "mediamanager",
                    "folderContentFolderviewMode",
                    "&systemid=" . $strRepoId . "&form_element=" . $this->getParam("form_element") . "&bit_link=1&download=" . $this->getParam("download"),
                    $this->getLang("wysiwygImagesBrowser"),
                    $this->getLang("wysiwygImagesBrowser"),
                    "icon_folderActionOpen"
                )
            );
            $strReturn .= $this->objToolkit->genericAdminList(generateSystemid(), $this->getLang("wysiwygImagesBrowser"), "", $strAction);
        }

        if (SystemModule::getModuleByName("mediamanager") !== null) {
            $strAction = $this->objToolkit->listButton(
                Link::getLinkAdmin(
                    "mediamanager",
                    "folderContentFolderviewMode",
                    "&form_element=" . $this->getParam("form_element") . "&bit_link=1&download=" . $this->getParam("download"),
                    $this->getLang("wysiwygRepoBrowser"),
                    $this->getLang("wysiwygRepoBrowser"),
                    "icon_folderActionOpen"
                )
            );
            $strReturn .= $this->objToolkit->genericAdminList(generateSystemid(), $this->getLang("wysiwygRepoBrowser"), "", $strAction);
        }

        $strReturn .= $this->objToolkit->listFooter();
        $strReturn .= "<script type='text/javascript'>Lists.initRowClick();</script>";
        return $strReturn;
    }

}
