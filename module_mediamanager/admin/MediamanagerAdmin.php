<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*    $Id$                        *
********************************************************************************************************/

namespace Kajona\Mediamanager\Admin;

use Kajona\Mediamanager\System\MediamanagerFile;
use Kajona\Mediamanager\System\MediamanagerFileFilter;
use Kajona\Mediamanager\System\MediamanagerLogbook;
use Kajona\Mediamanager\System\MediamanagerRepo;
use Kajona\System\Admin\AdminEvensimpler;
use Kajona\System\Admin\AdminInterface;
use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\ArraySectionIterator;
use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\Exception;
use Kajona\System\System\Filesystem;
use Kajona\System\System\HttpResponsetypes;
use Kajona\System\System\HttpStatuscodes;
use Kajona\System\System\Image2;
use Kajona\System\System\Imageplugins\ImageCrop;
use Kajona\System\System\Imageplugins\ImageRotate;
use Kajona\System\System\Link;
use Kajona\System\System\Logger;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\OrmObjectlistOrderby;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\Rights;
use Kajona\System\System\StringUtil;

/**
 * Admin class of the mediamanager-module. Used to sync the repos with the filesystem and to upload / manage
 * files.
 * Successor and combination of v3s' filemanager, galleries and download modules
 *
 * @package module_mediamanager
 * @author sidler@mulchprod.de
 *
 * @objectList Kajona\Mediamanager\System\MediamanagerRepo
 * @objectEdit Kajona\Mediamanager\System\MediamanagerRepo
 * @objectNew Kajona\Mediamanager\System\MediamanagerRepo
 *
 * @objectEditFile Kajona\Mediamanager\System\MediamanagerFile
 *
 * @autoTestable list,new
 *
 * @module mediamanager
 * @moduleId _mediamanager_module_id_
 */
class MediamanagerAdmin extends AdminEvensimpler implements AdminInterface
{

    const INT_LISTTYPE_FOLDER = "INT_LISTTYPE_FOLDER";
    const INT_LISTTYPE_FOLDERVIEW = "INT_LISTTYPE_FOLDERVIEW";

    /**
     * @return array
     */
    public function getOutputModuleNavi()
    {
        $arrReturn = array();
        $arrReturn[] = array("view", Link::getLinkAdmin($this->getArrModule("modul"), "list", "", $this->getLang("commons_list"), "", "", true, "adminnavi"));
        $arrReturn[] = array("", "");
        $arrReturn[] = array("edit", Link::getLinkAdmin($this->getArrModule("modul"), "massSync", "", $this->getLang("action_mass_sync"), "", "", true, "adminnavi"));
        $arrReturn[] = array("edit", Link::getLinkAdmin($this->getArrModule("modul"), "logbook", "", $this->getLang("action_logbook"), "", "", true, "adminnavi"));
        return $arrReturn;
    }


    /**
     * @param \Kajona\System\System\Model|MediamanagerRepo|MediamanagerFile $objListEntry
     *
     * @return array
     */
    protected function renderAdditionalActions(\Kajona\System\System\Model $objListEntry)
    {

        if ($objListEntry instanceof MediamanagerRepo && $objListEntry->rightView()) {
            return array($this->objToolkit->listButton(
                Link::getLinkAdmin($this->getArrModule("modul"), "openFolder", "&sync=true&systemid=".$objListEntry->getSystemid(), "", $this->getLang("action_open_folder"), "icon_folderActionOpen")
            ));
        } elseif ($objListEntry instanceof MediamanagerFile && $objListEntry->getIntType() == MediamanagerFile::$INT_TYPE_FOLDER && $objListEntry->rightView()) {
            return array($this->objToolkit->listButton(
                Link::getLinkAdmin($this->getArrModule("modul"), "openFolder", "&systemid=".$objListEntry->getSystemid(), "", $this->getLang("action_open_folder"), "icon_folderActionOpen")
            ));
        } elseif ($objListEntry instanceof MediamanagerFile && $objListEntry->getIntType() == MediamanagerFile::$INT_TYPE_FILE) {
            $arrReturn = array();
            //add a crop icon?
            $arrMime = $this->objToolkit->mimeType($objListEntry->getStrFilename());
            if (($arrMime[1] == "jpg" || $arrMime[1] == "png" || $arrMime[1] == "gif") && $objListEntry->rightEdit()) {
                $arrReturn[] = $this->objToolkit->listButton(
                    Link::getLinkAdminDialog($this->getArrModule("modul"), "imageDetails", "&file=".$objListEntry->getStrFilename(), "", $this->getLang("action_edit_image"), "icon_crop", $objListEntry->getStrDisplayName())
                );
            }

            if ($objListEntry->rightRight2()) {
                $arrReturn[] = $this->objToolkit->listButton(
                    Link::getLinkAdminManual("href='"._webpath_."/download.php?systemid=".$objListEntry->getSystemid()."'", $this->getLang("action_download"), $this->getLang("action_download"), "icon_downloads")
                );
            }

            return $arrReturn;
        }

        return array();
    }

    /**
     * @param \Kajona\System\System\ModelInterface $objListEntry
     *
     * @return string
     */
    protected function renderDeleteAction(ModelInterface $objListEntry)
    {
        if ($objListEntry instanceof MediamanagerRepo) {
            if ($objListEntry->rightDelete()) {
                $objLockmanager = $objListEntry->getLockManager();
                if (!$objLockmanager->isAccessibleForCurrentUser()) {
                    return $this->objToolkit->listButton(AdminskinHelper::getAdminImage("icon_deleteLocked", $this->getLang("commons_locked")));
                }

                return $this->objToolkit->listDeleteButton(
                    $objListEntry->getStrDisplayName(),
                    $this->getLang("delete_question_repo", $objListEntry->getArrModule("modul")),
                    Link::getLinkAdminHref($objListEntry->getArrModule("modul"), "delete", "&systemid=".$objListEntry->getSystemid().$this->getStrPeAddon())
                );
            } else {
                return "";
            }
        } else {
            return parent::renderDeleteAction($objListEntry);
        }
    }


    /**
     * @param string $strListIdentifier
     * @param bool $bitDialog
     *
     * @return array|string
     */
    protected function getNewEntryAction($strListIdentifier, $bitDialog = false)
    {

        if ($strListIdentifier == MediamanagerAdmin::INT_LISTTYPE_FOLDER || $strListIdentifier == MediamanagerAdmin::INT_LISTTYPE_FOLDERVIEW) {
            if (validateSystemid($this->getSystemid())) {
                $objCur = Objectfactory::getInstance()->getObject($this->getSystemid());
                if ($objCur->rightEdit()) {
                    return $this->objToolkit->listButton(Link::getLinkAdminManual("href=\"javascript:init_fm_newfolder_dialog();\"", "", $this->getLang("commons_create_folder"), "icon_new"));
                }
            }

        } else {
            return parent::getNewEntryAction($strListIdentifier, $bitDialog);
        }

        return "";
    }

