<?php
/*"******************************************************************************************************
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id: class_carrier.php 4059 2011-08-09 14:52:41Z sidler $                                            *
********************************************************************************************************/

/**
 * Admin class of the mediamanager-module. Used to sync the repos with the filesystem and to upload / manage
 * files.
 * Successor and combination of v3s' filemanager, galleries and download modules
 *
 * @package module_mediamanager
 * @author sidler@mulchprod.de
 */
class class_module_mediamanager_admin extends class_admin_simple implements interface_admin  {


    private static $INT_LISTTYPE_FOLDER = "INT_LISTTYPE_FOLDER";

    private $strPeAddon = "";

	/**
	 * Construcut
	 *
	 */
	public function __construct() {
		$this->setArrModuleEntry("moduleId", _mediamanager_module_id_);
		$this->setArrModuleEntry("modul", "mediamanager");
		parent::__construct();

        if($this->getParam("pe") == "1")
            $this->strPeAddon = "&pe=1";
	}


	public function getOutputModuleNavi() {
	    $arrReturn = array();
        $arrReturn[] = array("right", getLinkAdmin("right", "change", "&changemodule=".$this->arrModule["modul"],  $this->getLang("commons_module_permissions"), "", "", true, "adminnavi"));
        $arrReturn[] = array("", "");
	    $arrReturn[] = array("view", getLinkAdmin($this->arrModule["modul"], "list", "", $this->getLang("commons_list"), "", "", true, "adminnavi"));
     	$arrReturn[] = array("edit", getLinkAdmin($this->arrModule["modul"], "new", "", $this->getLang("actionNew"), "", "", true, "adminnavi"));
        $arrReturn[] = array("", "");
     	$arrReturn[] = array("edit", getLinkAdmin($this->arrModule["modul"], "massSync", "", $this->getLang("actionMassSync"), "", "", true, "adminnavi"));
     	return $arrReturn;
    }


    //FIXME needed?
    protected function actionSortUp() {
        $this->setPositionAndReload($this->getSystemid(), "upwards");
    }


    //FIXME needed?
    protected function actionSortDown() {
        $this->setPositionAndReload($this->getSystemid(), "downwards");
    }





    /**
     * @param class_model|class_module_mediamanager_repo|class_module_mediamanager_file $objListEntry
     * @return array
     */
    protected function renderAdditionalActions(class_model $objListEntry) {

        if($objListEntry instanceof class_module_mediamanager_repo)
            return array($this->objToolkit->listButton(getLinkAdmin($this->getArrModule("modul"), "openFolder", "&systemid=".$objListEntry->getSystemid(), "", $this->getLang("actionOpenFolder"), "icon_folderActionOpen.gif")));

        else if($objListEntry instanceof class_module_mediamanager_file && $objListEntry->getIntType() == class_module_mediamanager_file::$INT_TYPE_FOLDER)
            return array($this->objToolkit->listButton(getLinkAdmin($this->getArrModule("modul"), "openFolder", "&systemid=".$objListEntry->getSystemid(), "", $this->getLang("actionOpenFolder"), "icon_folderActionOpen.gif")));

        else if($objListEntry instanceof class_module_mediamanager_file && $objListEntry->getIntType() == class_module_mediamanager_file::$INT_TYPE_FILE) {
            //add a crop icon?
            $arrMime  = $this->objToolkit->mimeType($objListEntry->getStrFilename());
            if($arrMime[1] == "jpg" || $arrMime[1] == "png" || $arrMime[1] == "gif") {
                return array($this->objToolkit->listButton(getLinkAdminDialog($this->getArrModule("modul"), "imageDetails", "&file=".$objListEntry->getStrFilename(), "", $this->getLang("actionEditImage"), "icon_crop.gif")));
            }

        }

        return array();
    }

