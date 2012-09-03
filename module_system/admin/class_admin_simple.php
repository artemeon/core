<?php
/*"******************************************************************************************************
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                                            *
********************************************************************************************************/


/**
 * Class holding common methods for extended and simplified admin-guis.
 *
 * @module module_system
 * @since 4.0
 */
abstract class class_admin_simple extends class_admin {

    private $strPeAddon = "";

    public function __construct($strSystemid = "") {
        parent::__construct($strSystemid);

        if($this->getParam("pe") == "1")
            $this->strPeAddon = "&pe=1";

        if($this->getParam("unlockid") != "") {
            $objLockmanager = new class_lockmanager($this->getParam("unlockid"));
            $objLockmanager->unlockRecord(true);
        }
    }


    /**
     * Renders the form to create a new entry
     * @abstract
     * @return string
     * @permissions edit
     */
    protected abstract function actionNew();

    /**
     * Renders the form to edit an existing entry
     * @abstract
     * @return string
     * @permissions edit
     */
    protected abstract function actionEdit();

    /**
     * Renders the general list of records
     * @abstract
     * @return string
     * @permissions view
     */
    protected abstract function actionList();


    /**
     * A general action to delete a record.
     * This method may be overwritten by subclasses.
     *
     * @permissions delete
     * @throws class_exception
     */
    protected function actionDelete() {
        $objRecord = class_objectfactory::getInstance()->getObject($this->getSystemid());
        if($objRecord != null && $objRecord->rightDelete()) {
            if(!$objRecord->deleteObject())
                throw new class_exception("error deleting object ".$objRecord->getStrDisplayName(), class_exception::$level_ERROR);

            $this->adminReload(_indexpath_."?".$this->getHistory(1).($this->getParam("pe") != "" ? "&peClose=1" : ""));
        }
        else
            throw new class_exception("error loading object ".$this->getSystemid(), class_exception::$level_ERROR);
    }

    /**
     * A general action to delete a record.
     * This method may be overwritten by subclasses.
     *
     * @permissions delete
     * @throws class_exception
     */
    protected function actionCopyObject() {
        $objRecord = class_objectfactory::getInstance()->getObject($this->getSystemid());
        if($objRecord != null && $objRecord->rightEdit()) {
            if(!$objRecord->copyObject())
                throw new class_exception("error creating a copy of object ".$objRecord->getStrDisplayName(), class_exception::$level_ERROR);

            $this->adminReload(getLinkAdminHref($this->getArrModule("modul"), "list"));
        }
        else
            throw new class_exception("error loading object ".$this->getSystemid(), class_exception::$level_ERROR);
    }


    /**
     * Renders a list of items, target is the common admin-list.
     * Please be aware, that the combination of paging and sortable-lists may result in unpredictable ordering.
     * As soon as the list is sortable, the page-size should be at least the same as the number of elements
     *
     * @param class_array_section_iterator $objArraySectionIterator
     * @param bool $bitSortable
     * @param string $strListIdentifier an internal identifier to check the current parent-list
     * @param bool $bitAllowTreeDrop
     *
     * @throws class_exception
     * @return string
     */
    protected function renderList(class_array_section_iterator $objArraySectionIterator, $bitSortable = false, $strListIdentifier = "", $bitAllowTreeDrop = false) {
        $strReturn = "";
        $intI = 0;

        if($bitSortable && $objArraySectionIterator->getNrOfPages() > 1) {
            throw new class_exception("sortable lists with more than one page are not supported!", class_exception::$level_ERROR);
        }

        $strListId = generateSystemid();

        $arrPageViews = $this->objToolkit->getSimplePageview($objArraySectionIterator, $this->getArrModule("modul"), $this->getAction(), "&systemid=".$this->getSystemid().$this->strPeAddon);
        $arrIterables = $arrPageViews["elements"];

        if(count($arrIterables) == 0)
            $strReturn .= $this->objToolkit->getTextRow($this->getLang("commons_list_empty"));

        if($bitSortable)
            $strReturn .= $this->objToolkit->dragableListHeader($strListId, false, $bitAllowTreeDrop);
        else
            $strReturn .= $this->objToolkit->listHeader();

        if($this->renderLevelUpAction($strListIdentifier) != "") {
            $strReturn .= $this->objToolkit->genericAdminList("", "", "", $this->objToolkit->listButton($this->renderLevelUpAction($strListIdentifier)), $intI++);
        }

        if(count($arrIterables) > 0) {

            /** @var $objOneIterable class_model|interface_model|interface_admin_listable */
            foreach($arrIterables as $objOneIterable) {

                if(!$objOneIterable->rightView())
                    continue;

                $strActions = $this->getActionIcons($objOneIterable, $strListIdentifier);
                $strReturn .= $this->objToolkit->simpleAdminList($objOneIterable, $strActions, $intI++);
            }
        }

        if(is_array($this->getNewEntryAction($strListIdentifier)) || $this->getNewEntryAction($strListIdentifier) != "") {
            if(is_array($this->getNewEntryAction($strListIdentifier))) {
                $strReturn .= $this->objToolkit->genericAdminList("", "", "", implode("", $this->getNewEntryAction($strListIdentifier)), $intI);
            }
            else
                $strReturn .= $this->objToolkit->genericAdminList("", "", "", $this->getNewEntryAction($strListIdentifier), $intI);
        }

        if($bitSortable)
            $strReturn .= $this->objToolkit->dragableListFooter($strListId);
        else
            $strReturn .= $this->objToolkit->listFooter();

        $strReturn .= $arrPageViews["pageview"];

        return $strReturn;
    }

