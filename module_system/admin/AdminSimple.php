<?php

/*"******************************************************************************************************
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *    $Id$                                                *
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Admin;

use Closure;
use Kajona\System\System\AdminGridableInterface;
use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\ArraySectionIterator;
use Kajona\System\System\Exception;
use Kajona\System\System\Lifecycle\ServiceLifeCycleModelException;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContextFactoryInterface;
use Kajona\System\System\Modelaction\Renderer\ModelActionsRendererInterface;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\VersionableInterface;
use Kajona\System\View\Components\Dropdownmenu\Dropdownmenu;
use Kajona\System\View\Components\Menu\Item\Text;
use Kajona\System\View\Components\Menu\Menu;
use Kajona\System\View\Components\Menu\MenuItem;

/**
 * Class holding common methods for extended and simplified admin-guis.
 *
 * @package module_system
 * @since 4.0
 */
abstract class AdminSimple extends AdminController
{
    /**
     * @var string
     * @deprecated
     */
    private $strPeAddon = "";

    /**
     * @var ModelActionContextFactoryInterface
     * @inject Kajona\System\System\Modelaction\ModelActionContextFactoryInterface
     */
    protected $modelActionContextFactory;

    /**
     * @var ModelActionsRendererInterface
     * @inject Kajona\System\System\Modelaction\ModelActionsRendererInterface
     */
    protected $modelActionsRenderer;

    /**
     * @param string $strSystemid
     * @throws Exception
     */
    public function __construct($strSystemid = "")
    {
        parent::__construct($strSystemid);

        if ($this->getParam("unlockid") != "") {
            $objUnlock = Objectfactory::getInstance()->getObject($this->getParam("unlockid"));
            if ($objUnlock !== null) {
                $objUnlock->getLockManager()->unlockRecord(true);
            }
        }
    }

    /**
     * Overwritten in order to inject a toolbar per record. may be useful for certain actions
     *
     * @param array &$arrContent
     *
     * @return void
     * @deprecated
     * @throws Exception
     */
    protected function onRenderOutput(&$arrContent)
    {
        $arrContent["actiontoolbar"] = $this->objToolkit->getContentActionToolbar($this->getContentActionToolbar());
    }

    /**
     * Default-implementation to render an action toolbar
     *
     * @return string
     * @throws Exception
     */
    public function getContentActionToolbar()
    {
        if (StringUtil::indexOf($this->getAction(), "list") !== false || StringUtil::indexOf($this->getAction(), "new") !== false || StringUtil::indexOf($this->getAction(), "save") !== false) {
            return "";
        }

        if (validateSystemid($this->getSystemid())) {
            $objRecord = $this->objFactory->getObject($this->getSystemid());

            if ($objRecord instanceof AdminListableInterface) {
                return $this->objToolkit->getContentActionToolbar($this->getActionIcons($objRecord));
            }
        }

        return "";
    }

    /**
     * Renders the form to create a new entry
     *
     * @abstract
     * @return string
     * @permissions edit
     */
    abstract protected function actionNew();

    /**
     * Renders the form to edit an existing entry
     *
     * @abstract
     * @return string
     * @permissions edit
     */
    abstract protected function actionEdit();