    protected function getNewEntryAction($strListIdentifier, $bitDialog = false) {
        if($strListIdentifier != self::$INT_LISTTYPE_FOLDER)
            return parent::getNewEntryAction($strListIdentifier, $bitDialog);
    }

    protected function renderLevelUpAction($strListIdentifier) {
        if($strListIdentifier == self::$INT_LISTTYPE_FOLDER) {
            $objCur = class_objectfactory::getInstance()->getObject($this->getSystemid());

            if($objCur instanceof class_module_mediamanager_file)
                return $this->objToolkit->listButton(getLinkAdmin($this->getArrModule("modul"), "openFolder", "&systemid=".$objCur->getPrevId(), "..", $this->getLang("commons_one_level_up"), "icon_folderActionLevelup.gif"));
            else if($objCur instanceof class_module_mediamanager_repo)
                return $this->objToolkit->listButton(getLinkAdmin($this->getArrModule("modul"), "list", "", "..", $this->getLang("commons_one_level_up"), "icon_folderActionLevelup.gif"));
        }
        return parent::renderLevelUpAction($strListIdentifier);
    }

    protected function renderEditAction(class_model $objListEntry, $bitDialog = false) {
        if($objListEntry instanceof class_module_mediamanager_file) {
            if($objListEntry->rightEdit())
                return $this->objToolkit->listButton(getLinkAdminDialog($objListEntry->getArrModule("modul"), "editFile", "&systemid=".$objListEntry->getSystemid().$this->strPeAddon, $this->getLang("commons_list_edit"), $this->getLang("commons_list_edit"), "icon_pencil.gif"));
        }
        else
            return parent::renderEditAction($objListEntry, $bitDialog);
    }


    /**
	 * Creates a list of all available galleries
	 *
	 * @return string
     * @permissions view
     * @autoTestable
	 */
	protected function actionList() {

        $objIterator = new class_array_section_iterator(class_module_mediamanager_repo::getAllReposCount());
        $objIterator->setPageNumber($this->getParam("pv"));
        $objIterator->setArraySection(class_module_mediamanager_repo::getAllRepos($objIterator->calculateStartPos(), $objIterator->calculateEndPos()));

        return $this->renderList($objIterator);

	}


    /**
     * A general action to delete a record.
     * This method may be overwritten by subclasses.
     *
     * @permissions delete
     */
    protected function actionDelete() {
        $objRecord = class_objectfactory::getInstance()->getObject($this->getSystemid());
        $strPrevid = $objRecord->getPrevId();

        if($objRecord != null && $objRecord->rightDelete()) {
            if(!$objRecord->deleteObject())
                throw new class_exception("error deleting object ".$objRecord->getStrDisplayName(), class_exception::$level_ERROR);

            $this->actionMassSync();

            if($objRecord instanceof class_module_mediamanager_repo)
                $this->adminReload(getLinkAdminHref($this->getArrModule("modul"), "list"));
            else
                $this->adminReload(getLinkAdminHref($this->getArrModule("modul"), "openFolder", "&systemid=".$strPrevid));
        }
        else
            throw new class_exception("error loading object ".$this->getSystemid(), class_exception::$level_ERROR);
    }


    /**
     * @return string
     * @permissions edit
     */
    protected function actionEdit() {
        return $this->actionNew("edit");
    }

    /**
     * Creates a form to edit / create a gallery
     *
     * @param string $strMode
     * @param \class_admin_formgenerator|null $objForm
     *
     * @permissions edit
     * @autoTestable
     * @return string
     */
	protected function actionNew($strMode = "new", class_admin_formgenerator $objForm = null) {

        $objRepo = new class_module_mediamanager_repo();
        if($strMode == "edit") {
            $objRepo = new class_module_mediamanager_repo($this->getSystemid());

            if(!$objRepo->rightEdit())
                return $this->getLang("commons_error_permissions");
        }

        if($objForm == null)
            $objForm = $this->getAdminForm($objRepo);

        $objForm->addField(new class_formentry_hidden("", "mode"))->setStrValue($strMode);
        return $objForm->renderForm(getLinkAdminHref($this->getArrModule("modul"), "saveRepo"));

	}

