<?php
/*"******************************************************************************************************
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

/**
 * Class providing an installer for the jsonapi module
 *
 * @package module_jsonapi
 * @moduleId _jsonapi_module_id_
 */
class class_installer_jsonapi extends class_installer_base implements interface_installer_removable {

    public function install() {

        $strReturn = "";

        //register the module
        $this->registerModule("jsonapi", _jsonapi_module_id_, "", "class_module_jsonapi_admin.php", $this->objMetadata->getStrVersion(), false);

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

        //delete the module-node
        $strReturn .= "Deleting the module-registration...\n";
        $objModule = class_module_system_module::getModuleByName($this->objMetadata->getStrTitle(), true);
        if(!$objModule->deleteObject()) {
            $strReturn .= "Error deleting module, aborting.\n";
            return false;
        }

        return true;
    }


	public function update() {

	}

}
