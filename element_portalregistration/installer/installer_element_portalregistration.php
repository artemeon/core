<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2009 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                    *
********************************************************************************************************/

require_once(_systempath_."/class_installer_base.php");
require_once(_systempath_."/interface_installer.php");

/**
 * Installer to install a login-element to use in the portal
 *
 * @package modul_pages
 */
class class_installer_element_portalregistration extends class_installer_base implements interface_installer {

    /**
     * Constructor
     *
     */
	public function __construct() {
        $arrModule = array();
		$arrModule["version"] 		= "3.2.0";
		$arrModule["name"] 			= "element_portalregistration";
		$arrModule["name_lang"] 	= "Element portalregistration";
		$arrModule["nummer2"] 		= _pages_inhalte_modul_id_;
		$arrModule["tabellen"][]    = _dbprefix_."element_portalregistration";
		parent::__construct($arrModule);
	}

	public function getNeededModules() {
	    return array("system", "pages");
	}
	
    public function getMinSystemVersion() {
	    return "3.2.0";
	}

	public function hasPostInstalls() {
	    //needed: pages
	    try {
		    $objModule = class_modul_system_module::getModuleByName("pages");
		}
		catch (class_exception $objE) {
		    return false;
		}

	    //check, if not already existing
	    $objElement = null;
		try {
		    $objElement = class_modul_pages_element::getElement("portalregistration");
		}
		catch (class_exception $objEx)  {
		}
        if($objElement == null)
            return true;

        return false;
	}

	public function install() {
    }

    public function postInstall() {
		$strReturn = "";

       	//Table for page-element
		$strReturn .= "Installing formular-element table...\n";
		
		$arrFields = array();
		$arrFields["content_id"] 				   = array("char20", false);
		$arrFields["portalregistration_template"]  = array("char254", true);
		$arrFields["portalregistration_group"] 	   = array("char254", true);
		$arrFields["portalregistration_success"]   = array("char254", true);
		
		if(!$this->objDB->createTable("element_portalregistration", $arrFields, array("content_id")))
			$strReturn .= "An error occured! ...\n";

		//Register the element
		$strReturn .= "Registering portalregistration-element...\n";
		//check, if not already existing
        $objElement = null;
		try {
		    $objElement = class_modul_pages_element::getElement("portalregistration");
		}
		catch (class_exception $objEx)  {
		}
		if($objElement == null) {
		    $objElement = new class_modul_pages_element();
		    $objElement->setStrName("portalregistration");
		    $objElement->setStrClassAdmin("class_element_portalregistration.php");
		    $objElement->setStrClassPortal("class_element_portalregistration.php");
		    $objElement->setIntCachetime(-1);
		    $objElement->setIntRepeat(1);
            $objElement->setStrVersion($this->getVersion());
			$objElement->saveObjectToDb();
			$strReturn .= "Element registered...\n";
		}
		else {
			$strReturn .= "Element already installed!...\n";
		}

		return $strReturn;
	}


	public function update() {
	}
}
?>