    /**
     * Renders the general list of records
     *
     * @abstract
     * @return string
     * @permissions view
     */
    abstract protected function actionList();

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
        $objRecord = $this->objFactory->getObject($this->getSystemid());
        if ($objRecord != null && $objRecord->rightDelete()) {
            try {
                $this->objLifeCycleFactory->factory(get_class($objRecord))->delete($objRecord);
            } catch (ServiceLifeCycleModelException $e) {
                return $this->objToolkit->warningBox($e->getMessage());
            }

            $strTargetUrl = urldecode($this->getParam("reloadUrl"));
            parse_str($strTargetUrl, $arrParams);
            $bitFound = false;
            if (isset($arrParams["systemid"])) {
                $bitFound = $arrParams["systemid"] == $this->getSystemid();
            } else {
                $arrParams = explode("/", $strTargetUrl);
                if (count($arrParams) >= 4) {
                    $bitFound = $arrParams[3] == $this->getSystemid();
                }
            }

            if ($strTargetUrl == "" || $bitFound) {
                $strTargetUrl = "admin=1&module=" . $this->getArrModule("modul");

                $intI = 1;
                while ($this->getHistory($intI) !== null) {
                    $strTargetUrl = $this->getHistory($intI++);

                    if (StringUtil::indexOf($strTargetUrl, $this->getSystemid()) === false) {
                        break;
                    }
                }

                $strTargetUrl = Link::plainUrlToHashUrl($strTargetUrl);
            }

            return "<script type='text/javascript'>Router.loadUrl('{$strTargetUrl}');</script>";
        } else {
            throw new Exception("error loading object " . $this->getSystemid(), Exception::$level_ERROR);
        }

    }

    /**
     * A general action to copy a record.
     * This method may be overwritten by subclasses.
     *
     * @permissions edit
     * @throws Exception
     * @return void
     */
    protected function actionCopyObject()
    {
        $objRecord = $this->objFactory->getObject($this->getSystemid());
        if ($objRecord != null && $objRecord->rightEdit()) {
            $this->objLifeCycleFactory->factory(get_class($objRecord))->copy($objRecord);

            $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), $this->getActionNameForClass("list", $objRecord), "&systemid=" . $objRecord->getPrevId()));
        } else {
            throw new Exception("error loading object " . $this->getSystemid(), Exception::$level_ERROR);
        }
    }

    /**
     * Returns the action name for a given class name.
     *
     * @param string $strAction
     * @param ModelInterface $objInstance
     *
     * @return string specific action name
     */
    protected function getActionNameForClass($strAction, $objInstance)
    {
        return $strAction;
    }

    /**
     * Renders a list of items in a floatable "thumbnail" view, so a grid.
     * Please be aware, that the combination of paging and grids may result in unpredictable ordering.
     * As soon as the list is sortable, the page-size should be at least the same as the number of elements
     *
     * @param ArraySectionIterator $objArraySectionIterator
     * @param string $strListIdentifier an internal identifier to check the current parent-list
     * @param string $strPagerAddon
     * @param bool $bitSortable
     *
     * @throws Exception
     * @return string
     */
    final protected function renderFloatingGrid(ArraySectionIterator $objArraySectionIterator, $strListIdentifier = "", $strPagerAddon = "", $bitSortable = true)
    {
        $strReturn = "";

        $strListActions = "";
        if ($this->renderLevelUpAction($strListIdentifier) != "") {
            $strListActions .= $this->objToolkit->listButton($this->renderLevelUpAction($strListIdentifier));
        }

        $strListActions .= $this->mergeNewEntryActions($this->getNewEntryAction($strListIdentifier));

        if ($strListActions != "") {
            $strReturn .= $this->objToolkit->listHeader();
            $strReturn .= $this->objToolkit->genericAdminList("", "", "", $strListActions);
            $strReturn .= $this->objToolkit->listFooter();
        }

        if (!$objArraySectionIterator->valid()) {
            $strReturn .= $this->objToolkit->getTextRow($this->getLang("commons_list_empty"));
        }

        if ($objArraySectionIterator->valid()) {
            $strReturn .= $this->objToolkit->gridHeader($bitSortable, $objArraySectionIterator->getIntElementsPerPage(), $objArraySectionIterator->getPageNumber());

            /** @var $objOneIterable Model|ModelInterface|AdminGridableInterface */
            foreach ($objArraySectionIterator as $objOneIterable) {
                if (!$objOneIterable->rightView() || !$objOneIterable instanceof AdminGridableInterface) {
                    continue;
                }

                $strActions = $this->getActionIcons($objOneIterable, $strListIdentifier);
                $strReturn .= $this->objToolkit->gridEntry($objOneIterable, $strActions, $this->renderGridEntryClickAction($objOneIterable, $strListIdentifier));
            }

            $strReturn .= $this->objToolkit->gridFooter();
        }

        $strReturn .= $this->objToolkit->getPageview($objArraySectionIterator, $this->getArrModule("modul"), $this->getAction(), "&systemid=" . $this->getSystemid() . $strPagerAddon);

        return $strReturn;
    }

    /**
     * Renders a list of items, target is the common admin-list.
     * Please be aware, that the combination of paging and sortable-lists may result in unpredictable ordering.
     * As soon as the list is sortable, the page-size should be at least the same as the number of elements. Optional
     * it is possible to provide a filter callback which is called for each entry. If the callback returns false the
     * entry gets skipped.
     *
     * @param ArraySectionIterator $objArraySectionIterator
     * @param bool $bitSortable
     * @param string $strListIdentifier an internal identifier to check the current parent-list
     * @param bool $bitAllowTreeDrop
     * @param string $strPagerAddon
     * @param Closure $objFilter
     * @param bool $addNewActions
     *
     * @throws Exception
     * @return string
     */
    final protected function renderList(ArraySectionIterator $objArraySectionIterator, $bitSortable = false, $strListIdentifier = "", $bitAllowTreeDrop = false, $strPagerAddon = "", Closure $objFilter = null, $addNewActions = true)
    {
        $strReturn = "";

        $strListId = generateSystemid();

        if (!$objArraySectionIterator->valid()) {
            $strReturn .= $this->objToolkit->getTextRow($this->getLang("commons_list_empty"));
        }

        if ($bitSortable) {
            $strReturn .= $this->objToolkit->dragableListHeader($strListId, false, $bitAllowTreeDrop, $objArraySectionIterator->getIntElementsPerPage(), $objArraySectionIterator->getPageNumber());
        } else {
            $strReturn .= $this->objToolkit->listHeader();
        }

        if ($this->renderLevelUpAction($strListIdentifier) != "") {
            $strReturn .= $this->objToolkit->genericAdminList("", "", "", $this->objToolkit->listButton($this->renderLevelUpAction($strListIdentifier)));
        }

        $arrMassActions = $this->getBatchActionHandlers($strListIdentifier);

        $intTotalNrOfElements = $objArraySectionIterator->getNumberOfElements();
        /** @var $objOneIterable Model|ModelInterface|AdminListableInterface|ModelInterface */
        foreach ($objArraySectionIterator as $objOneIterable) {
            // if we have a filter Closure call it else use the standard rightView method
            if ($objFilter !== null) {
                if ($objFilter($objOneIterable) === false) {
                    if ($bitSortable) {
                        //inject hidden dummy row for a proper sorting
                        $strReturn .= $this->objToolkit->genericAdminList($objOneIterable->getSystemid(), "", "", "", "", "", false, "hidden");
                    }
                    $intTotalNrOfElements--;
                    continue;
                }
            } elseif (!$objOneIterable->rightView()) {
                $strReturn .= $this->objToolkit->genericAdminList($objOneIterable->getSystemid(), "", AdminskinHelper::getAdminImage("icon_lockerClosed"), "");
                continue;
            }

            $strActions = $this->getActionIcons($objOneIterable, $strListIdentifier);
            $strReturn .= $this->objToolkit->simpleAdminList($objOneIterable, $strActions, count($arrMassActions) > 0);
        }

        $strNewActions = $addNewActions ? $this->mergeNewEntryActions($this->getNewEntryAction($strListIdentifier)) : "";
        $strBatchActions = "";

        if (count($arrMassActions) > 0) {
            $strBatchActions .= $this->objToolkit->renderBatchActionHandlers($arrMassActions);
        }

        if ($strNewActions != "" || $strBatchActions != "") {
            $strReturn .= $this->objToolkit->genericAdminList("batchActionSwitch", $strBatchActions, "", $strNewActions, "", "", $strBatchActions != "");
        }

        if ($bitSortable) {
            $strReturn .= $this->objToolkit->dragableListFooter($strListId);
        } else {
            $strReturn .= $this->objToolkit->listFooter();
        }

        $objArraySectionIterator->setIntTotalElements($intTotalNrOfElements);
        $strReturn .= $this->objToolkit->getPageview($objArraySectionIterator, $this->getArrModule("modul"), $this->getAction(), "&systemid=" . $this->getSystemid() . $strPagerAddon);

        return $strReturn;
    }

    /**
     * Wrapper rendering all action-icons for a given record. In most cases used to render a list-entry.
     *
     * @param Model|ModelInterface|AdminListableInterface $model
     * @param string $listIdentifier
     * @return string
     */
    public function getActionIcons($model, $listIdentifier = '')
    {
        $context = $this->modelActionContextFactory->forListIdentifier($listIdentifier);

        try {
            return $this->modelActionsRenderer->render($model, $context);
        } catch (Exception $exception) {
            return '';
        }
    }

    /**
     * Renders the action to jump a level upwards.
     * Overwrite this method if you want to provide such an action.
     *
     * @param string $strListIdentifier
     *
     * @return string
     */
    protected function renderLevelUpAction($strListIdentifier)
    {
        return "";
    }

    /**
     * For grid elements, an additional action may be defined when clicking the whole object.
     * This action is "in addition" to the action-toolbar at the bottom of the grid entry.
     * Make sure to pass a full eventhandler, e.g. onclick="document.location=''"
     * Overwrite this method if you want to provide such an action.
     *
     * @param AdminListableInterface $objOneIterable
     * @param string $strListIdentifier
     *
     * @return string
     */
    protected function renderGridEntryClickAction($objOneIterable, $strListIdentifier)
    {
        return "";
    }

    /**
     * Renders the edit action button for the current record.
     *
     * @param Model|AdminListableInterface|ModelInterface $objListEntry
     * @param bool $bitDialog opens the linked page in a js-based dialog
     * @param array $arrParams
     * @return string
     * @throws Exception
     */
    protected function renderEditAction(Model $objListEntry, $bitDialog = false, array $arrParams = null)
    {
        if ($objListEntry->getIntRecordDeleted() == 1) {
            return "";
        }

        $arrParams = $arrParams ?? [];
        $arrParams["systemid"] = $objListEntry->getSystemid();

        if ($objListEntry->rightEdit()) {
            $objLockmanager = $objListEntry->getLockManager();
            if (!$objLockmanager->isAccessibleForCurrentUser()) {
                return $this->objToolkit->listButton(AdminskinHelper::getAdminImage("icon_editLocked", $this->getLang("commons_locked")));
            }

            if ($bitDialog) {
                $arrParams["folderview"] = "1";
                return $this->objToolkit->listButton(
                    Link::getLinkAdminDialog(
                        $objListEntry->getArrModule("modul"),
                        $this->getActionNameForClass("edit", $objListEntry),
                        $arrParams,
                        $this->getLang("commons_list_edit"),
                        $this->getLang("commons_list_edit"),
                        "icon_edit",
                        $objListEntry->getStrDisplayName()
                    )
                );
            } else {
                return $this->objToolkit->listButton(
                    Link::getLinkAdmin(
                        $objListEntry->getArrModule("modul"),
                        $this->getActionNameForClass("edit", $objListEntry),
                        $arrParams,
                        $this->getLang("commons_list_edit"),
                        $this->getLang("commons_list_edit"),
                        "icon_edit"
                    )
                );
            }
        }
        return "";
    }

    /**
     * Renders the unlock action button for the current record.
     *
     * @param Model|ModelInterface $objListEntry
     *
     * @return string
     * @throws Exception
     */
    protected function renderUnlockAction(ModelInterface $objListEntry)
    {
        if ($objListEntry->getIntRecordDeleted() == 1) {
            return "";
        }

        $objLockmanager = $objListEntry->getLockManager();
        if (!$objLockmanager->isAccessibleForCurrentUser()) {
            if ($objLockmanager->isUnlockableForCurrentUser()) {
                return $this->objToolkit->listButton(
                    Link::getLinkAdmin($objListEntry->getArrModule("modul"), $this->getAction(), "&systemid=" . $this->getSystemid() . "&unlockid=" . $objListEntry->getSystemid(), "", $this->getLang("commons_unlock"), "icon_lockerOpen")
                );
            }
        }
        return "";
    }

    /**
     * Renders the delete action button for the current record.
     *
     * @param Model|ModelInterface $objListEntry
     *
     * @return string
     * @throws Exception
     */
    protected function renderDeleteAction(ModelInterface $objListEntry)
    {
        if ($objListEntry->getIntRecordDeleted() == 1) {
            return "";
        }

        if ($objListEntry->rightDelete()) {
            $objLockmanager = $objListEntry->getLockManager();
            if (!$objLockmanager->isAccessibleForCurrentUser()) {
                return $this->objToolkit->listButton(AdminskinHelper::getAdminImage("icon_deleteLocked", $this->getLang("commons_locked")));
            }

            return $this->objToolkit->listDeleteButton(
                strip_tags($objListEntry->getStrDisplayName()),
                $this->getLang($this->getObjLang()->stringToPlaceholder($this->getActionNameForClass("delete", $objListEntry) . "_question"), $objListEntry->getArrModule("modul")),
                Link::getLinkAdminHref($objListEntry->getArrModule("modul"), $this->getActionNameForClass("delete", $objListEntry), "&systemid=" . $objListEntry->getSystemid())
            );
        }
        return "";
    }

    /**
     * Renders the status action button for the current record.
     *
     * @param Model $objListEntry
     * @param string $strAltActive tooltip text for the icon if record is active
     * @param string $strAltInactive tooltip text for the icon if record is inactive
     *
     * @return string
     * @throws Exception
     */
    protected function renderStatusAction(Model $objListEntry, $strAltActive = "", $strAltInactive = "")
    {
        if ($objListEntry->getIntRecordDeleted() == 1) {
            return "";
        }

        if ($objListEntry->rightEdit()) {
            return $this->objToolkit->listStatusButton($objListEntry, false, $strAltActive, $strAltInactive);
        }
        return "";
    }

    /**
     * Renders the permissions action button for the current record.
     *
     * @param Model|ModelInterface $objListEntry
     *
     * @return string
     * @throws Exception
     */
    protected function renderPermissionsAction(Model $objListEntry)
    {
        if ($objListEntry->rightRight()) {
            return $this->objToolkit->listButton(
                Link::getLinkAdminDialog(
                    "right",
                    $this->getActionNameForClass("change", $objListEntry),
                    "&systemid=" . $objListEntry->getSystemid(),
                    "",
                    $this->getLang("commons_edit_permissions"),
                    getRightsImageAdminName($objListEntry->getSystemid()),
                    strip_tags($objListEntry->getStrDisplayName()),
                    true,
                    true
                )
            );
        }
        return "";
    }

    /**
     * Renders the icon to edit a records tags
     *
     * @param Model|ModelInterface $objListEntry
     *
     * @return string
     * @throws Exception
     */
    protected function renderTagAction(Model $objListEntry)
    {
        if ($objListEntry->getIntRecordDeleted() == 1) {
            return "";
        }

        if ($objListEntry->rightView() && SystemModule::getModuleByName("tags") !== null && SystemModule::getModuleByName("tags")->rightView()) {
            //sanitize critical chars
            $strDialogTitle = StringUtil::jsSafeString($objListEntry->getStrDisplayName());

            //the tag list is more complex and wrapped by a js-logic to load the tags by ajax afterwards
            // @codingStandardsIgnoreStart
            $strOnClick = "Folderview.dialog.setContentIFrame('" . Link::getLinkAdminHref("tags", "genericTagForm", "&systemid=" . $objListEntry->getSystemid()) . "'); Folderview.dialog.setTitle('" . $strDialogTitle . "'); Folderview.dialog.init(); return false;";
            $strLink = "<a href=\"#\" onclick=\"" . $strOnClick . "\" title=\"" . $this->getLang("commons_edit_tags") . "\" rel=\"tagtooltip\" data-systemid=\"" . $objListEntry->getSystemid() . "\">" . AdminskinHelper::getAdminImage("icon_tag", $this->getLang("commons_edit_tags"), true) . "</a>";
            // @codingStandardsIgnoreEnd
            return $this->objToolkit->listButton($strLink);
        }
        return "";
    }

    /**
     * Renders the permissions action button for the current record.
     *
     * @param Model|ModelInterface $objListEntry
     *
     * @return string
     * @throws Exception
     */
    protected function renderCopyAction(Model $objListEntry)
    {
        if ($objListEntry->getIntRecordDeleted() == 1) {
            return "";
        }

        if ($objListEntry->rightEdit()) {
            $strQuestion = $this->getLang("commons_copy_record_question", "system", array(StringUtil::jsSafeString($objListEntry->getStrDisplayName())));
            $strHref = Link::getLinkAdminHref($objListEntry->getArrModule("modul"), $this->getActionNameForClass("copyObject", $objListEntry), "&systemid=" . $objListEntry->getSystemid());
            return $this->objToolkit->listConfirmationButton($strQuestion, $strHref, "icon_copy", $this->getLang("commons_edit_copy", "system"), $this->getLang("dialog_copyHeader", "system"), $this->getLang("dialog_copyButton", "system"));
        }
        return "";
    }

    /**
     * Returns an additional set of action-buttons rendered right after the edit-action.
     *
     * @param Model $objListEntry
     *
     * @return array
     */
    protected function renderAdditionalActions(Model $objListEntry)
    {
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
     * @return string|array|MenuItem[]
     * @throws Exception
     */
    protected function getNewEntryAction($strListIdentifier, $bitDialog = false)
    {
        $objObject = null;
        if (validateSystemid($this->getSystemid())) {
            $objObject = $this->objFactory->getObject($this->getSystemid());
        }
        if ($objObject == null) {
            $objObject = $this->getObjModule();
        }

        if ($objObject->rightEdit()) {
            if ($bitDialog) {
                return $this->objToolkit->listButton(Link::getLinkAdminDialog($this->getArrModule("modul"), $this->getActionNameForClass("new", null), "&folderview=1&systemid=" . $this->getSystemid(), $this->getLang("commons_list_new"), $this->getLang("commons_list_new"), "icon_new"));
            } else {
                return $this->objToolkit->listButton(Link::getLinkAdmin($this->getArrModule("modul"), $this->getActionNameForClass("new", null), "&systemid=" . $this->getSystemid(), $this->getLang("commons_list_new"), $this->getLang("commons_list_new"), "icon_new"));
            }
        }
        return "";
    }

    /**
     * If multiple new actions are given, all buttons are merged into a new, single button.
     * The button itself is rendering the different buttons using a new dropdown-menu.
     *
     * @param array $arrActions
     *
     * @return string
     */
    private function mergeNewEntryActions($arrActions)
    {
        if (!is_array($arrActions)) {
            return $arrActions;
        }

        if (is_array($arrActions) && count($arrActions) == 0) {
            return "";
        }

        //create a menu and merge all buttons
        $menu = new Menu();
        foreach ($arrActions as $strOneAction) {
            if ($strOneAction instanceof MenuItem) {
                $menu->addItem($strOneAction);
            } else {
                $strOneAction = trim($strOneAction);

                //extract a possible icon
                $iStart = StringUtil::indexOf($strOneAction, "<i");
                $icon = "";
                if ($iStart !== false) {
                    $icon = StringUtil::substring($strOneAction, $iStart, StringUtil::lastIndexOf($strOneAction, "</i>") - $iStart + 4);
                    $strOneAction = StringUtil::replace($icon, "", $strOneAction);

                    $icon = $this->objToolkit->listButton($icon);
                }

                //search for a title attribute
                $arrMatchesLink = array();
                if (preg_match('/<a.*?title=(["\'])(.*?)\1.*$/i', $strOneAction, $arrMatchesLink)) {
                    if (StringUtil::substring($strOneAction, -11) == "</a></span>") {
                        $strOneAction = StringUtil::substring($strOneAction, 0, -11) . $icon . $arrMatchesLink[2] . "</a></span>";
                    } else {
                        $strOneAction .= $icon . $arrMatchesLink[2];
                    }
                }

                //strip a possible span at the beginning
                if ($icon != "" && StringUtil::startsWith($strOneAction, "<span")) {
                    $strOneAction = StringUtil::substring($strOneAction, StringUtil::indexOf($strOneAction, ">") + 1, -7);
                }

                $menu->addItem(new Text($strOneAction));
            }
        }


        $dd = new Dropdownmenu(AdminskinHelper::getAdminImage("icon_new_multi"), $menu);

        return $this->objToolkit->listButton(
            $dd->renderComponent()
        );
    }

    /**
     * Overwrite this method if you want to provide a handler for a mass-action.
     * If one or more handler(s) are returned, the checkboxes to select a list of records
     * are rendered.
     *
     * @param string $strListIdentifier
     *
     * @return AdminBatchaction[]
     */
    protected function getBatchActionHandlers($strListIdentifier)
    {
        return array();
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getDefaultActionHandlers()
    {
        $arrReturn = array();
        if ($this->getObjModule()->rightDelete()) {
            $arrReturn[] = new AdminBatchaction(AdminskinHelper::getAdminImage("icon_delete"), Link::getLinkAdminXml("system", "delete", "&systemid=%systemid%"), $this->getLang("commons_batchaction_delete"));
        }

        if ($this->getObjModule()->rightEdit()) {
            $arrReturn[] = new AdminBatchaction(AdminskinHelper::getAdminImage("icon_enabled"), Link::getLinkAdminXml("system", "setStatus", "&systemid=%systemid%&status=1"), $this->getLang("commons_batchaction_enable"));
            $arrReturn[] = new AdminBatchaction(AdminskinHelper::getAdminImage("icon_disabled"), Link::getLinkAdminXml("system", "setStatus", "&systemid=%systemid%&status=0"), $this->getLang("commons_batchaction_disable"));
        }
        return $arrReturn;
    }

    /**
     * Renders the button to open the records' change history. In most cases, this is done in a overlay.
     * To open the change-history, the permission "right3" on the system-module is required.
     *
     * @param Model|ModelInterface $objListEntry
     *
     * @return string
     * @throws Exception
     */
    protected function renderChangeHistoryAction(Model $objListEntry)
    {
        if (SystemSetting::getConfigValue("_system_changehistory_enabled_") == "true" && $objListEntry instanceof VersionableInterface && $objListEntry->rightChangelog()) {
            return $this->objToolkit->listButton(
                Link::getLinkAdminDialog(
                    "system",
                    "genericChangelog",
                    "&systemid=" . $objListEntry->getSystemid() . "&folderview=1",
                    $this->getLang("commons_edit_history"),
                    $this->getLang("commons_edit_history"),
                    "icon_history",
                    $objListEntry->getStrDisplayName()
                )
            );
        }
        return "";
    }

    /**
     * @param string $strPeAddon
     *
     * @return void
     * @deprecated
     */
    public function setStrPeAddon($strPeAddon)
    {
        $this->strPeAddon = $strPeAddon;
    }

    /**
     * @return string
     * @deprecated
     */
    public function getStrPeAddon()
    {
        return $this->strPeAddon;
    }

}