    /**
     * @param string $strListIdentifier
     *
     * @return string
     */
    protected function renderLevelUpAction($strListIdentifier)
    {
        if ($strListIdentifier == MediamanagerAdmin::INT_LISTTYPE_FOLDER) {
            $objCur = Objectfactory::getInstance()->getObject($this->getSystemid());

            if ($objCur instanceof MediamanagerFile) {
                return $this->objToolkit->listButton(Link::getLinkAdmin($this->getArrModule("modul"), "openFolder", "&systemid=".$objCur->getPrevId(), "..", $this->getLang("commons_one_level_up"), "icon_folderActionLevelup"));
            } elseif ($objCur instanceof MediamanagerRepo) {
                return $this->objToolkit->listButton(Link::getLinkAdmin($this->getArrModule("modul"), "list", "", "..", $this->getLang("commons_one_level_up"), "icon_folderActionLevelup"));
            }
        }
        if ($strListIdentifier == self::INT_LISTTYPE_FOLDERVIEW) {
            $objCur = Objectfactory::getInstance()->getObject($this->getSystemid());
            $strTargetId = $objCur->getPrevId();

            if ($strTargetId == $this->getObjModule()->getSystemid()) {
                $strTargetId = "";
            }

            $strTargetfield = xssSafeString($this->getParam("form_element"));
            return $this->objToolkit->listButton(
                Link::getLinkAdmin($this->getArrModule("modul"), "folderContentFolderviewMode", "&form_element=".$strTargetfield."&systemid=".$strTargetId, "", $this->getLang("commons_one_level_up"), "icon_folderActionLevelup")
            );
        }
        return parent::renderLevelUpAction($strListIdentifier);
    }

    /**
     * @param \Kajona\System\System\Model $objListEntry
     * @param bool $bitDialog
     *
     * @param array $arrParams
     * @return string
     * @throws Exception
     */
    protected function renderEditAction(Model $objListEntry, $bitDialog = false, array $arrParams = null)
    {
        if ($objListEntry instanceof MediamanagerFile) {
            if ($objListEntry->rightEdit()) {
                if ($this->getStrPeAddon() != "") {
                    return $this->objToolkit->listButton(
                        Link::getLinkAdmin(
                            $objListEntry->getArrModule("modul"), "editFile", "&systemid=".$objListEntry->getSystemid().$this->getStrPeAddon(), $this->getLang("commons_list_edit"), $this->getLang("commons_list_edit"), "icon_edit"
                        )
                    );
                } else {
                    return $this->objToolkit->listButton(
                        Link::getLinkAdminDialog(
                            $objListEntry->getArrModule("modul"), "editFile", "&folderview=1&systemid=".$objListEntry->getSystemid().$this->getStrPeAddon(), $this->getLang("commons_list_edit"), $this->getLang("commons_list_edit"), "icon_edit"
                        )
                    );
                }
            }

            return "";
        } else {
            return parent::renderEditAction($objListEntry, $bitDialog, $arrParams);
        }
    }

    /**
     * @param \Kajona\System\System\Model $objListEntry
     *
     * @return string
     */
    protected function renderCopyAction(\Kajona\System\System\Model $objListEntry)
    {
        if ($objListEntry instanceof MediamanagerFile) {
            return "";
        }
        return parent::renderCopyAction($objListEntry);
    }


