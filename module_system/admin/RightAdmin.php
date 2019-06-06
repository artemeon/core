<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *   $Id$                                *
 ********************************************************************************************************/

namespace Kajona\System\Admin;

use Kajona\System\System\Exception;
use Kajona\System\System\Link;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Rights;
use Kajona\System\System\Root;
use Kajona\System\System\SystemCommon;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;
use Kajona\V4skin\View\Components\Rights\Rights as RightComponent;

/**
 * This class handles the backend-part of permission-management
 *
 * @package module_system
 * @author sidler@mulchprod.de
 *
 * @module right
 * @moduleId _system_modul_id_
 */
class RightAdmin extends AdminController implements AdminInterface
{
    /**
     * @inject system_rights
     * @var \Kajona\System\System\Rights
     */
    protected $rights;

    /**
     * Constructor
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->setStrLangBase("system");

        if ($this->getAction() === "list") {
            $this->setAction("change");
        }
    }

    /**
     * @inheritdoc
     */
    protected function getOutputModuleTitle()
    {
        return $this->getLang("moduleRightsTitle");
    }

    /**
     * Returns a form to modify the rights
     *
     * @return string
     * @permissions right
     * @throws Exception
     */
    protected function actionChange()
    {
        $strReturn = "";
        $strSystemID = $this->getParam("systemid");
        $objTargetRecord = null;

        if ($strSystemID == "") {
            $strSystemID = "0";
        }

        //Determine the systemid
        if ($strSystemID != "") {
            $objTargetRecord = Objectfactory::getInstance()->getObject($strSystemID);
        }
        //Edit a module?
        if ($this->getParam("changemodule") != "") {
            $objTargetRecord = SystemModule::getModuleByName($this->getParam("changemodule"));
            $strSystemID = $objTargetRecord->getSystemid();
        }

        if ($objTargetRecord == null) {
            return $this->getLang("commons_error_permissions");
        }

        if ($objTargetRecord->rightRight()) {
            //Get Rights
            $arrRights = $this->rights->getArrayRights($objTargetRecord->getSystemid());

            //Followed by the form
            $strReturn .= $this->objToolkit->formHeader(Link::getLinkAdminHref($this->getArrModule("modul"), "saverights"), "rightsForm", "", "Permissions.submitForm();return false;");
            $strReturn .= $this->objToolkit->formInputUserSelector("group_add", $this->getLang("permissons_add_group"), "", "", false, true);
            $strReturn .= "<div id=\"rightsContainer\">" . $this->actionLoadRights() . "</div>";
            $strReturn .= $this->objToolkit->formInputCheckbox("inherit", $this->getLang("titel_erben"), boolval($arrRights["inherit"]));
            $strReturn .= $this->objToolkit->formInputHidden("systemid", $strSystemID);

            //Close the form
            $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
            $strReturn .= $this->objToolkit->formClose();

            $strReturn .= "<script type=\"text/javascript\">
            // add new group
            $('#group_add_id').on('change', function(){
                Permissions.addGroup($('#group_add_id').val());
            });

            // toggle inherit checkbox
            $('#inherit').on('change', Permissions.toggleInherit);
            Permissions.toggleInherit();

            // ignore enter key press
            $(document).ready(function() {
                $(window).keydown(function(event){
                    if (event.keyCode === 13) {
                        event.preventDefault();
                        return false;
                    }
                });
            });
                </script>";
        } else {
            $strReturn .= $this->getLang("commons_error_permissions");
        }
        return $strReturn;
    }

    /**
     * Renders a right table containing the rights of the provided system id
     *
     * @permissions right
     * @responseType html
     * @return string
     * @throws Exception
     */
    protected function actionLoadRights()
    {
        $systemid = $this->getSystemid();
        if ($systemid == "" && $this->objSession->isSuperAdmin()) {
            $systemid = "0";
        }

        $object = $this->objFactory->getObject($systemid);

        if (!$object instanceof Root) {
            throw new \InvalidArgumentException("Invalid systemid");
        }

        return (new RightComponent($object))->renderComponent();
    }

    /**
     * Saves the rights passed by form
     *
     * @throws Exception
     * @return array
     * @permissions right
     * @responseType json
     */
    protected function actionSaveRights()
    {
        $body = file_get_contents("php://input");
        $arrRequest = json_decode($body);

        //Collecting & sorting the passed values
        $strSystemid = $this->getSystemid();

        if ($this->getParam("systemid") == "0") {
            $objTarget = new SystemCommon("0");
            $objTarget->setStrSystemid("0");
            $strSystemid = "0";
        } else {
            $objTarget = Objectfactory::getInstance()->getObject($this->getSystemid());
        }

        //Special case: The root-record.
        if (!$objTarget->rightRight()) {
            return ["message" => $this->getLang("commons_error_permissions"), "type" => "error"];
        }

        //Inheritance?
        if ($arrRequest->bitInherited) {
            $intInherit = 1;
        } else {
            $intInherit = 0;
        }

        //Modified RootRecord? Here Inheritance is NOT allowed!
        if ($strSystemid == "0") {
            $intInherit = 0;
        }

        $strAdminsGroupId = SystemSetting::getConfigValue("_admins_group_id_");

        $permissionRow = [
            Rights::$STR_RIGHT_VIEW => [$strAdminsGroupId],
            Rights::$STR_RIGHT_EDIT => [$strAdminsGroupId],
            Rights::$STR_RIGHT_DELETE => [$strAdminsGroupId],
            Rights::$STR_RIGHT_RIGHT => [$strAdminsGroupId],
            Rights::$STR_RIGHT_RIGHT1 => [$strAdminsGroupId],
            Rights::$STR_RIGHT_RIGHT2 => [$strAdminsGroupId],
            Rights::$STR_RIGHT_RIGHT3 => [$strAdminsGroupId],
            Rights::$STR_RIGHT_RIGHT4 => [$strAdminsGroupId],
            Rights::$STR_RIGHT_RIGHT5 => [$strAdminsGroupId],
            Rights::$STR_RIGHT_CHANGELOG => [$strAdminsGroupId],
        ];

        foreach ($arrRequest->arrConfigs as $strOneCfg) {
            $arrRow = explode(",", $strOneCfg);
            $rightName = $arrRow[0];
            $groupId = $arrRow[1];

            if ($groupId == $strAdminsGroupId) {
                continue;
            }

            if (isset($permissionRow[$rightName])) {
                $permissionRow[$rightName][] = $groupId;
            }
        }

        //Pass to right-class
        $permissionRow[Rights::$STR_RIGHT_INHERIT] = $intInherit;
        $rights = $this->rights->convertSystemidArrayToShortIdString($permissionRow);

        if ($this->rights->setRights($rights, $strSystemid)) {
            $strReturn = ["message" => $this->getLang("save_rights_success"), "type" => "success"];
        } else {
            $strReturn = ["message" => $this->getLang("save_rights_error"), "type" => "error"];
        }

        return $strReturn;
    }
}