    private function getAdminForm(class_module_mediamanager_repo $objRepo) {
        $objForm = new class_admin_formgenerator("repo", $objRepo);
        $objForm->addDynamicField("title")->setStrLabel($this->getLang("commons_title"));
        $objField = $objForm->addDynamicField("path")->setStrLabel($this->getLang("commons_path"));
        $objField->setStrOpener(getLinkAdminDialog("filemanager", "folderListFolderview", "&form_element=".$objField->getStrEntryName()."&folder=/files", $this->getLang("commons_open_browser"), $this->getLang("commons_open_browser"), "icon_externalBrowser.gif", $this->getLang("commons_open_browser")));
        $objForm->addDynamicField("uploadFilter")->setStrHint($this->getLang("mediamanager_upload_filter_h"));
        $objForm->addDynamicField("viewFilter")->setStrHint($this->getLang("mediamanager_view_filter_h"));

        return $objForm;
    }


    /**
     * @return string
     * @permissions edit
     */
    protected function actionSaveRepo() {
        $objRepo = null;

        if($this->getParam("mode") == "new")
            $objRepo = new class_module_mediamanager_repo();

        else if($this->getParam("mode") == "edit")
            $objRepo = new class_module_mediamanager_repo($this->getSystemid());

        if($objRepo != null) {

            $objForm = $this->getAdminForm($objRepo);
            if(!$objForm->validateForm())
                return $this->actionNew($this->getParam("mode"), $objForm);

            $objForm->updateSourceObject();
            $objRepo->updateObjectToDb();

            $objRepo->syncRepo();

            $this->adminReload(getLinkAdminHref($this->arrModule["modul"]));
            return "";
        }

        return $this->getLang("commons_error_permissions");
    }




    /**
     * Loads the content of a folder
     * If requested, loads subactions,too
     *
     * @return string
     * @permissions view
     */
    protected function actionOpenFolder() {


        $strActions = "";
        $strActions .= $this->generateNewFolderDialogCode();
        $strActions .= getLinkAdminManual("href=\"javascript:init_fm_newfolder_dialog();\"", $this->getLang("commons_create_folder"), "", "", "", "", "", "inputSubmit");
        $strActions .= $this->actionUploadFileInternal();


        $objIterator = new class_array_section_iterator(class_module_mediamanager_file::getFileCount($this->getSystemid()));
        $objIterator->setPageNumber($this->getParam("pv"));
        $objIterator->setArraySection(class_module_mediamanager_file::loadFilesDB($this->getSystemid()));

        return $strActions.$this->objToolkit->divider().$this->renderList($objIterator, true, self::$INT_LISTTYPE_FOLDER);


    }


    /**
     * Generates the code to delete a folder via ajax
     * @return string
     */
    private function generateNewFolderDialogCode() {
        $strReturn = "";

        $strPath = "";
        $objCurFile = class_objectfactory::getInstance()->getObject($this->getSystemid());

        if($objCurFile instanceof class_module_mediamanager_file)
            $strPath = $objCurFile->getStrFilename();
        if($objCurFile instanceof class_module_mediamanager_repo)
            $strPath = $objCurFile->getStrPath();

        //Build code for create-dialog
        $strDialog = $this->objToolkit->formInputText("folderName", $this->getLang("commons_name"));

        $strReturn .= "<script type=\"text/javascript\">\n
                        function init_fm_newfolder_dialog() {
                            jsDialog_1.setTitle('".$this->getLang("folder_new_dialogHeader")."');
                            jsDialog_1.setContent('".uniStrReplace(array("\r\n", "\n"), "", addslashes($strDialog))."',
                                                  '".$this->getLang("commons_create_folder")."',
                                                  'javascript:KAJONA.admin.mediamanager.createFolder(\'folderName\', \'".$this->getSystemid()."\'); jsDialog_1.hide();');
                                    jsDialog_1.init(); }\n
                      ";

        $strReturn .= "</script>";
        $strReturn .= $this->objToolkit->jsDialog(1);
        return $strReturn;
    }



