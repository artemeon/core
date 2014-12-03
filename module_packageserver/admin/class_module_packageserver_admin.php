<?php
/*"******************************************************************************************************
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                  *
********************************************************************************************************/

/**
 * Admin-GUI of the packageserver.
 * Provides all interfaces to manage the packages available for other systems
 *
 * @package module_packageserver
 * @author sidler@mulchprod.de
 * @since 4.0
 *
 * @module packageserver
 * @moduleId _packageserver_module_id_
 */
class class_module_packageserver_admin extends class_module_mediamanager_admin implements interface_admin {


    /**
     * @return array
     */
    public function getOutputModuleNavi() {
        $arrReturn = array();
        $arrReturn[] = array("view", class_link::getLinkAdmin($this->getArrModule("modul"), "list", "", $this->getLang("action_list"), "", "", true, "adminnavi"));
        $arrReturn[] = array("edit", class_link::getLinkAdmin($this->getArrModule("modul"), "logs", "", $this->getLang("action_logs"), "", "", true, "adminnavi"));

        return $arrReturn;
    }

    /**
     * Generic list of all packages available on the local filesystem
     * @return string
     * @permissions view
     * @autoTestable
     */
    protected function actionList() {
        return $this->actionOpenFolder();
    }


    /**
     * Generic list of all packages available on the local filesystem
     * @return string
     * @permissions view
     * @autoTestable
     */
    protected function actionOpenFolder() {

        if(validateSystemid(_packageserver_repo_id_)) {
            if($this->getSystemid() == "")
                $this->setSystemid(_packageserver_repo_id_);

            $objIterator = new class_array_section_iterator(class_module_mediamanager_file::getFileCount($this->getSystemid(), false, false, true));
            $objIterator->setPageNumber($this->getParam("pv"));
            $objIterator->setArraySection(class_module_mediamanager_file::loadFilesDB($this->getSystemid(), false, false, $objIterator->calculateStartPos(), $objIterator->calculateEndPos(), true));

        }
        else {
            $objIterator = new class_array_section_iterator(class_module_mediamanager_file::getFlatPackageListCount(false, false));
            $objIterator->setPageNumber($this->getParam("pv"));
            $objIterator->setArraySection(class_module_mediamanager_file::getFlatPackageList(false, false, $objIterator->calculateStartPos(), $objIterator->calculateEndPos()));
        }

        return $this->renderList($objIterator);
    }


    /**
     * @param string $strListIdentifier
     * @param bool $bitDialog
     *
     * @return string
     */
    protected function getNewEntryAction($strListIdentifier, $bitDialog = false) {
        return "";
    }

    /**
     * @param class_model $objListEntry
     * @param bool $bitDialog
     *
     * @return string
     */
    protected function renderEditAction(class_model $objListEntry, $bitDialog = false) {
        return "";
    }

    /**
     * @param class_model $objListEntry
     *
     * @return array
     */
    protected function renderAdditionalActions(class_model $objListEntry) {

        if($objListEntry instanceof class_module_mediamanager_file && $objListEntry->getIntType() == class_module_mediamanager_file::$INT_TYPE_FOLDER)
            return array(
                $this->objToolkit->listButton(class_link::getLinkAdmin($this->getArrModule("modul"), "openFolder", "&systemid=".$objListEntry->getSystemid(), "", $this->getLang("action_open_folder", "mediamanager"), "icon_folderActionOpen"))
            );


        else if($objListEntry instanceof class_module_mediamanager_file && $objListEntry->getIntType() == class_module_mediamanager_file::$INT_TYPE_FILE) {
            return array(
                $this->objToolkit->listButton(
                    class_link::getLinkAdminDialog($this->getArrModule("modul"), "showInfo", "&systemid=".$objListEntry->getSystemid(), $this->getLang("package_info"), $this->getLang("package_info"), "icon_lens", $objListEntry->getStrDisplayName())
                )
            );
        }

        return array();
    }


    /**
     * @return string
     * @permissions edit
     */
    protected function actionEdit() {
        return $this->getLang("commons_error_permissions");
    }

    /**
     * Not supported
     * @return string
     * @permissions edit
     */
    protected function actionNew() {
        return $this->getLang("commons_error_permissions");
    }

