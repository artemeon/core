<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                        *
********************************************************************************************************/

namespace Kajona\Dashboard\Installer;

use Kajona\Dashboard\System\DashboardConfig;
use Kajona\Dashboard\System\DashboardUserRoot;
use Kajona\Dashboard\System\DashboardWidget;
use Kajona\Dashboard\System\ICalendar;
use Kajona\System\System\InstallerBase;
use Kajona\System\System\InstallerInterface;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\OrmSchemamanager;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;

/**
 * Installer for the system-module
 *
 * @package module_dashboard
 *
 * @moduleId _dashboard_module_id_
 */
class InstallerDashboard extends InstallerBase implements InstallerInterface {

	public function install() {
	    $strReturn = "";

        $objManager = new OrmSchemamanager();
		$strReturn .= "Installing table dashboard...\n";
        $objManager->createTable(DashboardWidget::class);
        $objManager->createTable(DashboardConfig::class);
        $objManager->createTable(DashboardUserRoot::class);
        $objManager->createTable(ICalendar::class);

        $this->registerConstant("_dashboard_cal_dav_month_after_", 12, SystemSetting::$int_TYPE_INT, _dashboard_module_id_);
        $this->registerConstant("_dashboard_cal_dav_month_before_", 3, SystemSetting::$int_TYPE_INT, _dashboard_module_id_);
        $this->registerConstant("_dashboard_cal_dav_valid_time_", 15, SystemSetting::$int_TYPE_INT, _dashboard_module_id_);

        //the dashboard
        $this->registerModule("dashboard", _dashboard_module_id_, "", "DashboardAdmin.php", $this->objMetadata->getStrVersion());

        $strReturn .= "Setting dashboard to pos 1 in navigation.../n";
        $objModule = SystemModule::getModuleByName("dashboard");
        $objModule->setAbsolutePosition(1);


        return $strReturn;
	}


	public function update() {
	    $strReturn = "";
        //check installed version and to which version we can update
        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        $strReturn .= "Version found:\n\t Module: ".$arrModule["module_name"].", Version: ".$arrModule["module_version"]."\n\n";

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.0") {
            $strReturn .= "Updating to 7.1...\n";
            $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1");
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.1") {
            $strReturn .= $this->update_71_711();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.1.1") {
            $strReturn .= $this->update_711_712();
        }

        return $strReturn."\n\n";
	}

    private function update_71_711()
    {
        $return = "Updating to 7.1.1".PHP_EOL;

        $return .= "Schema udate".PHP_EOL;
        $objManager = new OrmSchemamanager();
        $objManager->createTable(DashboardConfig::class);
        $objManager->createTable(DashboardUserRoot::class);

        $return .= "Migrating dashboards to configurable dashboards".PHP_EOL;

        $return .= "Adding user root".PHP_EOL;
        $strQuery = "SELECT dashboard_id, dashboard_user, dashboard_aspect
        			  FROM agp_dashboard,
        			  	   agp_system
        			 WHERE dashboard_id = system_id
        			   AND system_prev_id = ?
        			   AND dashboard_class = ?
        	     ORDER BY system_sort ASC ";

        $legacyRootData = $this->objDB->getPArray($strQuery, [SystemModule::getModuleByName("dashboard")->getSystemid(), "root_node"]);

        foreach ($legacyRootData as $row) {
            $userId = $row['dashboard_user'];
            $aspectId = $row['dashboard_aspect'];
            $legacyRootId = $row['dashboard_id'];

            $root = DashboardUserRoot::getOrCreateForUser($userId);

            $objAspect = Objectfactory::getInstance()->getObject($aspectId);
            if ($objAspect instanceof SystemAspect) {

                $strQuery = "SELECT system_id
        			  FROM agp_dashboard,
        			  	   agp_system
        			 WHERE dashboard_id = system_id
        			   AND system_prev_id = ?
        			   AND dashboard_user = ?";

                $arrOldWidgets = $this->objDB->getPArray($strQuery, array($legacyRootId, $userId));


                if (in_array($objAspect->getStrName(), ["dls", "riskmanager"])) {
                    //create dashboard node
                    $cfgNode = new DashboardConfig();
                    $cfgNode->setStrTitle($objAspect->getStrDisplayName());
                    ServiceLifeCycleFactory::getLifeCycle($cfgNode)->update($cfgNode, $root->getSystemid());
                    //migrate widgets
                    foreach ($arrOldWidgets as $data) {
                        $oldWidget = Objectfactory::getInstance()->getObject($data["system_id"]);
                        ServiceLifeCycleFactory::getLifeCycle($oldWidget)->update($oldWidget, $cfgNode->getSystemid());
                    }


                } else {
                    //delete subordinate widgets
                    foreach ($arrOldWidgets as $data) {
                        $oldWidget = Objectfactory::getInstance()->getObject($data["system_id"]);
                        ServiceLifeCycleFactory::getLifeCycle($oldWidget)->deleteObjectFromDatabase($oldWidget);
                    }
                }

                //remove
                $oldCfg = Objectfactory::getInstance()->getObject($legacyRootId);
                ServiceLifeCycleFactory::getLifeCycle($oldCfg)->deleteObjectFromDatabase($oldCfg);
            }

        }

        $this->objDB->removeColumn("agp_dashboard", "dashboard_user");
        $this->objDB->removeColumn("agp_dashboard", "dashboard_aspect");

        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1.1");
        return $return;
	}

    private function update_711_712()
    {
        $message = "Updating to 7.1.2".PHP_EOL;

        $schema = new OrmSchemamanager();
        $schema->updateTable(ICalendar::class);
        $this->registerConstant("_dashboard_cal_dav_month_after_", 12, SystemSetting::$int_TYPE_INT, _dashboard_module_id_);
        $this->registerConstant("_dashboard_cal_dav_month_before_", 3, SystemSetting::$int_TYPE_INT, _dashboard_module_id_);
        $this->registerConstant("_dashboard_cal_dav_valid_time_", 15, SystemSetting::$int_TYPE_INT, _dashboard_module_id_);

        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1.2");

        return $message;

    }
}
