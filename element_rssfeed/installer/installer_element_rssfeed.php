<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2010 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                               *
********************************************************************************************************/

/**
 * Installer to install a rssfeed-element to use in the portal
 *
 * @package modul_pages
 */
class class_installer_element_rssfeed extends class_installer_base implements interface_installer {

    /**
     * Constructor
     *
     */
	public function __construct() {
        $arrModule = array();
		$arrModule["version"] 		= "3.3.0";
		$arrModule["name"] 			= "element_rssfeed";
		$arrModule["name_lang"] 	= "Element rssfeed";
		$arrModule["nummer2"] 		= _pages_content_modul_id_;
		parent::__construct($arrModule);
	}

	public function getNeededModules() {
	    return array("system", "pages");
	}
	
    public function getMinSystemVersion() {
	    return "3.2.0.9";
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
		    $objElement = class_modul_pages_element::getElement("rssfeed");
		}
		catch (class_exception $objEx)  {
		}
        if($objElement == null)
            return true;

        return false;
	}

    public function hasPostUpdates() {
        $objElement = null;
		try {
		    $objElement = class_modul_pages_element::getElement("rssfeed");
            if($objElement != null && version_compare($this->arrModule["version"], $objElement->getStrVersion(), ">"))
                return true;
		}
		catch (class_exception $objEx)  {
		}

        return false;
    }

	public function install() {
    }

    public function postInstall() {
		$strReturn = "";

		//Register the element
		$strReturn .= "Registering rssfeed-element...\n";
		//check, if not already existing
        $objElement = null;
		try {
		    $objElement = class_modul_pages_element::getElement("rssfeed");
		}
		catch (class_exception $objEx)  {
		}
		if($objElement == null) {
		    $objElement = new class_modul_pages_element();
		    $objElement->setStrName("rssfeed");
		    $objElement->setStrClassAdmin("class_element_rssfeed.php");
		    $objElement->setStrClassPortal("class_element_rssfeed.php");
		    $objElement->setIntCachetime(-1);
		    $objElement->setIntRepeat(0);
            $objElement->setStrVersion($this->getVersion());
			$objElement->updateObjectToDb();
			$strReturn .= "Element registered...\n";
		}
		else {
			$strReturn .= "Element already installed!...\n";
		}
		return $strReturn;
	}


	public function update() {
	}

    public function postUpdate() {
        $strReturn = "";
        if(class_modul_pages_element::getElement("rssfeed")->getStrVersion() == "3.2.0.9") {
            $strReturn .= $this->postUpdate_3209_321();
            $this->objDB->flushQueryCache();
        }

        if(class_modul_pages_element::getElement("rssfeed")->getStrVersion() == "3.2.1") {
            $strReturn .= $this->postUpdate_321_330();
            $this->objDB->flushQueryCache();
        }

        return $strReturn;
    }

    public function postUpdate_3209_321() {
        $strReturn = "Updating element rssfeed to 3.2.1...\n";
        $this->updateElementVersion("rssfeed", "3.2.1");
        return $strReturn;
    }

    public function postUpdate_321_330() {
        $strReturn = "Updating element rssfeed to 3.3.0...\n";
        $this->updateElementVersion("rssfeed", "3.3.0");
        return $strReturn;
    }
}
?>