    /**
     * A general action to delete a record.
     * This method may be overwritten by subclasses.
     *
     * @permissions delete
     * @throws Exception
     * @return void
     */
    protected function actionDelete()
    {
        $objRecord = Objectfactory::getInstance()->getObject($this->getSystemid());
        $strPrevid = $objRecord->getPrevId();

        if ($objRecord != null && $objRecord->rightDelete()) {
            if ($objRecord instanceof MediamanagerFile) {
                $this->setParam("mediamanagerDeleteFileFromFilesystem", true);
            }

            if (!$objRecord->deleteObject()) {
                throw new Exception("error deleting object ".$objRecord->getStrDisplayName(), Exception::$level_ERROR);
            }

            if ($objRecord instanceof MediamanagerRepo) {
                $this->actionMassSync();
                $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "list"));
            } else {
                $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "openFolder", "&systemid=".$strPrevid));
            }
        } else {
            throw new Exception("error loading object ".$this->getSystemid(), Exception::$level_ERROR);
        }
    }

    /**
     * @param AdminListableInterface|ModelInterface $objOneIterable
     * @param string $strListIdentifier
     *
     * @return string
     */
    public function getActionIcons($objOneIterable, $strListIdentifier = "")
    {
        if ($strListIdentifier == self::INT_LISTTYPE_FOLDERVIEW) {
            $strTargetfield = xssSafeString($this->getParam("form_element"));

            if ($objOneIterable instanceof MediamanagerFile && $objOneIterable->rightView()) {
                if ($objOneIterable->getIntType() == MediamanagerFile::$INT_TYPE_FOLDER) {
                    return $this->objToolkit->listButton(
                        Link::getLinkAdmin($this->getArrModule("modul"), "folderContentFolderviewMode", "&form_element=".$strTargetfield."&systemid=".$objOneIterable->getSystemid()."&download=".$this->getParam("download"), "", $this->getLang("action_open_folder"), "icon_folderActionOpen")
                    );
                } elseif ($objOneIterable->getIntType() == MediamanagerFile::$INT_TYPE_FILE) {
                    $strValue = $objOneIterable->getStrFilename();
                    if($this->getParam("download") == "1") {
                        $strValue = _webpath_."/download.php?systemid=".$objOneIterable->getSystemid();
                    }
                    return $this->objToolkit->listButton( //TODO
                        "<a href=\"#\" title=\"".$this->getLang("commons_accept")."\" rel=\"tooltip\" onclick=\"require('folderview').selectCallback([['".$strTargetfield."', '".$strValue."']]);\">".AdminskinHelper::getAdminImage("icon_accept")."</a>"
                    );
                }

            }

            return "";
        }
        return parent::getActionIcons($objOneIterable, $strListIdentifier);
    }


    /**
     * Loads the content of a folder
     * If requested, loads subactions,too
     *
     * @return string
     * @permissions view
     */
    protected function actionOpenFolder()
    {

        $strJsCode = "";
        if ($this->getParam("sync") == "true" && Objectfactory::getInstance()->getObject($this->getSystemid())->rightRight1()) {
            $strJsCode = <<<HTML
            <script type="text/javascript">
                require(['mediamanager', 'ajax', 'statusDisplay'], function(mediamanager, ajax) {
                    ajax.genericAjaxCall("mediamanager", "syncRepo", "{$this->getSystemid()}", function(data, status, jqXHR) {
                        if(status == 'success') {
                            if(data.indexOf("<repo>0</repo>") == -1) {
                                //show a dialog to reload the current page
                                jsDialog_1.setTitle('{$this->getLang('repo_change')}'); jsDialog_1.setContent('{$this->getLang('repo_change_hint')}', '{$this->getLang('repo_reload')}', 'javascript:document.location.reload();'); jsDialog_1.init();
                            }
                        }
                        else {
                            statusDisplay.messageError("<b>Request failed!</b>")
                        }
                    })
                });

            </script>
HTML;
        }

        $strActions = "";
        $strActions .= $this->actionUploadFileInternal();
        $strActions .= $this->generateNewFolderDialogCode();

        $objIterator = new ArraySectionIterator(MediamanagerFile::getFileCount($this->getSystemid()));
        $objIterator->setPageNumber($this->getParam("pv"));
        $objIterator->setArraySection(MediamanagerFile::loadFilesDB($this->getSystemid(), false, false, $objIterator->calculateStartPos(), $objIterator->calculateEndPos()));

        return $strJsCode.$strActions.$this->renderFloatingGrid($objIterator, MediamanagerAdmin::INT_LISTTYPE_FOLDER);

    }


    /**
     * Generates the code to delete a folder via ajax
     *
     * @return string
     */
    private function generateNewFolderDialogCode()
    {

        if (!Objectfactory::getInstance()->getObject($this->getSystemid())->rightRight1()) {
            return "";
        }

        $strReturn = "";

        //Build code for create-dialog
        $strDialog = $this->objToolkit->formInputText("folderName", $this->getLang("commons_name"));

        $strReturn .= "<script type=\"text/javascript\">
                        require(['mediamanager']);
                        function init_fm_newfolder_dialog() {
                            jsDialog_1.setTitle('".$this->getLang("folder_new_dialogHeader")."');
                            jsDialog_1.setContent('".StringUtil::replace(array("\r\n", "\n"), "", addslashes($strDialog))."',
                                                  '".$this->getLang("commons_create_folder")."',
                                                  'javascript:require(\'mediamanager\').createFolder(\'folderName\', \'".$this->getSystemid()."\'); jsDialog_1.hide();');
                                    jsDialog_1.init(); }
                      ";

        $strReturn .= "</script>";
        return $strReturn;
    }


    /**
     * Uploads or shows the form to upload a file
     *
     * @TODO check whether method is used
     * @permissions right1
     * @return string
     */
    private function actionUploadFileInternal()
    {

        if (!Objectfactory::getInstance()->getObject($this->getSystemid())->rightRight1()) {
            return "";
        }

        $strReturn = "";

        /** @var MediamanagerRepo|MediamanagerFile $objCurFile */
        $objCurFile = Objectfactory::getInstance()->getObject($this->getSystemid());

        while (!$objCurFile instanceof MediamanagerRepo && validateSystemid($this->getSystemid())) {
            $objCurFile = Objectfactory::getInstance()->getObject($objCurFile->getPrevId());
        }

        $strReturn .= $this->objToolkit->formInputUploadMultiple("mediamanager_upload", $objCurFile->getStrUploadFilter(), $this->getSystemid());

        return $strReturn;
    }


    /**
     * Synchronizes all repos available
     *
     * @return string
     * @permissions edit
     * @autoTestable
     */
    protected function actionMassSync()
    {

        /** @var $arrRepos MediamanagerRepo[] */
        $arrRepos = MediamanagerRepo::getObjectListFiltered();
        $arrSyncs = array("insert" => 0, "delete" => 0);
        foreach ($arrRepos as $objOneRepo) {
            if ($objOneRepo->rightEdit()) {
                $arrTemp = $objOneRepo->syncRepo();
                $arrSyncs["insert"] += $arrTemp["insert"];
                $arrSyncs["delete"] += $arrTemp["delete"];
            }
        }
        $strReturn = $this->getLang("sync_end");
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("sync_add").$arrSyncs["insert"].$this->getLang("sync_del").$arrSyncs["delete"]);

        return $strReturn;
    }


    /**
     * @return string
     * @permissions edit
     */
    protected function actionSaveFile()
    {

        $this->setStrCurObjectTypeName("file");
        $this->setCurObjectClassName('Kajona\Mediamanager\System\MediamanagerFile');
        parent::actionSave();

        $objFile = Objectfactory::getInstance()->getObject($this->getSystemid());

        if ($this->getParam("source") != "") {
            $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "openFolder", "&systemid=".$objFile->getPrevId()));
        } else {
            $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "openFolder", "&peClose=1&blockAction=1&systemid=".$objFile->getPrevId()));
        }
        return "";

    }


    /**
     * Returns details and additional functions handling the current image.
     *
     * @permissions view
     * @return string
     */
    protected function actionImageDetails()
    {
        $strReturn = "";

        //overlay-mode
        $this->setArrModuleEntry("template", "/folderview.tpl");

        $strFile = $this->getParam("file");
        $strFile = StringUtil::replace(_webpath_, "", $strFile);


        if (is_file(_realpath_.$strFile)) {
            $objFilesystem = new Filesystem();
            $arrDetails = $objFilesystem->getFileDetails($strFile);
            $arrSize = getimagesize(_realpath_.$strFile);


            //Generate Dimensions
            $intHeight = $arrSize[1];
            $intWidth = $arrSize[0];

            while ($intWidth > 500 || $intHeight > 400) {
                $intWidth *= 0.8;
                $intHeight *= 0.8;
            }
            //Round
            $intWidth = number_format($intWidth, 0);
            $intHeight = number_format($intHeight, 0);
            $strImage = "<img src=\""._webpath_."/image.php?image=".urlencode($strFile)."&amp;maxWidth=".$intWidth."&amp;maxHeight=".$intHeight."\" id=\"fm_mediamanagerPic\" style=\"max-width: none;\" />";


            $arrActions = array();
            $arrActions[] = $this->objToolkit->listButton(
                Link::getLinkAdminManual("href=\"#\" onclick=\"require('imageeditor').showRealSize(); return false;\"", "", $this->getLang("showRealsize"), "icon_zoom_in")
            );
            $arrActions[] = $this->objToolkit->listButton(
                Link::getLinkAdminManual(
                    "href=\"#\" onclick=\"require('imageeditor').showPreview(); return false;\"",
                    "",
                    $this->getLang("showPreview"),
                    "icon_zoom_out"
                )
            )." ";
            $arrActions[] = $this->objToolkit->listButton(
                Link::getLinkAdminManual("href=\"#\" onclick=\"require('imageeditor').rotate(90); return false;\"", "", $this->getLang("rotateImageLeft"), "icon_rotate_left")
            );
            $arrActions[] = $this->objToolkit->listButton(
                Link::getLinkAdminManual("href=\"#\" onclick=\"require('imageeditor').rotate(270); return false;\"", "", $this->getLang("rotateImageRight"), "icon_rotate_right")
            )." ";
            $arrActions[] = $this->objToolkit->listButton(
                Link::getLinkAdminManual("href=\"#\" onclick=\"require('imageeditor').showCropping(); return false;\"", "", $this->getLang("cropImage"), "icon_crop")
            );
            $arrActions[] = $this->objToolkit->listButton(
                Link::getLinkAdminManual("href=\"#\" id=\"accept_icon\"  onclick=\"require('imageeditor').saveCropping(); return false;\"", "", $this->getLang("cropImageAccept"), "icon_crop_acceptDisabled")
            )." ";


            $strReturn .= $this->objToolkit->getContentToolbar($arrActions);

            $strReturn .= "<div class=\"imageContainer\"><div class=\"image\">".$strImage."</div></div>";

            $strJs = "<script type=\"text/javascript\">
                require(['imageeditor', 'loader'], function (imageeditor, loader) {
                    loader.loadFile([
                        '".Resourceloader::getInstance()->getCorePathForModule("module_mediamanager")."/module_mediamanager/scripts/jcrop/css/jquery.Jcrop.min.css'
                    ]);
                    
                    imageeditor.strCropEnabled= '".addslashes(AdminskinHelper::getAdminImage("icon_crop_accept", $this->getLang("cropImageAccept")))."';
                    imageeditor.strCropDisabled = '".addslashes(AdminskinHelper::getAdminImage("icon_crop_acceptDisabled", $this->getLang("cropImageAccept")))."';

                    imageeditor.fm_image_rawurl = '"._webpath_."/image.php?image=".urlencode($strFile)."&quality=80';
                    imageeditor.fm_image_scaledurl = '"._webpath_."/image.php?image=".urlencode($strFile)."&maxWidth=__width__&maxHeight=__height__';
                    imageeditor.fm_image_scaledMaxWidth = $intWidth;
                    imageeditor.fm_image_scaledMaxHeight = $intHeight;
                    imageeditor.fm_image_isScaled = true;
                    imageeditor.fm_file = '".$strFile."' ;
    
                    imageeditor.init_fm_crop_save_warning_dialog = function () { jsDialog_1.setTitle('".$this->getLang("cropWarningDialogHeader")."'); jsDialog_1.setContent('".$this->getLang("cropWarningSaving")."', '".$this->getLang("cropWarningCrop")."', 'javascript:require(\'imageeditor\').saveCroppingToBackend()'); jsDialog_1.init(); }
                    imageeditor.init_fm_screenlock_dialog = function () { jsDialog_3.init(); }
                    imageeditor.hide_fm_screenlock_dialog = function () { jsDialog_3.hide(); }

                });

                </script>";

            $strJs .= "<input type=\"hidden\" name=\"fm_int_realwidth\" id=\"fm_int_realwidth\" value=\"".$arrSize[0]."\" />";
            $strJs .= "<input type=\"hidden\" name=\"fm_int_realheight\" id=\"fm_int_realheight\" value=\"".$arrSize[1]."\" />";

            $strReturn .= $strJs;

            $arrTable = array();
            $arrTable[] = array($this->getLang("commons_path"), $strFile);

            $arrTable[] = array($this->getLang("image_dimensions"), $arrSize[0]." x ".$arrSize[1]);
            $arrTable[] = array($this->getLang("file_size"), bytesToString($arrDetails["filesize"]));
            $arrTable[] = array($this->getLang("file_editdate"), timeToString($arrDetails["filechange"]));
            $strReturn .= $this->objToolkit->divider().$this->objToolkit->dataTable(array(), $arrTable);

        }
        return $strReturn;
    }


    /**
     * @return array
     */
    public function getArrOutputNaviEntries()
    {
        $arrEntries = parent::getArrOutputNaviEntries();

        //remove the duplicated link to the repo-list http://trace.kajona.de/view.php?id=856
        if (isset($arrEntries[2])) {
            unset($arrEntries[2]);
        }

        return $arrEntries;
    }

    /**
     * @param ModelInterface|Model $objInstance
     *
     * @return string
     */
    protected function getOutputNaviEntry(ModelInterface $objInstance)
    {
        return Link::getLinkAdmin($this->getArrModule("modul"), "openFolder", "&systemid=".$objInstance->getSystemid(), $objInstance->getStrDisplayName());
    }


    /**
     * Loads the content of a folder
     * If requested, loads subactions,too
     *
     * SPECIAL MODE FOR MODULE FOLDERVIEW
     *
     * @return string
     * @permissions view
     * @autoTestable
     */
    protected function actionFolderContentFolderviewMode()
    {
        $strReturn = "<script type='text/javascript'>require(['mediamanager']);</script>";

        //if set, save CKEditors CKEditorFuncNum parameter to read it again in require('folderview').selectCallback()
        //so we don't have to pass through the param with all requests
        if ($this->getParam("CKEditorFuncNum") != "") {
            $strReturn .= "<script type=\"text/javascript\">window.opener.require('folderview').selectCallbackCKEditorFuncNum = ".(int)$this->getParam("CKEditorFuncNum").";</script>";
        }

        $strTargetfield = xssSafeString($this->getParam("form_element"));

        $this->setArrModuleEntry("template", "/folderview.tpl");

        //list repos or contents?
        if ($this->getSystemid() == "") {
            //Load the repos
            $arrObjRepos = MediamanagerRepo::getObjectListFiltered();
            //Print every repo
            /** @var MediamanagerRepo $objOneRepo */
            foreach ($arrObjRepos as $objOneRepo) {
                //check rights
                if ($objOneRepo->rightView()) {
                    $strActions = "";
                    $strActions .= $this->objToolkit->listButton(
                        Link::getLinkAdmin(
                            $this->getArrModule("modul"),
                            "folderContentFolderviewMode",
                            "&form_element=".$strTargetfield."&systemid=".$objOneRepo->getSystemid()."&download=".$this->getParam("download"),
                            "",
                            $this->getLang("action_open_folder"),
                            "icon_folderActionOpen"
                        )
                    );

                    $strReturn .= $this->objToolkit->simpleAdminList($objOneRepo, $strActions);
                }
            }

            if (StringUtil::length($strReturn) != 0) {
                $strReturn = $this->objToolkit->listHeader().$strReturn.$this->objToolkit->listFooter();
            }

            if (count($arrObjRepos) == 0) {
                $strReturn .= $this->getLang("commons_list_empty");
            }
        } else {
            $objFile = Objectfactory::getInstance()->getObject($this->getSystemid());
            if ($objFile === null || !$objFile->rightView()) {
                return $this->getLang("commons_error_permissions");
            }

            $objIterator = new ArraySectionIterator(MediamanagerFile::getFileCount($this->getSystemid()));
            $objIterator->setPageNumber($this->getParam("pv"));
            $objIterator->setArraySection(MediamanagerFile::loadFilesDB($this->getSystemid(), false, false, $objIterator->calculateStartPos(), $objIterator->calculateEndPos()));

            $strReturn .= $this->actionUploadFileInternal();
            $strReturn .= $this->generateNewFolderDialogCode();
            $strReturn .= $this->renderFloatingGrid($objIterator, MediamanagerAdmin::INT_LISTTYPE_FOLDERVIEW, "&form_element=".$strTargetfield."&download=".$this->getParam("download"), false);
        }

        $strReturn .= "<script type='text/javascript'>require(['folderview'], function(f) { f.initRowClick(); });</script>";
        return $strReturn;
    }

    /**
     * @param AdminListableInterface $objOneIterable
     * @param string $strListIdentifier
     *
     * @return string
     */
    protected function renderGridEntryClickAction($objOneIterable, $strListIdentifier)
    {
        if ($strListIdentifier == self::INT_LISTTYPE_FOLDERVIEW && $objOneIterable instanceof MediamanagerFile) {
            $strTargetfield = xssSafeString($this->getParam("form_element"));

            if ($objOneIterable->getIntType() == MediamanagerFile::$INT_TYPE_FOLDER) {
                return "onclick=\"document.location='".Link::getLinkAdminHref($this->getArrModule("modul"), "folderContentFolderviewMode", "&form_element=".$strTargetfield."&systemid=".$objOneIterable->getSystemid())."&download=".$this->getParam("download")."'\"";
            } elseif ($objOneIterable->getIntType() == MediamanagerFile::$INT_TYPE_FILE) {
                $strValue = $objOneIterable->getStrFilename();
                $arrMime = $this->objToolkit->mimeType($strValue);
                $bitImage = false;
                if ($arrMime[1] == "jpg" || $arrMime[1] == "png" || $arrMime[1] == "gif") {
                    $bitImage = true;
                }

                if ($bitImage && $strTargetfield == "ckeditor") {
                    $strValue = _webpath_."/image.php?image=".$strValue;
                } elseif($this->getParam("download") == "1") {
                    $strValue = _webpath_."/download.php?systemid=".$objOneIterable->getSystemid();
                } else {
                    $strValue = _webpath_.$strValue;
                }

                return "onclick=\"require('folderview').selectCallback([['".$strTargetfield."', '".$strValue."']]);\"";
            }

            return "";
        }
        return parent::renderGridEntryClickAction($objOneIterable, $strListIdentifier);
    }


    /**
     * Generates a view to browse the filesystem directly.
     * By default, the methods takes two params into account: folder and form_element
     *
     * @return string
     * @permissions view
     * @autoTestable
     */
    protected function actionFolderListFolderview()
    {

        $this->setArrModuleEntry("template", "/folderview.tpl");
        $strReturn = "";

        //param inits
        $strFolder = "/files";
        if ($this->getParam("folder") != "") {
            $strFolder = $this->getParam("folder");
        }

        $arrExcludeFolder = array(0 => ".", 1 => "..");
        $strFormElement = xssSafeString($this->getParam("form_element"));


        $objFilesystem = new Filesystem();
        $arrContent = $objFilesystem->getCompleteList($strFolder, array(), array(), $arrExcludeFolder, true, false);

        $strReturn .= $this->objToolkit->listHeader();
        $strReturn .= $this->objToolkit->genericAdminList(generateSystemid(), $this->getLang("commons_path"), "", $strFolder);
        $strReturn .= $this->objToolkit->listFooter();
        $strReturn .= $this->objToolkit->divider();

        //Show Folders
        //Folder to jump one back up
        $arrFolderStart = array("/files");
        $strReturn .= $this->objToolkit->listHeader();
        $bitHit = false;
        if (!in_array($strFolder, $arrFolderStart) && $bitHit == false) {
            $strAction = $this->objToolkit->listButton(
                Link::getLinkAdmin(
                    $this->getArrModule("modul"),
                    "folderListFolderview",
                    "&folder=".StringUtil::substring($strFolder, 0, StringUtil::lastIndexOf($strFolder, "/"))."&form_element=".$strFormElement,
                    $this->getLang("commons_one_level_up"),
                    $this->getLang("commons_one_level_up"),
                    "icon_folderActionLevelup"
                )
            );
            $strReturn .= $this->objToolkit->genericAdminList(generateSystemid(), "..", AdminskinHelper::getAdminImage("icon_folderOpen"), $strAction);
        }
        if ($arrContent["nrFolders"] != 0) {
            foreach ($arrContent["folders"] as $strFolderCur) {
                $strAction = $this->objToolkit->listButton(
                    Link::getLinkAdmin(
                        $this->getArrModule("modul"),
                        "folderListFolderview",
                        "&folder=".$strFolder."/".$strFolderCur."&form_element=".$strFormElement,
                        $this->getLang("action_open_folder"),
                        $this->getLang("action_open_folder"),
                        "icon_folderActionOpen"
                    )
                );
                $strAction .= $this->objToolkit->listButton(
                    "<a href=\"#\" title=\"".$this->getLang("commons_accept")."\" rel=\"tooltip\" onclick=\"require('folderview').selectCallback([['".$strFormElement."', '".$strFolder."/".$strFolderCur."']]);\">"
                    .AdminskinHelper::getAdminImage("icon_accept")
                );
                $strReturn .= $this->objToolkit->genericAdminList(generateSystemid(), $strFolderCur, AdminskinHelper::getAdminImage("icon_folderOpen"), $strAction);
            }
        }
        if ($bitHit) {
            $strReturn .= $this->objToolkit->listFooter();
        }

        return $strReturn;
    }


    /**
     * Show a logbook of all downloads
     *
     * @return string
     * @permissions edit
     * @autoTestable
     */
    protected function actionLogbook()
    {
        $strReturn = "";

        $intNrOfRecordsPerPage = 25;

        $strReturn .= $this->objToolkit->getTextRow(Link::getLinkAdmin($this->getArrModule("modul"), "logbookFlush", "", $this->getLang("action_logbook_flush"), "")."<br />");

        $objLogbook = new MediamanagerLogbook();
        $objArraySectionIterator = new ArraySectionIterator($objLogbook->getLogbookDataCount());
        $objArraySectionIterator->setIntElementsPerPage($intNrOfRecordsPerPage);
        $objArraySectionIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection($objLogbook->getLogbookData($objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

        $arrLogs = array();
        foreach ($objArraySectionIterator as $intKey => $arrOneLog) {
            $arrLogs[$intKey][0] = $arrOneLog["downloads_log_id"];
            $arrLogs[$intKey][1] = timeToString($arrOneLog["downloads_log_date"]);
            $arrLogs[$intKey][2] = $arrOneLog["downloads_log_file"];
            $arrLogs[$intKey][3] = $arrOneLog["downloads_log_user"];
            $arrLogs[$intKey][4] = $arrOneLog["downloads_log_ip"];

            $strUtraceLinkMap = "href=\"http://www.utrace.de/ip-adresse/".$arrOneLog["downloads_log_ip"]."\" target=\"_blank\"";
            $strUtraceLinkText = "href=\"http://www.utrace.de/whois/".$arrOneLog["downloads_log_ip"]."\" target=\"_blank\"";
            if ($arrOneLog["downloads_log_ip"] != "127.0.0.1" && $arrOneLog["downloads_log_ip"] != "::1") {
                $arrLogs[$intKey][5] = Link::getLinkAdminManual($strUtraceLinkMap, "", $this->getLang("login_utrace_showmap", "user"), "icon_earth")
                    ." ".Link::getLinkAdminManual($strUtraceLinkText, "", $this->getLang("login_utrace_showtext", "user"), "icon_text");
            } else {
                $arrLogs[$intKey][5] = AdminskinHelper::getAdminImage("icon_earthDisabled", $this->getLang("login_utrace_noinfo", "user"))." "
                    .AdminskinHelper::getAdminImage("icon_textDisabled", $this->getLang("login_utrace_noinfo", "user"));
            }
        }
        //Create a data-table
        $arrHeader = array();
        $arrHeader[0] = $this->getLang("header_id");
        $arrHeader[1] = $this->getLang("commons_date");
        $arrHeader[2] = $this->getLang("header_file");
        $arrHeader[3] = $this->getLang("header_user");
        $arrHeader[4] = $this->getLang("header_ip");
        $arrHeader[5] = $this->getLang("login_utrace", "user");
        $strReturn .= $this->objToolkit->dataTable($arrHeader, $arrLogs);
        $strReturn .= $this->objToolkit->getPageview($objArraySectionIterator, $this->getArrModule("modul"), "logbook");

        return $strReturn;
    }

    /**
     * Shows a form or deltes a timeintervall from the logs
     *
     * @throws Exception
     * @return string "" in case of success
     * @permissions edit
     * @autoTestable
     */
    protected function actionLogbookFlush()
    {
        $strReturn = "";
        if ($this->getParam("flush") == "") {
            $strReturn .= $this->objToolkit->formHeader(Link::getLinkAdminHref($this->getArrModule("modul"), "logbookFlush", "flush=1"));
            $strReturn .= $this->objToolkit->formTextRow($this->getLang("logbook_hint_date"));
            $strReturn .= $this->objToolkit->formDateSingle("date", $this->getLang("commons_date"), new Date());
            $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
            $strReturn .= $this->objToolkit->formClose();
        } elseif ($this->getParam("flush") == "1") {
            //Build the date
            $objDate = new Date();
            $objDate->generateDateFromParams("date", $this->getAllParams());

            if (!MediamanagerLogbook::deleteFromLogs($objDate->getTimeInOldStyle())) {
                throw new Exception("Error deleting log-rows", Exception::$level_ERROR);
            }

            $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "logbook"));
        }
        return $strReturn;
    }


    /**
     * Create a new folder using the combination of passed folder & systemid
     *
     * @return string
     * @permissions edit
     */
    protected function actionCreateFolder()
    {
        $strReturn = "";

        /** @var MediamanagerRepo|MediamanagerFile $objInstance */
        $objInstance = Objectfactory::getInstance()->getObject($this->getSystemid());

        if ($objInstance->rightEdit()) {
            if ($objInstance instanceof MediamanagerFile && $objInstance->getIntType() == MediamanagerFile::$INT_TYPE_FOLDER) {
                $strPrevPath = $objInstance->getStrFilename();
            } elseif ($objInstance instanceof MediamanagerRepo) {
                $strPrevPath = $objInstance->getStrPath();
            } else {
                return "";
            }

            //create repo-instance
            $strFolder = $this->getParam("folder");

            //Create the folder
            $strFolder = createFilename($strFolder, true);
            //folder already existing?
            if (!is_dir(_realpath_.$strPrevPath."/".$strFolder)) {

                Logger::getInstance()->info("creating folder ".$strPrevPath."/".$strFolder);

                $objFilesystem = new Filesystem();
                if ($objFilesystem->folderCreate($strPrevPath."/".$strFolder)) {
                    $strReturn = "<message>".xmlSafeString($this->getLang("folder_create_success"))."</message>";
                } else {
                    ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_INTERNAL_SERVER_ERROR);
                    $strReturn = "<message><error>".xmlSafeString($this->getLang("folder_create_error"))."</error></message>";
                }
            } else {
                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_INTERNAL_SERVER_ERROR);
                $strReturn = "<message><error>".xmlSafeString($this->getLang("folder_create_error"))."</error></message>";
            }
        } else {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
            $strReturn .= "<message><error>".xmlSafeString($this->getLang("commons_error_permissions"))."</error></message>";
        }

        return $strReturn;
    }


    /**
     * Tries to save the passed file.
     * Therefore, the following post-params should be given:
     * action = fileUpload
     * folder = the folder to store the file within
     * systemid = the filemanagers' repo-id
     * inputElement = name of the inputElement
     *
     * @return string
     * @permissions right1
     * @responseType json
     * @throws Exception
     */
    protected function actionFileUpload()
    {
        /** @var MediamanagerRepo|MediamanagerFile $objFile */
        $objFile = Objectfactory::getInstance()->getObject($this->getSystemid());

        $strUploadFolder = removeDirectoryTraversals($this->getParam("folder"));

        /** @var MediamanagerRepo */
        $objRepo = null;

        if ($objFile instanceof MediamanagerFile) {
            $strTargetFolder = $objFile->getStrFilename();
            if ($objFile->getIntType() != MediamanagerFile::$INT_TYPE_FOLDER) {
                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
                return json_encode(['error' => $this->getLang("commons_error_permissions")]);
            }

            $objRepo = Objectfactory::getInstance()->getObject($objFile->getPrevId());
            while (!$objRepo instanceof MediamanagerRepo) {
                $objRepo = Objectfactory::getInstance()->getObject($objRepo->getPrevId());

                if (!$objRepo instanceof MediamanagerRepo && !$objRepo instanceof MediamanagerFile) {
                    //wrong parent record, outta here
                    ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
                    return json_encode(['error' => $this->getLang("commons_error_permissions")]);
                }
            }
        } elseif ($objFile instanceof MediamanagerRepo) {
            $objRepo = $objFile;
            $strTargetFolder = $objFile->getStrPath();
        } else {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
            return json_encode(['error' => $this->getLang("commons_error_permissions")]);
        }



        $arrReturn = [];

        //Handle the fileupload
        $arrSource = $this->getParam($this->getParam("inputElement"));
        $bitPostData = false;
        if (is_array($arrSource)) {
            $strFilename = $arrSource["name"];

            //handle copy n paste entries - rather conservative
            if ($strFilename == "blob") {
                switch ($arrSource["type"]) {
                    case 'image/png':
                        $strFilename = generateSystemid().".png";
                        break;
                    case 'image/gif':
                        $strFilename = generateSystemid().".gif";
                        break;
                    case 'image/jpg':
                        $strFilename = generateSystemid().".jpg";
                        break;
                }
            }
        } else {
            $bitPostData = getPostRawData() != "";
            $strFilename = $arrSource;
        }


        $strFullTargetFolder = $strTargetFolder.($strUploadFolder != "" ? "/".$strUploadFolder : "");
        $strTargetFile = $strFullTargetFolder . "/" .createFilename($strFilename);
        $objFilesystem = new Filesystem();

        if (!file_exists(_realpath_.$strFullTargetFolder)) {
            $objFilesystem->folderCreate($strFullTargetFolder, true);
        }

        if (is_file(_realpath_.$strTargetFile)) {
            $arrReturn['error'] = $this->getLang("upload_multiple_errorExisting");
            $arrReturn["files"][] = ["name" => createFilename($strFilename), "error" => $this->getLang("upload_multiple_errorExisting")];
        }

        if (empty($arrReturn['error']) && !$objFilesystem->isWritable($strFullTargetFolder)) {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_INTERNAL_SERVER_ERROR);
            $arrReturn['error'] = $this->getLang("xmlupload_error_notWritable");
        }

        if (empty($arrReturn['error'])) {
            //Check file for correct filters
            $arrAllowed = explode(",", $objRepo->getStrUploadFilter());

            $strSuffix = StringUtil::toLowerCase(StringUtil::substring($strFilename, StringUtil::lastIndexOf($strFilename, ".")));
            if ($objRepo->getStrUploadFilter() == "" || in_array($strSuffix, $arrAllowed)) {
                if ($bitPostData) {
                    $objFilesystem = new Filesystem();
                    $objFilesystem->openFilePointer($strTargetFile);
                    $bitCopySuccess = $objFilesystem->writeToFile(getPostRawData());
                    $objFilesystem->closeFilePointer();
                } else {
                    $bitCopySuccess = $objFilesystem->copyUpload($strTargetFile, $arrSource["tmp_name"]);
                }
                if ($bitCopySuccess) {
                    //see if we need to add the parent dir directly - avoid a full repo-sync
                    $objTargetMMFolder = MediamanagerFile::getFileForPath($objRepo->getSystemid(), $strFullTargetFolder);
                    if ($objTargetMMFolder == null && $strUploadFolder != "" &&  StringUtil::indexOf($strUploadFolder, "/") === false) {
                        $objTargetMMFolder = new MediamanagerFile();
                        $objTargetMMFolder->setStrFilename($strFullTargetFolder);
                        $objTargetMMFolder->setStrName($strUploadFolder);
                        $objTargetMMFolder->setIntType(MediamanagerFile::$INT_TYPE_FOLDER);
                        $objTargetMMFolder->updateObjectToDb($this->getSystemid());
                    }

                    $objFile = null;
                    if ($objTargetMMFolder != null) {
                        $objFile = new MediamanagerFile();
                        $objFile->setStrFilename($strTargetFile);
                        $objFile->setStrName(basename($strTargetFile));
                        $objFile->setIntType(MediamanagerFile::$INT_TYPE_FILE);
                        $objFile->updateObjectToDb($objTargetMMFolder->getSystemid());
                    } else {
                        //Oo. Need a full resync. Damn.
                        $objRepo->syncRepo();
                        $objFile = MediamanagerFile::getFileForPath($objRepo->getSystemid(), $strTargetFile);
                    }

                    $arrReturn['success'] = true;
                    if ($objFile != null) {
                        $arrReturn["files"][] = $this->mediamanagerFileToJqueryFileuploadArray($objFile);
                    }
                    Logger::getInstance()->info("uploaded file ".$strTargetFile);

                } else {
                    $arrReturn['error'] = $this->getLang("xmlupload_error_copyUpload");
                }
            } else {
                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_BADREQUEST);
                $arrReturn['error'] = $this->getLang("xmlupload_error_filter");
            }
        }

        $strReturn = json_encode($arrReturn);
        @unlink($arrSource["tmp_name"]);
        return $strReturn;
    }

    /**
     * Converts a mediamanager file to an array expected by the jquery fileupload plugin
     * @param MediamanagerFile $objFile
     * @return array
     */
    private function mediamanagerFileToJqueryFileuploadArray(MediamanagerFile $objFile)
    {

        $strDeleteButton = "";
        if ($objFile->rightDelete()) {
            $strLink = "javascript:require(\'fileupload\').deleteFile(\'{$objFile->getSystemid()}\')";
            $strDeleteButton = $this->objToolkit->listDeleteButton($objFile->getStrDisplayName(), $this->getLang("delete_file_question"), $strLink);
        }
        return [
            "name" => $objFile->getStrName(),
            "size" => $objFile->getIntFileSize(),
            "url" => $objFile->rightRight2() ? _webpath_."/download.php?systemid=".$objFile->getSystemid() : "",
            "systemid" => $objFile->getSystemid(),
            "deleteButton" => $strDeleteButton,
        ];
    }

    /**
     * Renders the list ob sub-ordinate folders, e.g. since
     * created by a versioning run
     *
     * @return string
     * @responseType html
     * @permissions view
     */
    protected function actionGetArchiveList()
    {
        $objRepo = Objectfactory::getInstance()->getObject($this->getSystemid());
        if (!$objRepo instanceof MediamanagerRepo) {
            return " ";
        }

        $objFile = MediamanagerFile::getFileForPath($this->getSystemid(), $objRepo->getStrPath()."/".$this->getParam("folder"));
        if ($objFile == null || !$objFile->rightView()) {
            return " ";
        }

        $strReturn = " ";
        $objFilter = new MediamanagerFileFilter();
        $objFilter->setBitDateDescOrder(true);
        $objFilter->setIntFileType(MediamanagerFile::$INT_TYPE_FOLDER);
        /** @var MediamanagerFile $objFolder */
        foreach (MediamanagerFile::getObjectListFiltered($objFilter, $objFile->getSystemid()) as $objFolder) {
            $objFilter = new MediamanagerFileFilter();
            $objFilter->setIntFileType(MediamanagerFile::$INT_TYPE_FILE);
            $arrFiles = MediamanagerFile::getObjectListFiltered($objFilter, $objFolder->getSystemid());
            if (count($arrFiles) > 0) {
                $strReturn .= $this->objToolkit->formHeadline($objFolder->getStrDisplayName(), "", "h4");

                $strReturn .= $this->objToolkit->listHeader();
                /** @var MediamanagerFile $objSingleFile */
                foreach ($arrFiles as $objSingleFile) {
                    $strReturn .= $this->objToolkit->genericAdminList(
                        $objSingleFile->getStrSystemid(),
                        $objSingleFile->getStrDisplayName(),
                        AdminskinHelper::getAdminImage($objSingleFile->getStrIcon()[0]),
                        $objSingleFile->rightRight2() ? Link::getLinkAdminManual("href='"._webpath_."/download.php?systemid=".$objSingleFile->getSystemid()."'", $this->getLang("action_download"), $this->getLang("action_download"), "icon_downloads") : ""
                    );
                }

                $strReturn .= $this->objToolkit->listFooter();
            }
        }

        return $strReturn;
    }

    /**
     * Copies all top-level files to a sub-folder named by the current date
     *
     * folder = the folder to store the file within
     * systemid = the filemanagers' repo-id
     *
     * @return string
     * @responseType json
     * @throws Exception
     * @permissions right1
     */
    protected function actionDocumentVersioning()
    {
        $objRepo = Objectfactory::getInstance()->getObject($this->getSystemid());
        if (!$objRepo instanceof MediamanagerRepo) {
            return json_encode([]);
        }

        $objFile = MediamanagerFile::getFileForPath($this->getSystemid(), $objRepo->getStrPath()."/".$this->getParam("folder"));
        if ($objFile == null || !$objFile->rightView()) {
            return json_encode(["status" => "error", "error" => "permissions"]);
        }

        $objFilter = new MediamanagerFileFilter();
        $objFilter->setIntFileType(MediamanagerFile::$INT_TYPE_FILE);
        $arrFiles = MediamanagerFile::getObjectListFiltered($objFilter, $objFile->getSystemid());
        if (count($arrFiles) == 0) {
            return json_encode(["status" => "error", "error" => "no_files"]);
        }
        //create a new target folder
        $objDate = new Date();
        $strBaseTarget = $objFile->getStrFilename()."/".$objDate->getIntYear()."-".$objDate->getIntMonth()."-".$objDate->getIntDay();
        $strTarget = $objFile->getStrFilename()."/".$objDate->getIntYear()."-".$objDate->getIntMonth()."-".$objDate->getIntDay();
        $intI = 1;
        while (file_exists(_realpath_.$strTarget)) {
            $strTarget = $strBaseTarget."_".$intI++;
        }

        $objFilesystem = new Filesystem();
        $objFilesystem->folderCreate($strTarget);

        $arrSynced = [];
        /** @var MediamanagerFile $objCurFile */
        foreach ($arrFiles as $objCurFile) {
            $objFilesystem->fileRename($objCurFile->getStrFilename(), $strTarget."/".basename($objCurFile->getStrFilename()));
            $arrSynced[] = $objCurFile->getStrName();
        }

        //and sync
        MediamanagerFile::syncRecursive($objFile->getSystemid(), $objFile->getStrFilename());
        //reset permissions to read only
        $objNewRoot = MediamanagerFile::getFileForPath($this->getSystemid(), $strTarget);
        if ($objNewRoot !== null) {
            $objRights = Carrier::getInstance()->getObjRights();
            foreach ([Rights::$STR_RIGHT_EDIT, Rights::$STR_RIGHT_DELETE, Rights::$STR_RIGHT_RIGHT1] as $strRight) {
                $arrGroups = $objRights->getArrayRights($objNewRoot->getSystemid(), $strRight);
                foreach ($arrGroups[$strRight] as $strGroup) {
                    $objRights->removeGroupFromRight($strGroup, $objNewRoot->getSystemid(), $strRight);
                }
            }
        }


        
        return json_encode(["status" => "ok", "target" => $strTarget, "moved" => $arrSynced]);
    }

    /**
     * Tries to save the passed file.
     * Therefore, the following post-params should be given:
     * action = fileUpload
     * folder = the folder to store the file within
     * systemid = the filemanagers' repo-id
     * inputElement = name of the inputElement
     *
     * @return string
     * @permissions view
     * @responseType json
     */
    protected function actionFileUploadList()
    {
        $objRepo = Objectfactory::getInstance()->getObject($this->getSystemid());
        if (!$objRepo instanceof MediamanagerRepo) {
            return json_encode([]);
        }

        $objFile = MediamanagerFile::getFileForPath($this->getSystemid(), $objRepo->getStrPath()."/".$this->getParam("folder"));
        if ($objFile == null || !$objFile->rightView()) {
            return json_encode([]);
        }


        $arrFiles = MediamanagerFile::getObjectListFiltered(null, $objFile->getSystemid());
        $arrReturn = [];
        /** @var MediamanagerFile $objFile */
        foreach ($arrFiles as $objFile) {
            if ($objFile->getIntType() == MediamanagerFile::$INT_TYPE_FILE && $objFile->rightView()) {
                $arrReturn[] = $this->mediamanagerFileToJqueryFileuploadArray($objFile);
            }
        }

        return json_encode(["files" => $arrReturn]);
    }

    /**
     * Syncs the repo partially
     *
     * @return string
     * @permissions edit
     */
    protected function actionPartialSyncRepo()
    {
        $strReturn = "";
        $strResult = "";

        /** @var MediamanagerRepo|MediamanagerFile $objInstance */
        $objInstance = Objectfactory::getInstance()->getObject($this->getSystemid());

        if ($objInstance instanceof MediamanagerFile) {
            $arrSyncs = MediamanagerFile::syncRecursive($objInstance->getSystemid(), $objInstance->getStrFilename());
        } elseif ($objInstance instanceof MediamanagerRepo) {
            $arrSyncs = $objInstance->syncRepo();
        } else {
            return "";
        }


        $strResult .= $this->getLang("sync_end")."<br />";
        $strResult .= $this->getLang("sync_add").$arrSyncs["insert"]."<br />".$this->getLang("sync_del").$arrSyncs["delete"];

        $strReturn .= "<repo>".xmlSafeString(strip_tags($strResult))."</repo>";

        Logger::getInstance()->info("synced gallery partially >".$this->getSystemid().": ".$strResult);

        return $strReturn;
    }

    /**
     * Syncs the repo partially
     *
     * @return string
     * @permissions edit
     */
    protected function actionSyncRepo()
    {
        $strReturn = "";

        /** @var MediamanagerRepo|MediamanagerFile $objInstance */
        $objInstance = Objectfactory::getInstance()->getObject($this->getSystemid());
        //close the session to avoid a blocking behaviour
        $this->objSession->sessionClose();
        if ($objInstance instanceof MediamanagerRepo) {
            $arrSyncs = $objInstance->syncRepo();
        } else {
            return "<error>mediamanager repo could not be loaded</error>";
        }

        $strResult = 0;

        $strResult += $arrSyncs["insert"] + $arrSyncs["delete"];
        $strReturn .= "<repo>".xmlSafeString(strip_tags($strResult))."</repo>";

        Logger::getInstance()->info("synced gallery partially >".$this->getSystemid().": ".$strResult);

        return $strReturn;
    }


    /**
     * Tries to rotate the passed imaged.
     * The following params are needed:
     * action = rotateImage
     * folder = the files' location
     * file = the file to crop
     * systemid = the repo-id
     * angle
     *
     * @return string
     * @permissions edit
     */
    protected function actionRotate()
    {
        $strReturn = "";

        $strFile = $this->getParam("file");

        $objImage = new Image2();
        $objImage->setUseCache(false);
        $objImage->load($strFile);
        $objImage->addOperation(new ImageRotate($this->getParam("angle")));
        if ($objImage->save($strFile)) {
            Logger::getInstance()->info("rotated file ".$strFile);
            $strReturn .= "<message>".xmlSafeString($this->getLang("xml_rotate_success"))."</message>";
        } else {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
            $strReturn .= "<message><error>".xmlSafeString($this->getLang("commons_error_permissions"))."</error></message>";
        }

        return $strReturn;
    }

    /**
     * Tries to save the passed cropping.
     * The following params are needed:
     * action = saveCropping
     * folder = the files' location
     * file = the file to crop
     * systemid = the repo-id
     * intX
     * intY
     * intWidth
     * intHeight
     *
     * @return string
     * @permissions edit
     */
    protected function actionSaveCropping()
    {
        $strReturn = "";

        $strFile = $this->getParam("file");

        $objImage = new Image2();
        $objImage->setUseCache(false);
        $objImage->load($strFile);
        $objImage->addOperation(new ImageCrop($this->getParam("intX"), $this->getParam("intY"), $this->getParam("intWidth"), $this->getParam("intHeight")));
        if ($objImage->save($strFile)) {
            Logger::getInstance()->info("cropped file ".$strFile);
            $strReturn .= "<message>".xmlSafeString($this->getLang("xml_cropping_success"))."</message>";
        } else {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
            $strReturn .= "<message><error>".xmlSafeString($this->getLang("commons_error_permissions"))."</error></message>";
        }

        return $strReturn;
    }

}

