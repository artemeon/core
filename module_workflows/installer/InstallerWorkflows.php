<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$					    *
********************************************************************************************************/

namespace Kajona\Workflows\Installer;

use Kajona\System\System\DbDatatypes;
use Kajona\System\System\Filesystem;
use Kajona\System\System\InstallerBase;
use Kajona\System\System\InstallerRemovableInterface;
use Kajona\System\System\OrmSchemamanager;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;
use Kajona\Workflows\System\WorkflowsHandler;
use Kajona\Workflows\System\WorkflowsWorkflow;


/**
 * Class providing an installer for the workflows module
 *
 * @package module_workflows
 * @moduleId _workflows_module_id_
 */
class InstallerWorkflows extends InstallerBase implements InstallerRemovableInterface {


    public function install() {
		$strReturn = "";
        $objManager = new OrmSchemamanager();
		//workflows workflow ---------------------------------------------------------------------
		$strReturn .= "Installing table workflows...\n";
        $objManager->createTable("Kajona\\Workflows\\System\\WorkflowsWorkflow");

        $strReturn .= "Installing table workflows_handler...\n";
        $objManager->createTable("Kajona\\Workflows\\System\\WorkflowsHandler");

        $arrFields = array();
        $arrFields["wfc_id"]                     = array("char20", false);
        $arrFields["wfc_start"]                  = array("long", false);
        $arrFields["wfc_end"]                    = array("long", true);
        if(!$this->objDB->createTable("agp_workflows_stat_wfc", $arrFields, array("wfc_id"), array("wfc_start")))
            $strReturn .= "An error occured! ...\n";

        $arrFields = array();
        $arrFields["wfh_id"]                     = array("char20", false);
        $arrFields["wfh_wfc"]                    = array("char20", false);
        $arrFields["wfh_start"]                  = array("long", false);
        $arrFields["wfh_end"]                    = array("long", true);
        $arrFields["wfh_class"]                  = array("char254", false);
        $arrFields["wfh_result"]                 = array("char254", true);
        if(!$this->objDB->createTable("agp_workflows_stat_wfh", $arrFields, array("wfh_id"), array('wfh_start', 'wfh_result')))
            $strReturn .= "An error occured! ...\n";

		//register the module
		$this->registerModule(
            "workflows",
            _workflows_module_id_,
            "WorkflowsPortal.php",
            "WorkflowsAdmin.php",
            $this->objMetadata->getStrVersion(),
            true
        );

        $strReturn .= "synchronizing list...\n";
        WorkflowsHandler::synchronizeHandlerList();

        $strReturn .= "Generating and adding trigger-authkey...\n";
        $this->registerConstant("_workflows_trigger_authkey_", generateSystemid(), SystemSetting::$int_TYPE_STRING, _workflows_module_id_);

		return $strReturn;

	}

    /**
     * Validates whether the current module/element is removable or not.
     * This is the place to trigger special validations and consistency checks going
     * beyond the common metadata-dependencies.
     *
     * @return bool
     */
    public function isRemovable() {
        return true;
    }

    /**
     * Removes the elements / modules handled by the current installer.
     * Use the reference param to add a human readable logging.
     *
     * @param string &$strReturn
     *
     * @return bool
     */
    public function remove(&$strReturn) {

        $strReturn .= "Removing system settings...\n";
        if(SystemSetting::getConfigByName("_workflows_trigger_authkey_") != null)
            SystemSetting::getConfigByName("_workflows_trigger_authkey_")->deleteObjectFromDatabase();

        /** @var WorkflowsWorkflow $objOneObject */
        foreach(WorkflowsWorkflow::getObjectListFiltered() as $objOneObject) {
            $strReturn .= "Deleting object '".$objOneObject->getStrDisplayName()."' ...\n";
            if(!$objOneObject->deleteObjectFromDatabase()) {
                $strReturn .= "Error deleting object, aborting.\n";
                return false;
            }
        }

        /** @var WorkflowsHandler $objOneObject */
        foreach(WorkflowsHandler::getObjectListFiltered() as $objOneObject) {
            $strReturn .= "Deleting object '".$objOneObject->getStrDisplayName()."' ...\n";
            if(!$objOneObject->deleteObjectFromDatabase()) {
                $strReturn .= "Error deleting object, aborting.\n";
                return false;
            }
        }

        //delete the module-node
        $strReturn .= "Deleting the module-registration...\n";
        $objModule = SystemModule::getModuleByName($this->objMetadata->getStrTitle(), true);
        if(!$objModule->deleteObjectFromDatabase()) {
            $strReturn .= "Error deleting module, aborting.\n";
            return false;
        }

        //delete the tables
        foreach(array("workflows_handler", "workflows") as $strOneTable) {
            $strReturn .= "Dropping table ".$strOneTable."...\n";
            if(!$this->objDB->_pQuery("DROP TABLE ".$this->objDB->encloseTableName($strOneTable)."", array())) {
                $strReturn .= "Error deleting table, aborting.\n";
                return false;
            }

        }

        return true;
    }


    public function update() {
	    $strReturn = "";
        //check installed version and to which version we can update
        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        $strReturn .= "Version found:\n\t Module: ".$arrModule["module_name"].", Version: ".$arrModule["module_version"]."\n\n";

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.2") {
            $strReturn .= $this->update_62_65();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.5") {
            $strReturn .= $this->update_65_651();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.5.1") {
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

    private function update_62_65() {
        $strReturn = "Adding new tables\n";

        $arrFields = array();
        $arrFields["wfc_id"]                     = array("char20", false);
        $arrFields["wfc_start"]                  = array("long", false);
        $arrFields["wfc_end"]                    = array("long", true);
        if(!$this->objDB->createTable("agp_workflows_stat_wfc", $arrFields, array("wfc_id"), array("wfc_start")))
            $strReturn .= "An error occured! ...\n";

        $arrFields = array();
        $arrFields["wfh_id"]                     = array("char20", false);
        $arrFields["wfh_wfc"]                    = array("char20", false);
        $arrFields["wfh_start"]                  = array("long", false);
        $arrFields["wfh_end"]                    = array("long", true);
        $arrFields["wfh_class"]                  = array("char254", false);
        $arrFields["wfh_result"]                 = array("char254", true);
        if(!$this->objDB->createTable("agp_workflows_stat_wfh", $arrFields, array("wfh_id"), array('wfh_start', 'wfh_result')))
            $strReturn .= "An error occured! ...\n";

        $strReturn .= "Updating module-versions...\n";
        $this->objDB->flushQueryCache();
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.5");

        return $strReturn;
    }

    private function update_65_651() {
        $strReturn = "Changing stats schema\n";
        $this->objDB->changeColumn("agp_agp_workflows_stat_wfc", "wfh_result", "wfh_result", DbDatatypes::STR_TYPE_CHAR254);

            $strReturn .= "An error occured! ...\n";

        $strReturn .= "Updating module-versions...\n";
        $this->objDB->flushQueryCache();
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.5.1");

        return $strReturn;
    }

}
