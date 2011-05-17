<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                  *
********************************************************************************************************/

/**
 * Loads the tags currently available in the system and renders them
 *
 * @package modul_pages
 */
class class_element_tags extends class_element_portal implements interface_portal_element {

	/**
	 * Constructor
	 *
	 * @param mixed $arrElementData
	 */
	public function __construct($objElementData) {
        $arrModule = array();
		$arrModule["name"] 			= "element_tags";
		$arrModule["author"] 		= "sidler@mulchprod.de";
		$arrModule["moduleId"] 		= _pages_elemente_modul_id_;
		$arrModule["table"]		    = _dbprefix_."element_universal";
		$arrModule["modul"]		    = "elemente";

		parent::__construct($arrModule, $objElementData);
	}

    /**
     * Looks up the list of tags and renders the list.
     *
     * @return string the prepared html-output
     */
	public function loadData() {
		$strReturn = "";


        $arrTags = class_modul_tags_tag::getTagsWithAssignments();
        
        //load the template
        $strTemplateWrapperID = $this->objTemplate->readTemplate("/element_tags/".$this->arrElementData["char1"], "tags");
        $strTemplateTagID = $this->objTemplate->readTemplate("/element_tags/".$this->arrElementData["char1"], "tagname");
        $strTemplateTaglinkID = $this->objTemplate->readTemplate("/element_tags/".$this->arrElementData["char1"], "taglink");
        
        
        $strTags = "";
        foreach($arrTags as $objTag) {
            if($objTag->rightView()) {

                $arrAssignments = $objTag->getListOfAssignments();
                

                $strLinks = "";
                //render the links - if possible
                foreach($arrAssignments as $arrOneAssignment) {
                    $objRecord = new class_modul_system_common($arrOneAssignment["tags_systemid"]);
                    $arrRecord = $objRecord->getSystemRecord();
                    if($arrRecord["system_module_nr"] == _pages_modul_id_) {
                        $objPage = new class_modul_pages_page($arrRecord["system_id"]);
                        $strLink = getLinkPortal($objPage->getStrName(), "", "_self", $objPage->getStrBrowsername(), "", "&highlight=".urlencode($objTag->getStrName()), "", "", $arrOneAssignment["tags_attribute"]);

                        $strLinks .= $this->fillTemplate(array("taglink" => $strLink), $strTemplateTaglinkID);
                    }

                    if(class_modul_system_module::getModuleByName("news") != null && $arrRecord["system_module_nr"] == _news_modul_id_) {
                        $objNews = new class_modul_news_news($arrRecord["system_id"]);
                        $strLink = getLinkPortal(_news_search_resultpage_, "", "_self", $objNews->getStrTitle(), "newsDetail", "&highlight=".urlencode($objTag->getStrName()), $arrRecord["system_id"], "", "", $objNews->getStrTitle());
                        $strLinks .= $this->fillTemplate(array("taglink" => $strLink), $strTemplateTaglinkID);
                    }

                }

                $arrTemplate = array();
                $arrTemplate["tagname"] = $objTag->getStrName();
                $arrTemplate["linkcount"] = count($arrAssignments);
                $arrTemplate["taglinks"] = $strLinks;
                $arrTemplate["tagid"] = $objTag->getSystemid();
                $strTags .= $this->fillTemplate($arrTemplate, $strTemplateTagID);
            }
        }
        
        $strReturn = $this->fillTemplate(array("tags" => $strTags), $strTemplateWrapperID);

		return $strReturn;
	}

}
?>