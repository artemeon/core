<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                          *
********************************************************************************************************/


namespace Kajona\Packagemanager\Installer;

use Kajona\System\System\InstallerBase;
use Kajona\System\System\InstallerInterface;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemModule;

/**
 * Class providing an install for the packagemanager module
 *
 * @package module_packagemanager
 * @moduleId _packagemanager_module_id_
 */
class InstallerPackagemanager extends InstallerBase implements InstallerInterface {

    public function install() {
		$strReturn = "";

		//register the module
		$this->registerModule(
            "packagemanager",
            _packagemanager_module_id_,
            "",
            "PackagemanagerAdmin.php",
            $this->objMetadata->getStrVersion(),
            true
        );

        $strReturn .= "Setting aspect assignments...\n";
        if(SystemAspect::getAspectByName("management") != null) {
            $objModule = SystemModule::getModuleByName($this->objMetadata->getStrTitle());
            $objModule->setStrAspect(SystemAspect::getAspectByName("management")->getSystemid());
            ServiceLifeCycleFactory::getLifeCycle(get_class($objModule))->update($objModule);
        }

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

        return $strReturn."\n\n";
    }


}
