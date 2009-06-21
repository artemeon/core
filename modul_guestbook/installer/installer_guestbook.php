<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2009 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$										*
********************************************************************************************************/

require_once(_systempath_."/class_installer_base.php");
require_once(_systempath_."/interface_installer.php");

/**
 * Installer of the guestbook
 *
 * @package modul_guestbook
 */
class class_installer_guestbook extends class_installer_base implements interface_installer {

	public function __construct() {
        $arrModule = array();
		$arrModule["version"] 		= "3.2.0";
		$arrModule["name"] 			= "guestbook";
		$arrModule["class_admin"] 	= "class_modul_guestbook_admin";
		$arrModule["file_admin"] 	= "class_modul_guestbook_admin.php";
		$arrModule["class_portal"] 	= "class_modul_guestbook_portal";
		$arrModule["file_portal"] 	= "class_modul_guestbook_portal.php";
		$arrModule["name_lang"] 	= "Module Guestbook";
		$arrModule["moduleId"] 		= _gaestebuch_modul_id_;

		$arrModule["tabellen"][]    = _dbprefix_."guestbook_buch";
		$arrModule["tabellen"][]    = _dbprefix_."guestbook_posts";
		$arrModule["tabellen"][]    = _dbprefix_."elemente_guestbook";
		parent::__construct($arrModule);
	}

	public function getNeededModules() {
	    return array("system", "pages");
	}
	
    public function getMinSystemVersion() {
	    return "3.2.0";
	}

	public function hasPostInstalls() {
		//check, if not already existing
		$strQuery = "SELECT COUNT(*) FROM "._dbprefix_."element WHERE element_name='guestbook'";
		$arrRow = $this->objDB->getRow($strQuery);
		if($arrRow["COUNT(*)"] == 0)
            return true;


        return false;
	}


   public function install() {

		$strReturn = "";
		//Tabellen anlegen

		//guestbook-------------------------------------------------------------------------------------
		$strReturn .= "Installing table guestbook_book...\n";

		$arrFields = array();
		$arrFields["guestbook_id"] 		  = array("char20", false);
		$arrFields["guestbook_title"] 	  = array("char254", true);
		$arrFields["guestbook_moderated"] = array("int", true);
		
		if(!$this->objDB->createTable("guestbook_book", $arrFields, array("guestbook_id")))
			$strReturn .= "An error occured! ...\n";

		//guestbook_post----------------------------------------------------------------------------------
		$strReturn .= "Installing table guestbook_post...\n";
		
		$arrFields = array();
		$arrFields["guestbook_post_id"]   = array("char20", false);
		$arrFields["guestbook_post_name"] = array("char254", true);
		$arrFields["guestbook_post_email"]= array("char254", true);
		$arrFields["guestbook_post_page"] = array("char254", false);
		$arrFields["guestbook_post_text"] = array("text", true);
		$arrFields["guestbook_post_date"] = array("int", true);

		if(!$this->objDB->createTable("guestbook_post", $arrFields, array("guestbook_post_id")))
			$strReturn .= "An error occured! ...\n";


		//register the module
		$strSystemID = $this->registerModule("guestbook", _gaestebuch_modul_id_, "class_modul_guestbook_portal.php", "class_modul_guestbook_admin.php", $this->arrModule["version"] , true);

		$strReturn .= "Registering system-constants...\n";
		$this->registerConstant("_guestbook_search_resultpage_", "guestbook", class_modul_system_setting::$int_TYPE_PAGE, _gaestebuch_modul_id_);

		return $strReturn;

	}

	public function postInstall() {
		$strReturn = "";

		//Table for page-element
		$strReturn .= "Installing guestbook-element table...\n";
		
		$arrFields = array();
		$arrFields["content_id"]   		= array("char20", false);
		$arrFields["guestbook_id"] 		= array("char20", true);
		$arrFields["guestbook_template"]= array("char254", true);
		$arrFields["guestbook_amount"] 	= array("int", true);
		
		if(!$this->objDB->createTable("element_guestbook", $arrFields, array("content_id")))
			$strReturn .= "An error occured! ...\n";

		//Register the element
		$strReturn .= "Registering guestbook-element...\n";
		//check, if not already existing
        $objElement = null;
		try {
		    $objElement = class_modul_pages_element::getElement("guestbook");
		}
		catch (class_exception $objEx)  {
		}
		if($objElement == null) {
		    $objElement = new class_modul_pages_element();
		    $objElement->setStrName("guestbook");
		    $objElement->setStrClassAdmin("class_element_guestbook.php");
		    $objElement->setStrClassPortal("class_element_guestbook.php");
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
	    $strReturn = "";
        //check installed version and to which version we can update
        $arrModul = $this->getModuleData($this->arrModule["name"], false);

        $strReturn .= "Version found:\n\t Module: ".$arrModul["module_name"].", Version: ".$arrModul["module_version"]."\n\n";

        $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.0.0") {
            $strReturn .= $this->update_300_301();
        }

        $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.0.1") {
            $strReturn .= $this->update_301_302();
        }
        
	    $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.0.2") {
            $strReturn .= $this->update_302_309();
        }
        
        $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.0.9") {
            $strReturn .= $this->update_309_3095();
        }
        
	    $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.0.95") {
            $strReturn .= $this->update_3095_310();
        }
        
	    $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.1.0") {
            $strReturn .= $this->update_310_311();
        }
        
	    $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.1.1") {
            $strReturn .= $this->update_311_319();
        }

        $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.1.9") {
            $strReturn .= $this->update_319_3195();
        }

        $arrModul = $this->getModuleData($this->arrModule["name"], false);
        if($arrModul["module_version"] == "3.1.95") {
            $strReturn .= $this->update_3195_320();
        }
        
        return $strReturn."\n\n";
	}


	private function update_300_301() {
	    $strReturn = "";
        $strReturn .= "Updating 3.0.0 to 3.0.1...\n";
        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.0.1");
        return $strReturn;
	}

	private function update_301_302() {
	    $strReturn = "";
        $strReturn .= "Updating 3.0.1 to 3.0.2...\n";
        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.0.2");
        return $strReturn;
	}
	
    private function update_302_309() {
        $strReturn = "";
        $strReturn .= "Updating 3.0.2 to 3.0.9...\n";
        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.0.9");
        return $strReturn;
    }	
    
	private function update_309_3095() {
        $strReturn = "";
        $strReturn .= "Updating 3.0.9 to 3.0.95...\n";
        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.0.95");
        return $strReturn;
    }
    
    private function update_3095_310() {
        $strReturn = "Updating 3.0.95 to 3.1.0...\n";
        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.1.0");
        return $strReturn;
    }
    
    private function update_310_311() {
        $strReturn = "Updating 3.1.0 to 3.1.1...\n";
        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.1.1");
        return $strReturn;
    }
    
    private function update_311_319() {
        $strReturn = "Updating 3.1.1 to 3.1.9...\n";
        
        $strReturn .= "Updating system-constants...\n";
        $objConstant = class_modul_system_setting::getConfigByName("_guestbook_suche_seite_");
        $objConstant->renameConstant("_guestbook_search_resultpage_");

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.1.9");

        return $strReturn;
    }
    
    private function update_319_3195() {
        $strReturn = "Updating 3.1.9 to 3.1.95...\n";
        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.1.95");
        return $strReturn;
    }

    private function update_3195_320() {
        $strReturn = "Updating 3.1.95 to 3.2.0...\n";
        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion("guestbook", "3.2.0");
        return $strReturn;
    }
    
}
?>