    /**
     * Wrapper rendering all action-icons for a given record. In most cases used to render a list-entry.
     *
     * @param class_model|interface_model|interface_admin_listable $objOneIterable
     * @param string $strListIdentifier
     *
     * @return string
     */
    public function getActionIcons($objOneIterable, $strListIdentifier = "") {
        $strActions = "";
        $strActions .= $this->renderUnlockAction($objOneIterable);
        $strActions .= $this->renderEditAction($objOneIterable);
        $arrAddons = $this->renderAdditionalActions($objOneIterable);
        if(is_array($arrAddons))
            $strActions .= implode("", $this->renderAdditionalActions($objOneIterable));
        $strActions .= $this->renderDeleteAction($objOneIterable);
        $strActions .= $this->renderCopyAction($objOneIterable);
        $strActions .= $this->renderStatusAction($objOneIterable);
        $strActions .= $this->renderTagAction($objOneIterable);
        $strActions .= $this->renderChangeHistoryAction($objOneIterable);
        $strActions .= $this->renderPermissionsAction($objOneIterable);

        return $strActions;
    }


    /**
     * Renders the action to jump a level upwards.
     * Overwrite this method if you want to provide such an action.
     *
     * @param $strListIdentifier
     * @return string
     */
    protected function renderLevelUpAction($strListIdentifier) {
        return "";
    }

    /**
     * Renders the edit action button for the current record.
     *
     * @param class_model $objListEntry
     * @param bool $bitDialog opens the linked page in a js-based dialog
     *
     * @return string
     */
    protected function renderEditAction(class_model $objListEntry, $bitDialog = false) {
        if($objListEntry->rightEdit()) {

            $objLockmanager = $objListEntry->getLockManager();
            if(!$objLockmanager->isAccessibleForCurrentUser()) {
                return $this->objToolkit->listButton(getImageAdmin("icon_pencilLocked.png", $this->getLang("commons_locked")));
            }

            if($bitDialog)
                return $this->objToolkit->listButton(
                    getLinkAdminDialog(
                        $objListEntry->getArrModule("modul"),
                        "edit",
                        "&systemid=".$objListEntry->getSystemid().$this->strPeAddon,
                        $this->getLang("commons_list_edit"),
                        $this->getLang("commons_list_edit"),
                        "icon_pencil.png"
                    )
                );
            else
                return $this->objToolkit->listButton(
                    getLinkAdmin(
                        $objListEntry->getArrModule("modul"),
                        "edit",
                        "&systemid=".$objListEntry->getSystemid().$this->strPeAddon,
                        $this->getLang("commons_list_edit"),
                        $this->getLang("commons_list_edit"),
                        "icon_pencil.png"
                    )
                );
        }
        return "";
    }


    /**
     * Renders the unlock action button for the current record.
     * @param \class_model|\interface_model $objListEntry
     * @return string
     */
    protected function renderUnlockAction(interface_model $objListEntry) {

        $objLockmanager = $objListEntry->getLockManager();
        if(!$objLockmanager->isAccessibleForCurrentUser()) {
            if($objLockmanager->isUnlockableForCurrentUser() ) {
                return $this->objToolkit->listButton(
                    getLinkAdmin($objListEntry->getArrModule("modul"), "list", "&unlockid=".$objListEntry->getSystemid(), "", $this->getLang("commons_unlock"), "icon_lockerOpen.png")
                );
            }
        }
        return "";
    }


    /**
     * Renders the delete action button for the current record.
     * @param \class_model|\interface_model $objListEntry
     * @return string
     */
    protected function renderDeleteAction(interface_model $objListEntry) {
        if($objListEntry->rightDelete()) {

            $objLockmanager = $objListEntry->getLockManager();
            if(!$objLockmanager->isAccessibleForCurrentUser()) {
                return $this->objToolkit->listButton(getImageAdmin("i=con_tonLocked.png", $this->getLang("commons_locked")));
            }

            return $this->objToolkit->listDeleteButton(
                $objListEntry->getStrDisplayName(),
                $this->getLang("delete_question", $objListEntry->getArrModule("modul")),
                getLinkAdminHref($objListEntry->getArrModule("modul"), "delete", "&systemid=".$objListEntry->getSystemid().$this->strPeAddon)
            );
        }
        return "";
    }