    /**
     * Uploads or shows the form to upload a file
     *
     * @return string
     */
    private function actionUploadFileInternal() {
        $strReturn = "";

        $strPath = "";
        $objCurFile = class_objectfactory::getInstance()->getObject($this->getSystemid());

        if($objCurFile instanceof class_module_mediamanager_file)
            $strPath = $objCurFile->getStrFilename();
        if($objCurFile instanceof class_module_mediamanager_repo)
            $strPath = $objCurFile->getStrPath();

        while(!$objCurFile instanceof class_module_mediamanager_repo)
            $objCurFile = class_objectfactory::getInstance()->getObject($objCurFile->getPrevId());

        $strReturn .= $this->objToolkit->formHeader(getLinkAdminHref($this->arrModule["modul"], $this->getAction(), "datei_upload_final=1"), "formUpload", "multipart/form-data");
        $strReturn .= $this->objToolkit->formInputHidden("systemid", $this->getSystemid());
        $strReturn .= $this->objToolkit->formInputHidden("flashuploadSystemid", $this->getSystemid());

        $strReturn .= $this->objToolkit->formInputUploadFlash("mediamanager_upload", $this->getLang("mediamanager_upload"), $objCurFile->getStrUploadFilter(), true, true);
        $strReturn .= $this->objToolkit->formClose();

        if($this->getParam("datei_upload_final") != "") {
            //Handle the fileupload
            $arrSource = $this->getParam("mediamanager_upload");

            $strTarget = $strPath."/".createFilename($arrSource["name"]);
            $objFilesystem = new class_filesystem();

            //Check file for correct filters
            $arrAllowed = explode(",", $objCurFile->getStrUploadFilter());
            $strSuffix = uniStrtolower(uniSubstr($arrSource["name"], uniStrrpos($arrSource["name"], ".")));
            if($objCurFile->getStrUploadFilter() == "" || in_array($strSuffix, $arrAllowed)) {
                if($objFilesystem->copyUpload($strTarget, $arrSource["tmp_name"])) {
                    $strReturn .= $this->getLang("upload_success");

                    $objCurFile->syncRepo();

                    class_logger::getInstance()->addLogRow("uploaded file ".$strTarget, class_logger::$levelInfo);
                }
                else
                    $strReturn .= $this->getLang("upload_fehler");
            }
            else {
                @unlink($arrSource["tmp_name"]);
                $strReturn .= $this->getLang("upload_fehler_filter");
            }
        }

        return $strReturn;
    }



	/**
	 * Synchronizes all repos available
	 *
	 * @return string
     * @permission edit
	 */
	protected function actionMassSync() {

        $arrRepos = class_module_mediamanager_repo::getAllRepos();
        $arrSyncs = array( "insert" => 0, "delete" => 0);
        foreach($arrRepos as $objOneRepo) {
            if($objOneRepo->rightEdit()) {
                $arrTemp = $objOneRepo->syncRepo();
                $arrSyncs["insert"] += $arrTemp["insert"];
                $arrSyncs["delete"] += $arrTemp["delete"];
            }
        }
        $strReturn = $this->getLang("sync_end");
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("sync_add").$arrSyncs["insert"].$this->getLang("sync_del").$arrSyncs["delete"]);

        //Flush cache
        $this->flushCompletePagesCache();

