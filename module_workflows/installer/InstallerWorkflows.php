<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$					    *
********************************************************************************************************/

namespace Kajona\Workflows\Installer;

use Kajona\System\Installer\InstallerSystem;
use Kajona\System\System\Database;
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
        if(!$this->objDB->createTable("agp_workflows_stat_wfh", $arrFields, array("wfh_id"), array('wfh_start', 'wfh_result', 'wfh_wfc')))
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
        if($arrModule["module_version"] == "7.0") {
            $strReturn .= $this->update_70_71();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.1") {
            $strReturn .= $this->update_71_711();
        }

        return $strReturn."\n\n";
	}

    private function update_70_71() {
        $strReturn = "Migrating date col\n";
        $this->objDB->addColumn("agp_workflows", "workflows_triggerdate",DbDatatypes::STR_TYPE_LONG);

        $generator = $this->objDB->getGenerator("SELECT system_date_start, workflows_id FROM agp_system_date, agp_workflows WHERE system_date_id = workflows_id ORDER BY system_date_id DESC", [], 2048, false);
        foreach ($generator as $sets) {
            foreach ($sets as $row) {
                $this->objDB->_pQuery("UPDATE agp_workflows SET workflows_triggerdate = ? WHERE workflows_id = ?", [$row["system_date_start"], $row["workflows_id"]]);
                $this->objDB->_pQuery("DELETE FROM agp_system_date WHERE system_date_id = ?", [$row["workflows_id"]]);
            }
        }

        $strReturn .= "Updating module-versions...\n";
        $this->objDB->flushQueryCache();
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1");

        return $strReturn;
    }

    private function update_71_711() {
        $strReturn = "Adding index column".PHP_EOL;
        $this->objDB->createIndex('agp_workflows_stat_wfh', 'ix_'.generateSystemid(), ['wfh_wfc']);
        $strReturn .= "Updating module-versions...\n";
        $this->objDB->flushQueryCache();
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1.1");

        return $strReturn;
    }

}