    /**
     * Renders the status action button for the current record.
     * @param class_model $objListEntry
     * @return string
     */
    protected function renderStatusAction(class_model $objListEntry) {
        if($objListEntry->rightEdit() && $this->strPeAddon == "") {
            return $this->objToolkit->listStatusButton($objListEntry);
        }
        return "";
    }

    /**
     * Renders the permissions action button for the current record.
     * @param class_model $objListEntry
     * @return string
     */
    protected function renderPermissionsAction(class_model $objListEntry) {
        if($objListEntry->rightRight() && $this->strPeAddon == "") {
            return $this->objToolkit->listButton(
                getLinkAdminDialog(
                    "right",
                    "change",
                    "&systemid=".$objListEntry->getSystemid().$this->strPeAddon,
                    "",
                    $this->getLang("commons_edit_permissions"),
                    getRightsImageAdminName($objListEntry->getSystemid()),
                    $objListEntry->getStrDisplayName(),
                    true,
                    true
                )
            );
        }
        return "";
    }

    /**
     * Renders the icon to edit a records tags
     * @param class_model $objListEntry
     * @return string
     */
    protected function renderTagAction(class_model $objListEntry) {
        if($objListEntry->rightEdit()) {
            return $this->objToolkit->listButton(
                getLinkAdminDialog(
                    "tags",
                    "genericTagForm",
                    "&systemid=".$objListEntry->getSystemid(),
                    $this->getLang("commons_edit_tags"),
                    $this->getLang("commons_edit_tags"),
                    "icon_tag.png",
                    $objListEntry->getStrDisplayName()
                )
            );
        }
        return "";
    }


    /**
     * Renders the permissions action button for the current record.
     * @param class_model $objListEntry
     * @return string
     */
    protected function renderCopyAction(class_model $objListEntry) {
        if($objListEntry->rightEdit() && $this->strPeAddon == "") {
            return $this->objToolkit->listButton(
                getLinkAdmin(
                    $objListEntry->getArrModule("modul"),
                    "copyObject",
                    "&systemid=".$objListEntry->getSystemid().$this->strPeAddon,
                    "",
                    $this->getLang("commons_edit_copy"),
                    "icon_copy.png"
                )
            );
        }
        return "";
    }

    /**
     * Returns an additional set of action-buttons rendered right after the edit-action.
     *
     * @param class_model $objListEntry
     * @return array
     */
    protected function renderAdditionalActions(class_model $objListEntry) {
        return array();
    }

    /**
     * Renders the action to add a new record to the end of the list.
     * Make sure you have the lang-key "module_action_new" in the modules' lang-file.
     * If you overwrite this method, you can either return a string containing the new-link or an array if you want to
     * provide multiple new-action.
     *
     * @param string $strListIdentifier an internal identifier to check the current parent-list
     * @param bool $bitDialog opens the linked pages in a dialog
     *
     * @return string|array
     */
    protected function getNewEntryAction($strListIdentifier, $bitDialog = false) {
        if($this->getObjModule()->rightEdit()) {
            if($bitDialog)
                return $this->objToolkit->listButton(
                    getLinkAdminDialog($this->getArrModule("modul"), "new", $this->strPeAddon, $this->getLang("module_action_new"), $this->getLang("module_action_new"), "icon_new.png")
                );
            else
                return $this->objToolkit->listButton(
                    getLinkAdmin($this->getArrModule("modul"), "new", $this->strPeAddon, $this->getLang("module_action_new"), $this->getLang("module_action_new"), "icon_new.png")
                );
        }
        return "";
    }

    /**
     * Renders the button to open the records' change history. In most cases, this is done in a overlay.
     * To open the change-history, the permission "right3" on the system-module is required.
     * @param class_model $objListEntry
     *
     * @return string
     */
    protected function renderChangeHistoryAction(class_model $objListEntry) {
        if(_system_changehistory_enabled_ == "true" && $objListEntry instanceof interface_versionable && $objListEntry->rightEdit() && class_module_system_module::getModuleByName("system")->rightRight3()) {
            return $this->objToolkit->listButton(
                getLinkAdminDialog(
                    "system",
                    "genericChangelog",
                    "&systemid=".$objListEntry->getSystemid(),
                    $this->getLang("commons_edit_history"),
                    $this->getLang("commons_edit_history"),
                    "icon_history.png",
                    $objListEntry->getStrDisplayName()
                )
            );
        }
        return "";
    }


}

