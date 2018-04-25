<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                            *
********************************************************************************************************/

namespace Kajona\Ldap\Installer;

use Kajona\System\System\Config;
use Kajona\System\System\DbDatatypes;
use Kajona\System\System\InstallerBase;
use Kajona\System\System\InstallerRemovableInterface;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemModule;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;
use Kajona\Workflows\System\WorkflowsHandler;
use Kajona\Workflows\System\WorkflowsWorkflow;

/**
 * Class providing an installer for the monita module
 *
 * @package module_ldap
 * @author sidler@mulchprod.de
 * @moduleId _ldap_module_id_
 */
class InstallerLdap extends InstallerBase implements InstallerRemovableInterface
{

    public function install()
    {
        $strReturn = "";

        $strReturn .= "Installing table group_ldap...\n";
        $arrFields = array();
        $arrFields["group_ldap_id"] = array(DbDatatypes::STR_TYPE_CHAR20, false);
        $arrFields["group_ldap_dn"] = array(DbDatatypes::STR_TYPE_TEXT, true);
        $arrFields["group_ldap_cfg"] = array(DbDatatypes::STR_TYPE_INT, true);

        if (!$this->objDB->createTable("agp_user_group_ldap", $arrFields, array("group_ldap_id"))) {
            $strReturn .= "An error occurred! ...\n";
        }

        $strReturn .= "Installing table user_ldap...\n";
        $arrFields = array();
        $arrFields["user_ldap_id"] = array(DbDatatypes::STR_TYPE_CHAR20, false);
        $arrFields["user_ldap_email"] = array(DbDatatypes::STR_TYPE_CHAR254, true);
        $arrFields["user_ldap_familyname"] = array(DbDatatypes::STR_TYPE_CHAR254, true);
        $arrFields["user_ldap_givenname"] = array(DbDatatypes::STR_TYPE_CHAR254, true);
        $arrFields["user_ldap_dn"] = array(DbDatatypes::STR_TYPE_TEXT, true);
        $arrFields["user_ldap_cfg"] = array(DbDatatypes::STR_TYPE_INT, true);

        if (!$this->objDB->createTable("agp_user_ldap", $arrFields, array("user_ldap_id"))) {
            $strReturn .= "An error occurred! ...\n";
        }


        //register the module
        $this->registerModule("ldap", _ldap_module_id_, "", "", $this->objMetadata->getStrVersion(), false);
        return $strReturn;

    }

    /**
     * Validates whether the current module/element is removable or not.
     * This is the place to trigger special validations and consistency checks going
     * beyond the common metadata-dependencies.
     *
     * @return bool
     */
    public function isRemovable()
    {
        return StringUtil::indexOf(Config::getInstance()->getConfig("loginproviders"), "ldap") === false;
    }

    /**
     * Removes the elements / modules handled by the current installer.
     * Use the reference param to add a human readable logging.
     *
     * @param string &$strReturn
     *
     * @return bool
     */
    public function remove(&$strReturn)
    {

        //remove the workflow
        if (SystemModule::getModuleByName("workflows") !== null) {
            foreach (WorkflowsWorkflow::getWorkflowsForClass("Kajona\\Ldap\\System\\Workflows\\WorkflowLdapSync") as $objOneWorkflow) {
                if (!$objOneWorkflow->deleteObjectFromDatabase()) {
                    $strReturn .= "Error deleting workflow, aborting.\n";
                    return false;
                }
            }

            $objHandler = WorkflowsHandler::getHandlerByClass("Kajona\\Ldap\\System\\Workflows\\WorkflowLdapSync");
            if (!$objHandler->deleteObjectFromDatabase()) {
                $strReturn .= "Error deleting workflow handler, aborting.\n";
                return false;
            }
        }

        //fetch associated users
        foreach ($this->objDB->getPArray("SELECT * FROM agp_user_ldap", array()) as $arrOneRow) {
            $objOneUser = new UserUser($arrOneRow["user_ldap_id"]);
            echo "Deleting ldap user ".$objOneUser->getStrDisplayName()."...\n";
            $objOneUser->deleteObjectFromDatabase();
        }

        //fetch associated groups
        foreach ($this->objDB->getPArray("SELECT * FROM agp_user_group_ldap", array()) as $arrOneRow) {
            $objOneUser = new UserGroup($arrOneRow["group_ldap_id"]);
            echo "Deleting ldap group ".$objOneUser->getStrDisplayName()."...\n";
            $objOneUser->deleteObjectFromDatabase();
        }

        //delete the module-node
        $strReturn .= "Deleting the module-registration...\n";
        $objModule = SystemModule::getModuleByName($this->objMetadata->getStrTitle(), true);
        if (!$objModule->deleteObjectFromDatabase()) {
            $strReturn .= "Error deleting module, aborting.\n";
            return false;
        }

        //delete the tables
        foreach (array("user_group_ldap", "user_ldap") as $strOneTable) {
            $strReturn .= "Dropping table ".$strOneTable."...\n";
            if (!$this->objDB->_pQuery("DROP TABLE ".$this->objDB->encloseTableName($strOneTable)."", array())) {
                $strReturn .= "Error deleting table, aborting.\n";
                return false;
            }

        }

        return true;
    }


    public function update()
    {
        $strReturn = "";
        //check installed version and to which version we can update
        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        $strReturn .= "Version found:\n\t Module: ".$arrModule["module_name"].", Version: ".$arrModule["module_version"]."\n\n";

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if ($arrModule["module_version"] == "6.2") {
            $strReturn .= "Updating to 6.5...\n";
            $this->updateModuleVersion("ldap", "6.5");
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.5") {
            $strReturn .= "Updating to 6.6...\n";
            $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.6");
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.6") {
            $strReturn .= "Updating to 7.0...\n";
            $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.0");
        }

        return $strReturn."\n\n";
    }

}