		return $strReturn;
	}


    /**
     * Creates a form to edit / create a gallery
     *
     * @param \class_admin_formgenerator|null $objForm
     *
     * @permissions edit
     * @autoTestable
     * @return string
     */
    protected function actionEditFile(class_admin_formgenerator $objForm = null) {
        $this->setArrModuleEntry("template", "/folderview.tpl");

        $objFile = new class_module_mediamanager_file($this->getSystemid());

        if(!$objFile->rightEdit())
            return $this->getLang("commons_error_permissions");

        if($objForm == null)
            $objForm = $this->getFileAdminForm($objFile);

        return $objForm->renderForm(getLinkAdminHref($this->getArrModule("modul"), "saveFile"));

    }

    private function getFileAdminForm(class_module_mediamanager_file $objFile) {
        $objForm = new class_admin_formgenerator("file", $objFile);
        $objForm->generateFieldsFromObject();
        return $objForm;
    }


    /**
     * @return string
     * @permissions edit
     */
    protected function actionSaveFile() {
        $objFile = null;

        $objFile = new class_module_mediamanager_file($this->getSystemid());

        if($objFile != null && $objFile->rightEdit()) {

            $objForm = $this->getFileAdminForm($objFile);
            if(!$objForm->validateForm())
                return $this->actionEditFile($objForm);

            $objForm->updateSourceObject();
            $objFile->updateObjectToDb();

            $this->flushCompletePagesCache();

            $this->adminReload(getLinkAdminHref($this->arrModule["modul"], "openFolder", "&peClose=1&systemid=".$objFile->getPrevId()));
            return "";
        }

        return $this->getLang("commons_error_permissions");
    }



    /**
     * Returns details and additional functions handling the current image.
     *
     * @return string
     */
    protected function actionImageDetails() {
        $strReturn = "";

        //overlay-mode
        $this->setArrModuleEntry("template", "/folderview.tpl");

        $strFile = $this->getParam("file");
        $strFile = uniStrReplace(_webpath_, "", $strFile);

        $arrTemplate = array();

        if(is_file(_realpath_.$strFile)) {

            $objFilesystem = new class_filesystem();
            $arrDetails = $objFilesystem->getFileDetails($strFile);

            $arrTemplate["file_name"] = $arrDetails["filename"];
            $arrTemplate["file_path"] = $strFile;
            $arrTemplate["file_path_title"] = $this->getLang("commons_path");

            $arrSize = getimagesize(_realpath_.$strFile);
            $arrTemplate["file_dimensions"] = $arrSize[0]." x ".$arrSize[1];
            $arrTemplate["file_dimensions_title"] = $this->getLang("image_dimensions");

            $arrTemplate["file_size"] = bytesToString($arrDetails["filesize"]);
            $arrTemplate["file_size_title"] = $this->getLang("file_size");

            $arrTemplate["file_lastedit"] = timeToString($arrDetails["filechange"]);
            $arrTemplate["file_lastedit_title"] = $this->getLang("file_editdate");

            //Generate Dimensions
            $intHeight = $arrSize[1];
            $intWidth = $arrSize[0];

            while($intWidth > 500 || $intHeight > 400) {
                $intWidth *= 0.8;
                $intHeight *= 0.8;
            }
            //Round
            $intWidth = number_format($intWidth, 0);
            $intHeight = number_format($intHeight, 0);
            $arrTemplate["file_image"] = "<img src=\""._webpath_."/image.php?image=".urlencode($strFile)."&amp;maxWidth=".$intWidth."&amp;maxHeight=".$intHeight."\" id=\"fm_mediamanagerPic\" />";

            $arrTemplate["file_actions"] = "";

            $arrTemplate["file_actions"] .= $this->objToolkit->listButton(getLinkAdminManual("href=\"#\" onclick=\"KAJONA.admin.mediamanager.imageEditor.showRealSize(); return false;\"", "", $this->getLang("showRealsize"), "icon_zoom_in.gif"));
            $arrTemplate["file_actions"] .= $this->objToolkit->listButton(getLinkAdminManual("href=\"#\" onclick=\"KAJONA.admin.mediamanager.imageEditor.showPreview(); return false;\"", "", $this->getLang("showPreview"), "icon_zoom_out.gif"))." ";
            $arrTemplate["file_actions"] .= $this->objToolkit->listButton(getLinkAdminManual("href=\"#\" onclick=\"KAJONA.admin.mediamanager.imageEditor.rotate(90); return false;\"", "", $this->getLang("rotateImageLeft"), "icon_rotate_left.gif"));
            $arrTemplate["file_actions"] .= $this->objToolkit->listButton(getLinkAdminManual("href=\"#\" onclick=\"KAJONA.admin.mediamanager.imageEditor.rotate(270); return false;\"", "", $this->getLang("rotateImageRight"), "icon_rotate_right.gif"))." ";
            $arrTemplate["file_actions"] .= $this->objToolkit->listButton(getLinkAdminManual("href=\"#\" onclick=\"KAJONA.admin.mediamanager.imageEditor.showCropping(); return false;\"", "", $this->getLang("cropImage"), "icon_crop.gif"));
            $arrTemplate["file_actions"] .= $this->objToolkit->listButton(getLinkAdminManual("href=\"#\" onclick=\"KAJONA.admin.mediamanager.imageEditor.saveCropping(); return false;\"", "", $this->getLang("cropImageAccept"), "icon_crop_acceptDisabled.gif", "accept_icon"))." ";


            $arrTemplate["filemanager_image_js"] = "<script type=\"text/javascript\">
                KAJONA.admin.loader.loadImagecropperBase();

                var fm_image_rawurl = '"._webpath_."/image.php?image=".urlencode($strFile)."&quality=80';
                var fm_image_scaledurl = '"._webpath_."/image.php?image=".urlencode($strFile)."&maxWidth=__width__&maxHeight=__height__';
                var fm_image_scaledMaxWidth = $intWidth;
                var fm_image_scaledMaxHeight = $intHeight;
                var fm_image_isScaled = true;
                var fm_file = '".$strFile."' ;
                var fm_warning_unsavedHint = '".$this->getLang("cropWarningUnsavedHint")."';

                function init_fm_crop_save_warning_dialog() { jsDialog_1.setTitle('".$this->getLang("cropWarningDialogHeader")."'); jsDialog_1.setContent('".$this->getLang("cropWarningSaving")."', '".$this->getLang("cropWarningCrop")."', 'javascript:KAJONA.admin.mediamanager.imageEditor.saveCroppingToBackend()'); jsDialog_1.init(); }
                function init_fm_screenlock_dialog() { jsDialog_3.init(); }
                function hide_fm_screenlock_dialog() { jsDialog_3.hide(); }

				</script>";

            $arrTemplate["filemanager_image_js"] .= $this->objToolkit->jsDialog(1);
            $arrTemplate["filemanager_image_js"] .= $this->objToolkit->jsDialog(3);

            $arrTemplate["filemanager_internal_code"] = "<input type=\"hidden\" name=\"fm_int_realwidth\" id=\"fm_int_realwidth\" value=\"".$arrSize[0]."\" />";
            $arrTemplate["filemanager_internal_code"] .= "<input type=\"hidden\" name=\"fm_int_realheight\" id=\"fm_int_realheight\" value=\"".$arrSize[1]."\" />";

        }
        $strReturn .= $this->objToolkit->getMediamanagerImageDetails($arrTemplate);
        return $strReturn;
    }




    /**
     * Generates a path-navigation
     *
     * @return array
     */
    protected function getArrOutputNaviEntries() {
        $arrEntries = parent::getArrOutputNaviEntries();

        $arrPath = $this->getPathArray();

        foreach($arrPath as $strOneSystemid) {
            $objPoint = class_objectfactory::getInstance()->getObject($strOneSystemid);
            $arrEntries[] = getLinkAdmin($this->getArrModule("modul"), "openFolder", "&systemid=".$strOneSystemid, $objPoint->getStrDisplayName());
        }

        return $arrEntries;

    }


}