    /**
     * Creates a small print-view of the current package, rendering all relevant key-value-pairs
     * @permissions view
     * @return string
     */
    protected function actionShowInfo() {
        $strReturn = "";
        $this->setArrModuleEntry("template", "/folderview.tpl");

        /** @var $objPackage class_module_mediamanager_file */
        $objPackage = class_objectfactory::getInstance()->getObject($this->getSystemid());
        if($objPackage instanceof class_module_mediamanager_file && $objPackage->rightView()) {

            $objManager = new class_module_packagemanager_manager();
            $objHandler = $objManager->getPackageManagerForPath($objPackage->getStrFilename());

            /** @var class_module_packagemanager_admin $objAdmin */
            $objAdmin = class_module_system_module::getModuleByName("packagemanager")->getAdminInstanceOfConcreteModule();
            $strReturn .= $objAdmin->renderPackageDetails($objHandler);
        }

        return $strReturn;

    }

    /**
     * Copies the metadata.xml content into the files properties.
     * @permissions edit
     * @xml
     * @return string
     */
    protected function actionUpdateDataFromMetadata() {
        $objPackage = new class_module_mediamanager_file($this->getSystemid());
        $objZip = new class_zip();
        $strMetadata = $objZip->getFileFromArchive($objPackage->getStrFilename(), "/metadata.xml");
        if($strMetadata !== false) {
            $objMetadata = new class_module_packagemanager_metadata();
            $objMetadata->autoInit($objPackage->getStrFilename());
            $objPackage->setStrName($objMetadata->getStrTitle());
            $objPackage->setStrDescription($objMetadata->getStrDescription());
            //updateObjectToDb triggers the update of the isPackage and the category flags
            $objPackage->updateObjectToDb();
            return "<message><success /></message>";
        }

        return "<message><error /></message>";

    }


    /**
     * Show a log of all queries
     *
     * @return string
     * @permissions edit
     * @autoTestable
     */
    protected function actionLogs() {
        $strReturn = "";

        $intNrOfRecordsPerPage = 25;

        $objLog = new class_module_packageserver_log();
        $objArraySectionIterator = new class_array_section_iterator($objLog->getLogDataCount());
        $objArraySectionIterator->setIntElementsPerPage($intNrOfRecordsPerPage);
        $objArraySectionIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection($objLog->getLogData($objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

        $arrLogs = array();
        foreach($objArraySectionIterator as $intKey => $arrOneLog) {
            $arrLogs[$intKey][0] = dateToString(new class_date($arrOneLog["log_date"]));
            $arrLogs[$intKey][1] = $arrOneLog["log_ip"];
            $arrLogs[$intKey][2] = $arrOneLog["log_hostname"];
            $arrLogs[$intKey][3] = $arrOneLog["log_query"];
        }
        //Create a data-table
        $arrHeader = array();
        $arrHeader[0] = $this->getLang("commons_date");
        $arrHeader[1] = $this->getLang("header_ip");
        $arrHeader[2] = $this->getLang("header_hostname");
        $arrHeader[3] = $this->getLang("header_query");
        $strReturn .= $this->objToolkit->dataTable($arrHeader, $arrLogs);
        $strReturn .= $this->objToolkit->getSimplePageview($objArraySectionIterator, $this->getArrModule("modul"), "logs");

        return $strReturn;
    }


    /**
     * @param string $strListIdentifier
     *
     * @return array
     */
    protected function getBatchActionHandlers($strListIdentifier) {
        $arrDefault = array();
        $arrDefault[] = new class_admin_batchaction(class_adminskin_helper::getAdminImage("icon_text"), class_link::getLinkAdminXml("packageserver", "updateDataFromMetadata", "&systemid=%systemid%"), $this->getLang("batchaction_metadata"));
        return $arrDefault;
    }

    /**
     * Generates a path-navigation
     *
     * @return array
     */
    protected function getArrOutputNaviEntries() {
        $arrEntries = class_admin_controller::getArrOutputNaviEntries();

        $arrPath = $this->getPathArray();
        array_shift($arrPath);

        foreach($arrPath as $strOneSystemid) {
            $objPoint = class_objectfactory::getInstance()->getObject($strOneSystemid);
            $arrEntries[] = class_link::getLinkAdmin($this->getArrModule("modul"), "openFolder", "&systemid=".$strOneSystemid, $objPoint->getStrDisplayName());
        }

        return $arrEntries;

    }
}
