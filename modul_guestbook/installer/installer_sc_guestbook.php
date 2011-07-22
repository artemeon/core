<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                            *
********************************************************************************************************/


/**
 * Installer of the guestbook samplecontent
 *
 * @package modul_guestbook
 */
class class_installer_sc_guestbook implements interface_sc_installer  {

    private $objDB;
    private $strContentLanguage;

    /**
     * Does the hard work: installs the module and registers needed constants
     *
     */
    public function install() {
        $strReturn = "";

        //fetch navifolder-id
        $strNaviFolderId = "";
        $arrFolder = class_modul_pages_folder::getFolderList();
        foreach($arrFolder as $objOneFolder)
            if($objOneFolder->getStrName() == "mainnavigation")
                $strNaviFolderId = $objOneFolder->getSystemid();

        $strReturn .= "Creating new guestbook...\n";
        $objGuestbook = new class_modul_guestbook_guestbook();
        $objGuestbook->setGuestbookTitle("Guestbook");
        $objGuestbook->setGuestbookModerated(1);
        $objGuestbook->updateObjectToDb();
        $strGuestbookID = $objGuestbook->getSystemid();
        $strReturn .= "ID of new guestbook: ".$strGuestbookID."\n";


        $strReturn .= "Creating new guestbook page...\n";

        $objPage = new class_modul_pages_page();
        $objPage->setStrName("guestbook");
        $objPage->setStrBrowsername("Guestbook");
        $objPage->setStrTemplate("kajona_demo.tpl");
        //set language to "" - being update by the languages sc installer later
        $objPage->setStrLanguage("");
        $objPage->updateObjectToDb($strNaviFolderId);

        $strGuestbookpageID = $objPage->getSystemid();
        $strReturn .= "ID of new page: ".$strGuestbookpageID."\n";
        $strReturn .= "Adding pagelement to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("gb1_guestbook");
        $objPagelement->setStrName("gb1");
        $objPagelement->setStrElement("guestbook");
        $objPagelement->updateObjectToDb($strGuestbookpageID);
        $strElementId = $objPagelement->getSystemid();
        $strQuery = "UPDATE "._dbprefix_."element_guestbook
                        SET guestbook_id = ?,
                            guestbook_template = ?,
                            guestbook_amount = ?
                        WHERE content_id = ?";
        if($this->objDB->_pQuery($strQuery, array($strGuestbookID, "guestbook.tpl", 5, $strElementId)))
            $strReturn .= "Guestbookelement created.\n";
        else
            $strReturn .= "Error creating Guestbookelement.\n";

        $strReturn .= "Adding headline-element to new page\n";
        $objPagelement = new class_modul_pages_pageelement();
        $objPagelement->setStrPlaceholder("headline_row");
        $objPagelement->setStrName("headline");
        $objPagelement->setStrElement("row");
        $objPagelement->updateObjectToDb($strGuestbookpageID);
        $strElementId = $objPagelement->getSystemid();
        $strQuery = "UPDATE "._dbprefix_."element_paragraph
                            SET paragraph_title = ?
                            WHERE content_id = ?";
        if($this->objDB->_pQuery($strQuery, array("Guestbook", $strElementId)))
            $strReturn .= "Headline element created.\n";
        else
            $strReturn .= "Error creating headline element.\n";


        return $strReturn;
    }

    public function setObjDb($objDb) {
        $this->objDB = $objDb;
    }

    public function setStrContentlanguage($strContentlanguage) {
        $this->strContentLanguage = $strContentlanguage;
    }

    public function getCorrespondingModule() {
        return "guestbook";
    }

}
?>