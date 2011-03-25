<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                               *
********************************************************************************************************/


/**
 * Interface of the seach samplecontent
 *
 * @package modul_search
 */
class class_installer_sc_search implements interface_sc_installer  {

    private $objDB;
    private $strContentLanguage;

    private $strMasterID = "";

    /**
     * Does the hard work: installs the module and registers needed constants
     *
     */
    public function install() {
        $strReturn = "";

        //fetch navifolder-id
        $strNaviFolderId = "";
        $strSystemFolderId = "";
        $arrFolder = class_modul_pages_folder::getFolderList();
        foreach($arrFolder as $objOneFolder) {
            if($objOneFolder->getStrName() == "mainnavigation")
                $strNaviFolderId = $objOneFolder->getSystemid();
            
            if($objOneFolder->getStrName() == "_system")
                $strSystemFolderId = $objOneFolder->getSystemid();
        }

        //search the master page
        $objMaster = class_modul_pages_page::getPageByName("master");
        if($objMaster != null)
            $this->strMasterID = $objMaster->getSystemid();

        $strReturn .= "Creating search page\n";
            $objPage = new class_modul_pages_page();
            $objPage->setStrName("search");

            if($this->strContentLanguage == "de")
                $objPage->setStrBrowsername("Suchergebnisse");
            else
                $objPage->setStrBrowsername("Search results");

            //set language to "" - being update by the languages sc installer later
            $objPage->setStrLanguage("");
            $objPage->setStrTemplate("kajona_demo.tpl");
            $objPage->updateObjectToDb($strSystemFolderId);
            $strSearchresultsId = $objPage->getSystemid();
            $strReturn .= "ID of new page: ".$strSearchresultsId."\n";
            $strReturn .= "Adding search-element to new page\n";
            $objPagelement = new class_modul_pages_pageelement();
            $objPagelement->setStrPlaceholder("results_search");
            $objPagelement->setStrName("results");
            $objPagelement->setStrElement("search");
            $objPagelement->updateObjectToDb();
            $strElementId = $objPagelement->getSystemid();
             $strQuery = "UPDATE "._dbprefix_."element_search
                                SET search_template = 'search_results.tpl',
                                    search_amount = 6,
                                    search_page = ''
                                WHERE content_id = '".dbsafeString($strElementId)."'";
                if($this->objDB->_query($strQuery))
                    $strReturn .= "Search element created.\n";
                else
                    $strReturn .= "Error creating search element.\n";

            $strReturn .= "Adding headline-element to new page\n";
            $objPagelement = new class_modul_pages_pageelement();
            $objPagelement->setStrPlaceholder("headline_row");
            $objPagelement->setStrName("headline");
            $objPagelement->setStrElement("row");
            $objPagelement->updateObjectToDb($strSearchresultsId);
            $strElementId = $objPagelement->getSystemid();

            if($this->strContentLanguage == "de") {
                $strQuery = "UPDATE "._dbprefix_."element_paragraph
                                SET paragraph_title = 'Suchergebnisse'
                                WHERE content_id = '".dbsafeString($strElementId)."'";
            }
            else {
                $strQuery = "UPDATE "._dbprefix_."element_paragraph
                                SET paragraph_title = 'Search results'
                                WHERE content_id = '".dbsafeString($strElementId)."'";
            }

            if($this->objDB->_query($strQuery))
                $strReturn .= "Headline element created.\n";
            else
                $strReturn .= "Error creating headline element.\n";

            $strReturn .= "Creating navigation point.\n";

//            //navigations installed?
//	        try {
//	            $objModule = class_modul_system_module::getModuleByName("navigation", true);
//	        }
//	        catch (class_exception $objException) {
//	            $objModule = null;
//	        }
//	        if($objModule != null) {
//
//		        $arrNavis = class_modul_navigation_tree::getAllNavis();
//		        $objNavi = class_modul_navigation_tree::getNavigationByName("portalnavigation");
//		        $strTreeId = $objNavi->getSystemid();
//
//		        $objNaviPoint = new class_modul_navigation_point();
//		        if($this->strContentLanguage == "de") {
//		            $objNaviPoint->setStrName("Suche");
//		        }
//		        else {
//		        	$objNaviPoint->setStrName("Search");
//		        }
//
//		        $objNaviPoint->setStrPageI("search");
//		        $objNaviPoint->updateObjectToDb($strTreeId);
//		        $strReturn .= "ID of new navigation point: ".$objNaviPoint->getSystemid()."\n";
//            }

        return $strReturn;
    }

    public function setObjDb($objDb) {
        $this->objDB = $objDb;
    }

    public function setStrContentlanguage($strContentlanguage) {
        $this->strContentLanguage = $strContentlanguage;
    }

    public function getCorrespondingModule() {
        return "search";
    }
}
?>