<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                           *
********************************************************************************************************/

namespace Kajona\Flow\Installer;

use AGP\Agp_Commons\System\ArtemeonCommon;
use Kajona\Flow\System\FlowActionAbstract;
use Kajona\Flow\System\FlowConditionAbstract;
use Kajona\Flow\System\FlowConfig;
use Kajona\Flow\System\FlowStatus;
use Kajona\Flow\System\FlowTransition;
use Kajona\System\System\DbDatatypes;
use Kajona\System\System\InstallerBase;
use Kajona\System\System\OrmSchemamanager;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemModule;

/**
 * Class providing an install for the news module
 *
 * @package module_flow
 * @moduleId _flow_module_id_
 */
class InstallerFlow extends InstallerBase
{
    public function install()
    {
        $strReturn = "";
        $objManager = new OrmSchemamanager();

        $strReturn .= "Installing table flow ...\n";
        $objManager->createTable(FlowConfig::class);

        $strReturn .= "Installing table flow_step...\n";
        $objManager->createTable(FlowStatus::class);

        $strReturn .= "Installing table flow_step_transition...\n";
        $objManager->createTable(FlowTransition::class);

        $strReturn .= "Installing table flow_step_transition_action...\n";
        $objManager->createTable(FlowActionAbstract::class);

        $strReturn .= "Installing table flow_step_transition_condition...\n";
        $objManager->createTable(FlowConditionAbstract::class);

        //register the module
        $this->registerModule(
            "flow",
            _flow_module_id_,
            "",
            "FlowAdmin.php",
            $this->objMetadata->getStrVersion()
        );

        // sync all handler classes
        FlowConfig::syncHandler();

        return $strReturn;
    }

    public function update()
    {
        $strReturn = "";
        //check installed version and to which version we can update
        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        $strReturn .= "Version found:\n\t Module: ".$arrModule["module_name"].", Version: ".$arrModule["module_version"]."\n\n";

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if ($arrModule["module_version"] == "6.2") {
            $strReturn .= $this->update_62_65();
        }

        return $strReturn;
    }

    private function update_62_65()
    {
        $strReturn = "Updating flow transition table\n";
        $this->objDB->addColumn("flow_step_transition", "transition_visible", DbDatatypes::STR_TYPE_INT);

        // make all existing transitions visible
        $dbPrefix = _dbprefix_;
        $this->objDB->_pQuery("UPDATE {$dbPrefix}flow_step_transition SET transition_visible = 1", []);

        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.5");
        return $strReturn;
    }
}
