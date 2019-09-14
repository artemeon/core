<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *    $Id$                                *
 ********************************************************************************************************/

namespace Kajona\System\Admin;

use AGP\Phpexcel\System\PhpexcelDataTableExporter;
use Kajona\Packagemanager\System\PackagemanagerManager;
use Kajona\System\Admin\Formentries\FormentryText;
use Kajona\System\Admin\Formentries\FormentryTextarea;
use Kajona\System\Admin\Formentries\FormentryUser;
use Kajona\System\Admin\Systemtasks\AdminSystemtaskInterface;
use Kajona\System\Admin\Systemtasks\SystemtaskBase;
use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\ArraySectionIterator;
use Kajona\System\System\Carrier;
use Kajona\System\System\ChangelogContainer;
use Kajona\System\System\Date;
use Kajona\System\System\Exception;
use Kajona\System\System\Filesystem;
use Kajona\System\System\Filters\DeletedRecordsFilter;
use Kajona\System\System\HttpStatuscodes;
use Kajona\System\System\Lang;
use Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException;
use Kajona\System\System\Link;
use Kajona\System\System\Lockmanager;
use Kajona\System\System\Logger;
use Kajona\System\System\Mail;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\OrmBase;
use Kajona\System\System\OrmDeletedhandlingEnum;
use Kajona\System\System\Pluginmanager;
use Kajona\System\System\Reflection;
use Kajona\System\System\RequestEntrypointEnum;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\Rights;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemChangelog;
use Kajona\System\System\SystemChangelogRestorer;
use Kajona\System\System\SystemCommon;
use Kajona\System\System\SysteminfoInterface;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSession;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserGroup;
use Kajona\System\System\Validators\EmailValidator;
use Kajona\System\System\VersionableInterface;
use Kajona\System\View\Components\Formentry\Datesingle\Datesingle;
use Kajona\System\View\Components\Formentry\Datetime\Datetime;
use Kajona\System\View\Components\Formentry\Buttonbar\Buttonbar;
use Kajona\System\View\Components\Formentry\Dropdown\Dropdown;
use Kajona\System\View\Components\Formentry\Inputcheckbox\Inputcheckbox;
use Kajona\System\View\Components\Formentry\Inputcolorpicker\Inputcolorpicker;
use Kajona\System\View\Components\Formentry\Inputonoff\Inputonoff;
use Kajona\System\View\Components\Formentry\Inputtext\Inputtext;
use Kajona\System\View\Components\Formentry\Listeditor\Listeditor;
use Kajona\System\View\Components\Formentry\Objectlist\Objectlist;
use Kajona\System\View\Components\Formentry\Objectselector\Objectselector;
use Kajona\System\View\Components\Formentry\Radiogroup\Radiogroup;

/**
 * Class to handle infos about the system and to set systemwide properties
 *
 * @package module_system
 * @author sidler@mulchprod.de
 *
 * @module system
 * @moduleId _system_modul_id_
 *
 * @objectListAspect Kajona\System\System\SystemAspect
 * @objectEditAspect Kajona\System\System\SystemAspect
 * @objectNewAspect Kajona\System\System\SystemAspect
 *
 * @autoTestable listAspect
 */
class SystemAdmin extends AdminEvensimpler implements AdminInterface
{

    /**
     * @return array
     */
    public function getOutputModuleNavi()
    {
        $arrReturn = array();
        $arrReturn[] = array("view", Link::getLinkAdmin($this->getArrModule("modul"), "list", "", $this->getLang("action_list")));
        $arrReturn[] = array("edit", Link::getLinkAdmin($this->getArrModule("modul"), "systemInfo", "", $this->getLang("action_system_info")));
        $arrReturn[] = array("right1", Link::getLinkAdmin($this->getArrModule("modul"), "systemSettings", "", $this->getLang("action_system_settings")));
        $arrReturn[] = array("right2", Link::getLinkAdmin($this->getArrModule("modul"), "systemTasks", "", $this->getLang("action_system_tasks")));
        $arrReturn[] = array("right3", Link::getLinkAdmin($this->getArrModule("modul"), "systemlog", "", $this->getLang("action_systemlog")));
        if (SystemSetting::getConfigValue("_system_changehistory_enabled_") != "false") {
            $arrReturn[] = array("right3", Link::getLinkAdmin($this->getArrModule("modul"), "genericChangelog", "&bitBlockFolderview=true", $this->getLang("action_changelog")));
        }
        $arrReturn[] = array("right5", Link::getLinkAdmin($this->getArrModule("modul"), "listAspect", "", $this->getLang("action_list_aspect")));
        $arrReturn[] = array("right1", Link::getLinkAdmin($this->getArrModule("modul"), "systemSessions", "", $this->getLang("action_system_sessions")));
        $arrReturn[] = array("right1", Link::getLinkAdmin($this->getArrModule("modul"), "lockedRecords", "", $this->getLang("action_locked_records")));
        $arrReturn[] = array("right1", Link::getLinkAdmin($this->getArrModule("modul"), "deletedRecords", "", $this->getLang("action_deleted_records")));
        $arrReturn[] = array("", "");
        //$arrReturn[] = array("", Link::getLinkAdmin($this->getArrModule("modul"), "about", "", $this->getLang("action_about")));
        $arrReturn[] = array("", "");
        $arrReturn[] = array("right", Link::getLinkAdmin("right", "change", "&systemid=0", $this->getLang("modul_rechte_root")));
        return $arrReturn;
    }

    /**
     * Sets the status of a module.
     * Therefore you have to be member of the admin-group.
     *
     * @permissions edit
     */
    protected function actionModuleStatus()
    {
        //status: for setting the status of modules, you have to be member of the admin-group
        $objModule = new SystemModule($this->getSystemid());
        if ($objModule->rightEdit() && Carrier::getInstance()->getObjSession()->isSuperAdmin()) {
            $objModule->setIntRecordStatus($objModule->getIntRecordStatus() == 0 ? 1 : 0);
            $this->objLifeCycleFactory->factory(get_class($objModule))->update($objModule);
            $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul")));
        }
    }

    /**
     * Renders the form to edit an existing entry
     *
     * @permissions edit
     * @return string
     * @throws Exception
     */
    protected function actionEdit()
    {

        $objInstance = Objectfactory::getInstance()->getObject($this->getSystemid());
        if ($objInstance instanceof SystemAspect) {
            $this->setStrCurObjectTypeName("Aspect");
            $this->setCurObjectClassName(SystemAspect::class);
            return parent::actionEdit();
        }

        if ($objInstance instanceof SystemModule) {
            $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "list"));
        }
    }

    /**
     * Creates a list of all installed modules
     *
     * @return string
     * @permissions view
     * @autoTestable
     * @throws Exception
     */
    protected function actionList()
    {
        if ($this->getParam("action") == "listAspect") {
            $this->setStrCurObjectTypeName("Aspect");
            $this->setCurObjectClassName(SystemAspect::class);
            return parent::actionList();
        }

        $objIterator = new ArraySectionIterator(SystemModule::getObjectCountFiltered());
        $objIterator->setPageNumber($this->getParam("pv"));
        $objIterator->setArraySection(SystemModule::getAllModules($objIterator->calculateStartPos(), $objIterator->calculateEndPos()));

        return $this->renderList($objIterator, true, "moduleList");

    }

    /**
     * @param Model $objListEntry
     *
     * @return array
     * @throws Exception
     */
    protected function renderAdditionalActions(Model $objListEntry)
    {
        if ($objListEntry instanceof SystemModule) {
            $arrReturn = array();
            $arrReturn[] = $this->objToolkit->listButton(Link::getLinkAdminDialog("system", "moduleAspect", "&systemid=" . $objListEntry->getSystemid(), "", $this->getLang("modul_aspectedit"), "icon_aspect", $this->getLang("modul_aspectedit")));

            if ($objListEntry->rightEdit() && Carrier::getInstance()->getObjSession()->isSuperAdmin()) {
                if ($objListEntry->getStrName() == "system") {
                    $arrReturn[] = $this->objToolkit->listButton(Link::getLinkAdmin("system", "moduleList", "", "", $this->getLang("modul_status_system"), "icon_enabled"));
                } elseif ($objListEntry->getIntRecordStatus() == 0) {
                    $arrReturn[] = $this->objToolkit->listButton(Link::getLinkAdmin("system", "moduleStatus", "&systemid=" . $objListEntry->getSystemid(), "", $this->getLang("modul_status_disabled"), "icon_disabled"));
                } else {
                    $arrReturn[] = $this->objToolkit->listButton(Link::getLinkAdmin("system", "moduleStatus", "&systemid=" . $objListEntry->getSystemid(), "", $this->getLang("modul_status_enabled"), "icon_enabled"));
                }
            }

            return $arrReturn;
        }
        return parent::renderAdditionalActions($objListEntry);
    }

    /**
     * @param Model $objListEntry
     * @param string $strAltActive tooltip text for the icon if record is active
     * @param string $strAltInactive tooltip text for the icon if record is inactive
     *
     * @return string
     */
    protected function renderStatusAction(Model $objListEntry, $strAltActive = "", $strAltInactive = "")
    {
        if ($objListEntry instanceof SystemModule) {
            return "";
        }

        return parent::renderStatusAction($objListEntry, $strAltActive, $strAltInactive);
    }

    /**
     * @param Model $objListEntry
     * @param bool $bitDialog
     *
     * @param array $arrParams
     * @return string
     * @throws Exception
     */
    protected function renderEditAction(Model $objListEntry, $bitDialog = false, array $arrParams = null)
    {
        if ($objListEntry instanceof SystemModule) {
            return "";
        }

        return parent::renderEditAction($objListEntry, $bitDialog, $arrParams);
    }

    /**
     * @param ModelInterface $objListEntry
     *
     * @return string
     */
    protected function renderDeleteAction(ModelInterface $objListEntry)
    {
        if ($objListEntry instanceof SystemModule) {
            return "";
        }

        return parent::renderDeleteAction($objListEntry);
    }

    /**
     * @param string $strListIdentifier
     * @param bool $bitDialog
     *
     * @return array|string
     */
    protected function getNewEntryAction($strListIdentifier, $bitDialog = false)
    {
        if ($strListIdentifier == "moduleList") {
            return "";
        }

        return parent::getNewEntryAction($strListIdentifier);
    }

    /**
     * @param Model $objListEntry
     *
     * @return string
     */
    protected function renderCopyAction(Model $objListEntry)
    {
        return "";
    }

    /**
     * Creates the form to manipulate the aspects of a single module
     *
     * @return string
     * @permissions right5
     */
    protected function actionModuleAspect()
    {
        $strReturn = "";
        $objModule = SystemModule::getModuleBySystemid($this->getSystemid());
        $strReturn .= $this->objToolkit->formHeadline($objModule->getStrName());
        $arrAspectsSet = explode(",", $objModule->getStrAspect());
        $strReturn .= $this->objToolkit->formHeader(Link::getLinkAdminHref($this->getArrModule("modul"), "saveModuleAspect"));
        $arrAspects = SystemAspect::getObjectListFiltered();
        foreach ($arrAspects as $objOneAspect) {
            $strReturn .= $this->objToolkit->formInputCheckbox("aspect_" . $objOneAspect->getSystemid(), $objOneAspect->getStrName(), in_array($objOneAspect->getSystemid(), $arrAspectsSet));
        }

        $strReturn .= $this->objToolkit->formInputHidden("systemid", $this->getSystemid());
        $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
        $strReturn .= $this->objToolkit->formClose();

        return $strReturn;
    }

    /**
     * @return string
     * @permissions right5
     * @throws ServiceLifeCycleUpdateException
     */
    protected function actionSaveModuleAspect()
    {
        $arrParams = array();
        foreach ($this->getAllParams() as $strName => $intValue) {
            if (StringUtil::indexOf($strName, "aspect_") !== false) {
                $arrParams[] = StringUtil::substring($strName, 7);
            }
        }

        $objModule = SystemModule::getModuleBySystemid($this->getSystemid());
        $objModule->setStrAspect(implode(",", $arrParams));

        $this->objLifeCycleFactory->factory(get_class($objModule))->update($objModule);
        $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "list", "peClose=1&blockAction=1"));
    }

    /**
     * Shows information about the current system
     *
     * @return string
     * @permissions edit
     */
    protected function actionSystemInfo()
    {
        $strReturn = "";

        $objPluginmanager = new Pluginmanager(SysteminfoInterface::STR_EXTENSION_POINT);
        /** @var SysteminfoInterface[] $arrPlugins */
        $arrPlugins = $objPluginmanager->getPlugins();

        foreach ($arrPlugins as $objOnePlugin) {
            $strContent = $this->objToolkit->dataTable(array(), $objOnePlugin->getArrContent());
            $strReturn .= $this->objToolkit->getFieldset($objOnePlugin->getStrTitle(), $strContent);
        }

        return $strReturn;
    }

    // -- SystemSettings ------------------------------------------------------------------------------------

    /**
     * Creates a form to edit systemsettings or updates them
     *
     * @return string "" in case of success
     * @autoTestable
     * @permissions right1
     * @throws Exception
     */
    protected function actionSystemSettings()
    {
        $strReturn = "";
        //Create a warning before doing s.th.
        $strReturn .= $this->objToolkit->warningBox($this->getLang("warnung_settings"));

        $arrTabs = array();

        $arrSettings = SystemSetting::getObjectListFiltered();
        /** @var SystemModule $objCurrentModule */
        $objCurrentModule = null;
        $strRows = "";
        foreach ($arrSettings as $objOneSetting) {
            if ($objCurrentModule === null || $objCurrentModule->getIntNr() != $objOneSetting->getIntModule()) {
                $objTemp = $this->getModuleDataID($objOneSetting->getIntModule(), true);
                if ($objTemp !== null) {
                    //In the first loop, ignore the output
                    if ($objCurrentModule !== null) {
                        //Build a form to return
                        $strTabContent = $this->objToolkit->formHeader(Link::getLinkAdminHref($this->getArrModule("modul"), "systemSettings"));
                        $strTabContent .= $strRows;
                        $strTabContent .= $this->objToolkit->formClose();
                        $arrTabs[$this->getLang("modul_titel", $objCurrentModule->getStrName())] = $strTabContent;
                    }
                    $strRows = "";
                    $objCurrentModule = $objTemp;
                }
            }
            //Build the rows
            //Print a help-text?
            $strHelper = $this->getLang($objOneSetting->getStrName() . "hint", $objCurrentModule->getStrName());
            if ($strHelper != "!" . $objOneSetting->getStrName() . "hint!") {
                $strRows .= $this->objToolkit->formTextRow($strHelper);
            }

            //The input element itself
            if ($objOneSetting->getIntType() == 0) {
                $arrDD = array();
                $arrDD["true"] = $this->getLang("commons_yes");
                $arrDD["false"] = $this->getLang("commons_no");
                $strRows .= $this->objToolkit->formInputDropdown("set[" . $objOneSetting->getSystemid() . "]", $arrDD, $this->getLang($objOneSetting->getStrName(), $objCurrentModule->getStrName()), $objOneSetting->getStrValue(), "", true, "", "", "", $objOneSetting->getSystemid() . "#strValue");
            } elseif ($objOneSetting->getIntType() == 3) {
                $strRows .= $this->objToolkit->formInputPageSelector("set[" . $objOneSetting->getSystemid() . "]", $this->getLang($objOneSetting->getStrName(), $objCurrentModule->getStrName()), $objOneSetting->getStrValue(), "", false, true, "", $objOneSetting->getSystemid() . "#strValue");
            } else {
                $strRows .= $this->objToolkit->formInputText("set[" . $objOneSetting->getSystemid() . "]", $this->getLang($objOneSetting->getStrName(), $objCurrentModule->getStrName()), $objOneSetting->getStrValue(), "", "", false, $objOneSetting->getSystemid() . "#strValue");
            }
        }
        //Build a form to return -> include the last module
        $strTabContent = $this->objToolkit->formHeader(Link::getLinkAdminHref($this->getArrModule("modul"), "systemSettings"));
        $strTabContent .= $strRows;
        $strTabContent .= $this->objToolkit->formClose();

        $arrTabs[$this->getLang("modul_titel", $objCurrentModule->getStrName())] = $strTabContent;

        $strReturn .= $this->objToolkit->getTabbedContent($arrTabs);

        $strReturn .= "<script type='text/javascript'>InstantSave.init()</script>";

        return $strReturn;
    }

    /**
     * Loads the list of all systemtasks available and creates the form required to trigger a task
     *
     * @return string
     * @autoTestable
     * @permissions right2
     */
    protected function actionSystemTasks()
    {
        $strReturn = "";
        $strTaskOutput = "";

        //include the list of possible tasks
        $arrFiles = SystemtaskBase::getAllSystemtasks();

        //react on special task-commands?
        if ($this->getParam("task") != "") {
            //search for the matching task
            /** @var $objTask SystemtaskBase */
            foreach ($arrFiles as $objTask) {
                if ($objTask->getStrInternalTaskname() == $this->getParam("task")) {
                    $strTaskOutput .= self::getTaskDialogExecuteCode($this->getParam("execute") == "true", $objTask, "system", "systemTasks", $this->getParam("executedirectly") == "true");
                    break;
                }
            }
        }

        //loop over the found files and group them
        $arrTaskGroups = array();
        /** @var AdminSystemtaskInterface|SystemtaskBase $objTask */
        foreach ($arrFiles as $objTask) {
            if (!isset($arrTaskGroups[$objTask->getGroupIdentifier()])) {
                $arrTaskGroups[$objTask->getGroupIdentifier()] = array();
            }

            $arrTaskGroups[$objTask->getGroupIdentifier()][] = $objTask;
        }

        ksort($arrTaskGroups);

        foreach ($arrTaskGroups as $strGroupName => $arrTasks) {
            if ($strGroupName == "") {
                $strGroupName = "default";
            }

            $strReturn .= $this->objToolkit->formHeadline($this->getLang("systemtask_group_" . $strGroupName));
            $strReturn .= $this->objToolkit->listHeader();
            /** @var $objOneTask AdminSystemtaskInterface */
            foreach ($arrTasks as $objOneTask) {
                //generate the link to execute the task
                $strLink = Link::getLinkAdmin(
                    "system",
                    "systemTasks",
                    "&task=" . $objOneTask->getStrInternalTaskName(),
                    $objOneTask->getStrTaskname(),
                    $this->getLang("systemtask_run"),
                    "icon_accept"
                );

                $strReturn .= $this->objToolkit->genericAdminList(
                    generateSystemid(),
                    $objOneTask->getStrTaskname(),
                    AdminskinHelper::getAdminImage("icon_systemtask"),
                    $this->objToolkit->listButton($strLink)
                );
            }
            $strReturn .= $this->objToolkit->listFooter();
        }

        //include js-code & stuff to handle executions
        $strReturn .= self::getTaskDialogCode();
        $strReturn = $strTaskOutput . $strReturn;

        return $strReturn;
    }

    /**
     * Renders the code to run and execute a systemtask. You only need this if you want to provide the user an additional place
     * to run a systemtask besides the common place at module system / admin.
     *
     * @param bool $bitExecute
     * @param SystemtaskBase $objTask
     * @param string $strModule
     * @param string $strAction
     * @param bool $bitExecuteDirectly
     *
     * @return string
     * @throws Exception
     */
    public static function getTaskDialogExecuteCode($bitExecute, SystemtaskBase $objTask, $strModule = "", $strAction = "", $bitExecuteDirectly = false)
    {
        $objLang = Carrier::getInstance()->getObjLang();
        $strTaskOutput = "";

        //If the task is going to be executed, validate the form first (if form is of type AdminFormgenerator)
        $objAdminForm = null;
        if ($bitExecute) {
            $objAdminForm = $objTask->getAdminForm();
            if ($objAdminForm !== null && $objAdminForm instanceof AdminFormgenerator) {
                if (!$objAdminForm->validateForm()) {
                    $bitExecute = false;
                }
            }
        }

        //execute the task or show the form?
        if ($bitExecute) {
            if ($bitExecuteDirectly) {
                $strTaskOutput .= $objTask->executeTask();
            } else {
                $strTaskOutput .= "
                <script type=\"text/javascript\">
                SystemTask.executeTask('" . $objTask->getStrInternalTaskname() . "', '" . $objTask->getSubmitParams() . "');
                SystemTask.setName('" . $objLang->getLang("systemtask_runningtask", "system") . " " . $objTask->getStrTaskName() . "');
                </script>";
            }
        } else {
            $strForm = $objTask->generateAdminForm($strModule, $strAction, $objAdminForm);
            if ($strForm != "") {
                $strTaskOutput .= $strForm;
            } else {
                $strLang = Carrier::getInstance()->getObjLang()->getLang("systemtask_runningtask", "system");
                $strTaskJS = <<<JS
                SystemTask.executeTask('{$objTask->getStrInternalTaskName()}', '');
                SystemTask.setName('{$strLang} {$objTask->getStrTaskName()}');
JS;
                $strTaskOutput .= "<script type='text/javascript'>" . $strTaskJS . "</script>";
            }
        }

        return $strTaskOutput;
    }

    /**
     * Renders the code to wrap systemtask
     *
     * @return string
     */
    public static function getTaskDialogCode()
    {
        $objLang = Carrier::getInstance()->getObjLang();
        $strDialogContent = "<div id=\"systemtaskLoadingDiv\" class=\"loadingContainer loadingContainerBackground\"></div><br /><b id=\"systemtaskNameDiv\"></b><br /><br /><div id=\"systemtaskStatusDiv\"></div><br /><input id=\"systemtaskCancelButton\" type=\"submit\" value=\"" . $objLang->getLang("systemtask_cancel_execution", "system") . "\" class=\"btn inputSubmit\" /><br />";
        return "<script type=\"text/javascript\">
            var KAJONA_SYSTEMTASK_TITLE = '" . $objLang->getLang("systemtask_dialog_title", "system") . "';
            var KAJONA_SYSTEMTASK_TITLE_DONE = '" . $objLang->getLang("systemtask_dialog_title_done", "system") . "';
            var KAJONA_SYSTEMTASK_CLOSE = '" . $objLang->getLang("systemtask_close_dialog", "system") . "';
            var kajonaSystemtaskDialogContent = '" . $strDialogContent . "';
            </script>";
    }

    /**
     * Renders a list of records currently locked
     *
     * @permissions right1
     * @return string
     * @autoTestable
     */
    protected function actionLockedRecords()
    {
        $objArraySectionIterator = new ArraySectionIterator(Lockmanager::getLockedRecordsCount());
        $objArraySectionIterator->setPageNumber((int) ($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(Lockmanager::getLockedRecords($objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

        $strReturn = "";
        if (!$objArraySectionIterator->valid()) {
            $strReturn .= $this->getLang("commons_list_empty");
        }

        $strReturn .= $this->objToolkit->listHeader();

        foreach ($objArraySectionIterator as $objOneRecord) {
            $strImage = "";
            if ($objOneRecord instanceof AdminListableInterface) {
                $strImage = $objOneRecord->getStrIcon();
                if (is_array($strImage)) {
                    $strImage = AdminskinHelper::getAdminImage($strImage[0], $strImage[1]);
                } else {
                    $strImage = AdminskinHelper::getAdminImage($strImage);
                }
            }

            $strActions = $this->objToolkit->listButton(Link::getLinkAdmin($this->getArrModule("modul"), "lockedRecords", "&unlockid=" . $objOneRecord->getSystemid(), $this->getLang("action_unlock_record"), $this->getLang("action_unlock_record"), "icon_lockerOpen"));
            $objLockUser = Objectfactory::getInstance()->getObject($objOneRecord->getLockManager()->getLockId());

            $strReturn .= $this->objToolkit->genericAdminList(
                $objOneRecord->getSystemid(),
                $objOneRecord instanceof ModelInterface ? $objOneRecord->getStrDisplayName() : get_class($objOneRecord),
                $strImage,
                $strActions,
                get_class($objOneRecord),
                $this->getLang("locked_record_info", array(dateToString(new Date($objOneRecord->getIntLockTime())), $objLockUser->getStrDisplayName()))
            );
        }

        $strReturn .= $this->objToolkit->listFooter();

        $strReturn .= $this->objToolkit->getPageview($objArraySectionIterator, "system", "lockedRecords");

        return $strReturn;
    }

    /**
     * Renders a list of logically deleted records
     *
     * @permissions right1
     * @return string
     * @autoTestable
     * @throws Exception
     */
    protected function actionDeletedRecords()
    {

        $strReturn = "";
        /** @var  DeletedRecordsFilter $objFilter */
        $objFilter = DeletedRecordsFilter::getOrCreateFromSession();
        $strFilterForm = $this->renderFilter($objFilter);
        if ($strFilterForm === AdminFormgeneratorFilter::STR_FILTER_REDIRECT) {
            return "";
        }
        $strReturn .= $strFilterForm;

        $objArraySectionIterator = new ArraySectionIterator(DeletedRecordsFilter::getDeletedRecordsCount($objFilter));
        $objArraySectionIterator->setPageNumber((int) ($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(DeletedRecordsFilter::getDeletedRecords($objFilter, $objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

        if (!$objArraySectionIterator->valid()) {
            $strReturn .= $this->getLang("commons_list_empty");
        }

        $strReturn .= $this->objToolkit->listHeader();

        /** @var Model $objOneRecord */
        foreach ($objArraySectionIterator as $objOneRecord) {
            $strImage = "";
            if ($objOneRecord instanceof AdminListableInterface) {
                $strImage = $objOneRecord->getStrIcon();
                if (is_array($strImage)) {
                    $strImage = AdminskinHelper::getAdminImage($strImage[0], $strImage[1]);
                } else {
                    $strImage = AdminskinHelper::getAdminImage($strImage);
                }
            }

            $strActions = "";
            if ($objOneRecord->rightDelete()) {
                $strActions .= $this->objToolkit->listButton(
                    Link::getLinkAdmin($this->getArrModule("modul"), "finalDeleteRecord", "&systemid=" . $objOneRecord->getSystemid(), $this->getLang("action_final_delete_record"), $this->getLang("action_final_delete_record"), "icon_delete")
                );
            }

            if ($objOneRecord->isRestorable()) {
                $strActions .= $this->objToolkit->listButton(
                    Link::getLinkAdmin($this->getArrModule("modul"), "restoreRecord", "&systemid=" . $objOneRecord->getSystemid(), $this->getLang("action_restore_record"), $this->getLang("action_restore_record"), "icon_undo")
                );
            } else {
                $strActions .= $this->objToolkit->listButton(AdminskinHelper::getAdminImage("icon_undoDisabled", $this->getLang("action_restore_record_blocked")));
            }

            $strReturn .= $this->objToolkit->genericAdminList(
                $objOneRecord->getSystemid(),
                $objOneRecord instanceof ModelInterface ? $objOneRecord->getStrDisplayName() : get_class($objOneRecord),
                $strImage,
                $strActions,
                "Systemid / Previd: " . $objOneRecord->getStrSystemid() . " / " . $objOneRecord->getStrPrevId()
            );
        }

        $strReturn .= $this->objToolkit->listFooter();

        $strReturn .= $this->objToolkit->getPageview($objArraySectionIterator, "system", "deletedRecords");

        return $strReturn;
    }

    /**
     * Restores a single object
     *
     * @permissions right1
     * @return string
     * @throws Exception
     */
    protected function actionRestoreRecord()
    {
        OrmBase::setObjHandleLogicalDeletedGlobal(OrmDeletedhandlingEnum::INCLUDED);
        $objRecord = Objectfactory::getInstance()->getObject($this->getSystemid());
        if ($objRecord !== null && !$objRecord->isRestorable()) {
            throw new Exception("Record is not restoreable", Exception::$level_ERROR);
        }

        $this->objLifeCycleFactory->factory(get_class($objRecord))->restore($objRecord);

        $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "deletedRecords"));
        return "";
    }

    /**
     * Restores a single object
     *
     * @permissions right1,delete
     * @return string
     * @throws Exception
     */
    protected function actionFinalDeleteRecord()
    {
        if ($this->getParam("delete") == "") {
            OrmBase::setObjHandleLogicalDeletedGlobal(OrmDeletedhandlingEnum::INCLUDED);
            $objRecord = Objectfactory::getInstance()->getObject($this->getSystemid());
            $strReturn = $this->objToolkit->formHeader(Link::getLinkAdminHref($this->getArrModule("modul"), "finalDeleteRecord"));
            $strReturn .= $this->objToolkit->warningBox($this->getLang("final_delete_question", array($objRecord->getStrDisplayName())), "alert-danger");
            $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("final_delete_submit"));
            $strReturn .= $this->objToolkit->formInputHidden("systemid", $this->getParam("systemid"));
            $strReturn .= $this->objToolkit->formInputHidden("delete", "1");
            $strReturn .= $this->objToolkit->formClose();
            return $strReturn;
        } else {
            OrmBase::setObjHandleLogicalDeletedGlobal(OrmDeletedhandlingEnum::INCLUDED);
            $objRecord = Objectfactory::getInstance()->getObject($this->getSystemid());
            if ($objRecord !== null && !$objRecord->rightDelete()) {
                throw new Exception($this->getLang("commons_error_permissions"), Exception::$level_ERROR);
            }

            $this->objLifeCycleFactory->factory(get_class($objRecord))->deleteObjectFromDatabase($objRecord);

            $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "deletedRecords"));
        }
        return "";
    }

    /**
     * Creates a table filled with the sessions currently registered
     *
     * @autoTestable
     * @return string
     * @permissions right1
     * @throws ServiceLifeCycleUpdateException
     */
    protected function actionSystemSessions()
    {
        $strReturn = "";
        //react on commands?
        if ($this->getParam("logout") == "true") {
            $objSession = new SystemSession($this->getSystemid());
            $objSession->setStrLoginstatus(SystemSession::$LOGINSTATUS_LOGGEDOUT);
            $this->objLifeCycleFactory->factory(get_class($objSession))->update($objSession);
            Carrier::getInstance()->getObjDB()->flushQueryCache();
        }

        //showing a list using the pageview
        $objArraySectionIterator = new ArraySectionIterator(SystemSession::getNumberOfActiveSessions());
        $objArraySectionIterator->setPageNumber((int) ($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(SystemSession::getAllActiveSessions($objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

        $arrData = array();
        $arrHeader = array();
        $arrHeader[0] = "";
        $arrHeader[1] = $this->getLang("session_username");
        $arrHeader[2] = $this->getLang("session_valid");
        $arrHeader[3] = $this->getLang("session_status");
        $arrHeader[4] = $this->getLang("session_activity");
        $arrHeader[5] = "";
        /** @var $objOneSession SystemSession */
        foreach ($objArraySectionIterator as $objOneSession) {
            $arrRowData = array();
            $strUsername = "";
            if ($objOneSession->getStrUserid() != "") {
                $objUser = Objectfactory::getInstance()->getObject($objOneSession->getStrUserid());
                $strUsername = $objUser->getStrUsername();
            }
            $arrRowData[0] = AdminskinHelper::getAdminImage("icon_user");
            $arrRowData[1] = $strUsername;
            $arrRowData[2] = timeToString($objOneSession->getIntReleasetime());
            if ($objOneSession->getStrLoginstatus() == SystemSession::$LOGINSTATUS_LOGGEDIN) {
                $arrRowData[3] = $this->getLang("session_loggedin");
            } else {
                $arrRowData[3] = $this->getLang("session_loggedout");
            }

            //find out what the user is doing...
            $strLastUrl = $objOneSession->getStrLasturl();
            if (StringUtil::indexOf($strLastUrl, "?") !== false) {
                $strLastUrl = StringUtil::substring($strLastUrl, StringUtil::indexOf($strLastUrl, "?"));
            }
            $strActivity = "";

            $strActivity .= $this->getLang("session_admin");
            foreach (explode("&amp;", $strLastUrl) as $strOneParam) {
                $arrUrlParam = explode("=", $strOneParam);
                if ($arrUrlParam[0] == "module") {
                    $strActivity .= $arrUrlParam[1];
                }
            }

            $arrRowData[4] = $strActivity;
            if ($objOneSession->getStrLoginstatus() == SystemSession::$LOGINSTATUS_LOGGEDIN) {
                $arrRowData[5] = Link::getLinkAdmin("system", "systemSessions", "&logout=true&systemid=" . $objOneSession->getSystemid(), "", $this->getLang("session_logout"), "icon_delete");
            } else {
                $arrRowData[5] = AdminskinHelper::getAdminImage("icon_deleteDisabled");
            }
            $arrData[] = $arrRowData;
        }
        $strReturn .= $this->objToolkit->dataTable($arrHeader, $arrData);
        $strReturn .= $this->objToolkit->getPageview($objArraySectionIterator, "system", "systemSessions");

        return $strReturn;
    }

    /**
     * Fetches the entries from the system-log an prints them as preformatted text
     *
     * @return string
     * @autoTestable
     * @permissions right3
     */
    protected function actionSystemlog()
    {

        //load logfiles available
        $objFilesystem = new Filesystem();
        $arrFiles = $objFilesystem->getFilelist(_projectpath_ . "/log", array(".log"));

        $arrTabs = array();

        foreach ($arrFiles as $strName) {
            $objFilesystem->openFilePointer(_projectpath_ . "/log/" . $strName, "r");
            $strLogContent = $objFilesystem->readLastLinesFromFile(20);
            $strLogContent = str_replace(array("INFO", "ERROR"), array("INFO   ", "ERROR  "), $strLogContent);
            $arrLogEntries = explode("\r", $strLogContent);
            $objFilesystem->closeFilePointer();

            $arrTabs[$strName] = $this->objToolkit->getPreformatted($arrLogEntries);
        }

        return $this->objToolkit->getTabbedContent($arrTabs);
    }

    /**
     * Renders the list of changes for the passed systemrecord.
     * May be called from other modules in order to get the rendered list for a single record.
     * In most cases rendered as a overlay, so in folderview mode
     *
     * @param string $strSystemid sytemid to filter
     * @param string $strSourceModule source-module, required for a working pageview
     * @param string $strSourceAction source-action, required for a working pageview
     * @param bool $bitBlockFolderview
     *
     * @return string
     * @since 3.4.0
     * @autoTestable
     * @permissions changelog
     * @throws Exception
     */
    public function actionGenericChangelog($strSystemid = "", $strSourceModule = "system", $strSourceAction = "genericChangelog", $bitBlockFolderview = false)
    {
        if ($strSystemid == "") {
            $strSystemid = $this->getSystemid();
        }

        if (!validateSystemid($strSystemid) && $this->getObjModule()->rightChangelog()) {
            $strReturn = $this->objToolkit->warningBox($this->getLang("generic_changelog_no_systemid"));
            $strReturn .= $this->objToolkit->formHeader(getLinkAdminHref("system", "genericChangeLog", "bitBlockFolderview=1"));
            $strReturn .= $this->objToolkit->formInputText("systemid", "systemid");
            $strReturn .= $this->objToolkit->formInputSubmit();
            $strReturn .= $this->objToolkit->formClose();

            return $strReturn;
        }

        /** @var VersionableInterface|Model $objObject */
        $objObject = Objectfactory::getInstance()->getObject($strSystemid);
        if (!$objObject instanceof VersionableInterface) {
            return $this->objToolkit->warningBox($this->getLang("generic_changelog_not_versionable"));
        }

        $strReturn = "";
        //showing a list using the pageview
        $arrExcludeActionsFilter = [];
        if (!Session::getInstance()->isSuperAdmin()
            && !Rights::getInstance()->checkPermissionForUserId(Session::getInstance()->getUserID(), Rights::$STR_RIGHT_RIGHT, $strSystemid)) {
            $arrExcludeActionsFilter = [SystemChangelog::$STR_ACTION_PERMISSIONS];
        }
        $objArraySectionIterator = new ArraySectionIterator(SystemChangelog::getLogEntriesCount($strSystemid, $arrExcludeActionsFilter));
        $objArraySectionIterator->setPageNumber((int) ($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(SystemChangelog::getLogEntries($strSystemid, $objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos(), $arrExcludeActionsFilter));

        $objManager = new PackagemanagerManager();
        $arrToolbar = array();
        if ($objManager->getPackage("phpexcel") != null) {
            $strLink = Link::getLinkAdminXml($this->getArrModule("modul"), "genericChangelogExportExcel", "&systemid=" . $strSystemid);
            $strLinkXml = Link::getLinkAdminManual(
                "href='{$strLink}'",
                AdminskinHelper::getAdminImage("icon_excel") . " " . $this->getLang("change_export_excel")
            );

            $arrToolbar[] = $strLinkXml;
        }

        $arrToolbar[] = Link::getLinkAdmin($this->getArrModule("modul"), "changelogDiff", "&systemid=" . $strSystemid . "&bitBlockFolderview=" . $this->getParam("bitBlockFolderview"), AdminskinHelper::getAdminImage("icon_aspect") . " " . $this->getLang("change_diff"), "", "", false);

        $strReturn .= $this->objToolkit->getContentToolbar($arrToolbar);

        list($arrHeader, $arrData) = $this->buildChangelogDataTable($objObject, $objArraySectionIterator, true);
        $strReturn .= $this->objToolkit->dataTable($arrHeader, $arrData, "kajona-data-table-ignore-floatthread");

        $strReturn .= $this->objToolkit->getPageview($objArraySectionIterator, $strSourceModule, $strSourceAction, "&systemid=" . $strSystemid . "&bitBlockFolderview=" . $this->getParam("bitBlockFolderview"));

        return $strReturn;
    }

    /**
     * Internal helper to build a changelog array
     *
     * @param VersionableInterface $objVersionable
     * @param ArraySectionIterator $objIterator
     * @param bool $bitStripContent
     * @return array
     */
    private function buildChangelogDataTable(VersionableInterface $objVersionable, ArraySectionIterator $objIterator, bool $bitStripContent)
    {
        $arrData = array();
        $arrHeader = array();
        $arrHeader[] = $this->getLang("commons_date");
        $arrHeader[] = $this->getLang("change_user");
        $arrHeader[] = $this->getLang("change_action");
        $arrHeader[] = $this->getLang("change_property");
        $arrHeader[] = $this->getLang("change_oldvalue");
        $arrHeader[] = $this->getLang("change_newvalue");

        /** @var $objOneEntry ChangelogContainer */
        foreach ($objIterator as $objOneEntry) {
            $arrRowData = array();

            $strOldValue = htmlentities($objVersionable->renderVersionValue($objOneEntry->getStrProperty(), $objOneEntry->getStrOldValue()));
            $strNewValue = htmlentities($objVersionable->renderVersionValue($objOneEntry->getStrProperty(), $objOneEntry->getStrNewValue()));

            $arrRowData[] = dateToString($objOneEntry->getObjDate());
            $arrRowData[] = $objOneEntry->getStrUsername();
            $arrRowData[] = $objVersionable->getVersionActionName($objOneEntry->getStrAction());
            $arrRowData[] = $objVersionable->getVersionPropertyName($objOneEntry->getStrProperty());
            $arrRowData[] = $bitStripContent ? $this->objToolkit->getTooltipText(StringUtil::truncate($strOldValue, 40), $strOldValue) : $strOldValue;
            $arrRowData[] = $bitStripContent ? $this->objToolkit->getTooltipText(StringUtil::truncate($strNewValue, 40), $strNewValue) : $strNewValue;

            $arrData[] = $arrRowData;
        }

        return [$arrHeader, $arrData];
    }

    /**
     * Provides an option to compare the current record with a state from a different time
     *
     * @permissions changelog
     * @since 5.1
     * @return string
     * @throws Exception
     */
    protected function actionChangelogDiff()
    {
        $strSystemId = $this->getSystemid();
        /** @var VersionableInterface $objObject */
        $objObject = Objectfactory::getInstance()->getObject($strSystemId);

        if (!$objObject instanceof VersionableInterface) {
            return $this->objToolkit->warningBox($this->getLang("generic_changelog_not_versionable"));
        }

        $objNow = new Date();
        $objNow->setEndOfDay();
        $objYearAgo = new Date();
        $objYearAgo->setPreviousYear()->setEndOfDay();

        $arrDates = SystemChangelog::getDatesForSystemid($strSystemId, $objYearAgo, $objNow);

        $arrResult = array();
        foreach ($arrDates as $arrDate) {
            $objDate = new Date($arrDate["change_date"]);
            $arrResult[substr($objDate->getLongTimestamp(), 0, 8)] = $objDate->getLongTimestamp();
        }
        ksort($arrResult);

        $objRightDate = new Date(array_pop($arrResult));
        $strRightDate = $objRightDate->setEndOfDay()->getLongTimestamp();
        $objLeftDate = new Date(array_pop($arrResult));
        $strLeftDate = $objLeftDate->setEndOfDay()->getLongTimestamp();

        $strReturn = "";
        $strReturn .= $this->objToolkit->getContentToolbar(array(
            Link::getLinkAdmin($this->getArrModule("modul"), "genericChangelog", "&systemid=" . $objObject->getStrSystemid() . "&bitBlockFolderview=" . $this->getParam("bitBlockFolderview"), AdminskinHelper::getAdminImage("icon_history") . " " . $this->getLang("commons_edit_history"), "", "", false),
        ));

        $arrTemplate = array(
            "strSystemId" => $strSystemId,
            "strLeftDate" => $strLeftDate,
            "strRightDate" => $strRightDate,
            "strDateFormat" => $this->getLang("dateStyleShort"),
            "strLang" => json_encode(array(
                "months" => $this->getLang("toolsetCalendarMonthShort"),
                "days" => $this->getLang("toolsetCalendarWeekdayShort"),
                "tooltipUnit" => $this->getLang("changelog_tooltipUnit"),
                "tooltipUnitPlural" => $this->getLang("changelog_tooltipUnitPlural"),
                "tooltipHtml" => $this->getLang("changelog_tooltipHtml"),
                "tooltipColumn" => $this->getLang("changelog_tooltipColumn"),
            )),
        );

        $strReturn .= $this->objTemplate->fillTemplateFile($arrTemplate, "/elements.tpl", "changelog_heatmap");

        $objReflection = new Reflection($objObject);
        $arrProps = $objReflection->getPropertiesWithAnnotation(SystemChangelog::ANNOTATION_PROPERTY_VERSIONABLE);
        $arrData = array();

        foreach ($arrProps as $strPropertyName => $strValue) {
            $strGetter = $objReflection->getGetter($strPropertyName);
            if (!empty($strGetter)) {
                $strPropertyLabel = $objObject->getVersionPropertyName($strPropertyName);

                $arrRow = array();
                $arrRow['0 border-right'] = $strPropertyLabel;
                $arrRow['1 border-right'] = "<div id='property_" . $strPropertyName . "_left' class='changelog_property changelog_property_left' data-name='" . $strPropertyName . "'></div>";
                $arrRow[] = "<div id='property_" . $strPropertyName . "_right' class='changelog_property changelog_property_right' data-name='" . $strPropertyName . "'></div>";
                $arrData[] = $arrRow;
            }
        }

        $arrHeader = array(
            '0 border-right' => $this->getLang("change_property"),
            '1 border-right" style="width:30%;"' => "<div id='date_left'></div>",
            '2" style="width:30%;"' => "<div id='date_right'></div>",
        );

        $strReturn .= $this->objToolkit->dataTable($arrHeader, $arrData);

        return $strReturn;
    }

    /**
     * Generates an excel sheet based on the changelog entries from the given systemid
     *
     * @param string $strSystemid
     *
     * @since 4.6.6
     * @permissions changelog
     * @return string
     * @throws Exception
     */
    protected function actionGenericChangelogExportExcel($strSystemid = "")
    {

        $objManager = new PackagemanagerManager();
        if ($objManager->getPackage("phpexcel") == null) {
            return $this->getLang("commons_error_permissions");
        }
        // get system id
        if ($strSystemid == "") {
            $strSystemid = $this->getSystemid();
        }

        /** @var VersionableInterface|Model $objObject */
        $objObject = Objectfactory::getInstance()->getObject($strSystemid);
        if (!$objObject instanceof VersionableInterface) {
            return $this->objToolkit->warningBox($this->getLang("generic_changelog_not_versionable"));
        }

        $intCount = SystemChangelog::getLogEntriesCount($strSystemid);
        $objArraySectionIterator = new ArraySectionIterator($intCount);
        $objArraySectionIterator->setPageNumber((int) ($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(SystemChangelog::getLogEntries($strSystemid, 0, $intCount));

        list($arrHeader, $arrData) = $this->buildChangelogDataTable($objObject, $objArraySectionIterator, false);

        $objExporter = new PhpexcelDataTableExporter();
        $objExporter->exportDataTableToExcel($arrHeader, $arrData);
    }

    /**
     * About Kajona, credits and co
     *
     * @return string
     * @autoTestable
     * @permissions view
     */
    protected function actionAbout()
    {
        $strReturn = "";
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part1"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2_header"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2a_header"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2a"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2b_header"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part2b"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part5_header"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part5"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part3_header"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part3"));
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("about_part4"));
        return $strReturn;
    }

    /**
     * Creates a form to send mails to specific users.
     *
     * @return AdminFormgenerator
     * @throws Exception
     */
    private function getMailForm()
    {
        $objFormgenerator = new AdminFormgenerator("mail", new SystemCommon());
        $objFormgenerator->addField(new FormentryText("mail", "recipient"))->setStrLabel($this->getLang("mail_recipient"))->setBitMandatory(true)->setObjValidator(new EmailValidator());
        $objFormgenerator->addField(new FormentryUser("mail", "cc"))->setStrLabel($this->getLang("mail_cc"));
        $objFormgenerator->addField(new FormentryText("mail", "subject"))->setStrLabel($this->getLang("mail_subject"))->setBitMandatory(true);
        $objFormgenerator->addField(new FormentryTextarea("mail", "body"))->setStrLabel($this->getLang("mail_body"))->setBitMandatory(true);
        return $objFormgenerator;
    }

    /**
     * Generates a form in order to send an email.
     * This form is generic, so it may be called from several places.
     * If a mail-address was passed by param "mail_recipient", the form tries to send the message by mail,
     * otherwise (default) the message is delivered using the messaging. Therefore the param mail_to_id is expected when being
     * triggered externally.
     *
     * @param AdminFormgenerator $objForm
     *
     * @return string
     * @since 3.4
     * @autoTestable
     * @permissions view
     * @throws Exception
     */
    protected function actionMailForm(AdminFormgenerator $objForm = null)
    {
        if ($objForm == null) {
            $objForm = $this->getMailForm();
        }

        return $objForm->renderForm(Link::getLinkAdminHref($this->getArrModule("modul"), "sendMail"));
    }

    /**
     * Sends an email. In most cases this mail was generated using the form
     * provided by actionMailForm
     *
     * @return string
     * @since 3.4
     * @permissions view
     */
    protected function actionSendMail()
    {

        $objForm = $this->getMailForm();

        if (!$objForm->validateForm()) {
            return $this->actionMailForm($objForm);
        }

        $objUser = $this->objSession->getUser();

        //mail or internal message?
        $objMailValidator = new EmailValidator();
        $objEmail = new Mail();

        $objEmail->setSender($objUser->getStrEmail());
        $arrRecipients = explode(",", $this->getParam("mail_recipient"));
        foreach ($arrRecipients as $strOneRecipient) {
            if ($objMailValidator->validate($strOneRecipient)) {
                $objEmail->addTo($strOneRecipient);
            }
        }

        if ($objForm->getField("mail_cc")->getStrValue() != "") {
            $objUser = Objectfactory::getInstance()->getObject($objForm->getField("mail_cc")->getStrValue());
            $objEmail->addCc($objUser->getStrEmail());
        }

        $objEmail->setSubject($objForm->getField("mail_subject")->getStrValue());
        $objEmail->setText($objForm->getField("mail_body")->getStrValue());

        if ($objEmail->sendMail()) {
            return $this->getLang("mail_send_success");
        } else {
            return $this->getLang("mail_send_error");
        }
    }

    /**
     * Loads the data for one module
     *
     * @param int $intModuleID
     * @param bool $bitZeroIsSystem
     *
     * @return SystemModule
     */
    private function getModuleDataID($intModuleID, $bitZeroIsSystem = false)
    {
        $arrModules = SystemModule::getAllModules();

        if ($intModuleID != 0 || !$bitZeroIsSystem) {
            foreach ($arrModules as $objOneModule) {
                if ($objOneModule->getIntNr() == $intModuleID) {
                    return $objOneModule;
                }
            }
        } elseif ($intModuleID == 0 && $bitZeroIsSystem) {
            foreach ($arrModules as $objOneModule) {
                if ($objOneModule->getStrName() == "system") {
                    return $objOneModule;
                }
            }
        }
        return null;
    }

    /**
     * Unlocks a record if currently locked by the current user
     *
     * @permissions edit
     * @return string
     * @throws Exception
     */
    protected function actionUnlockRecord()
    {
        $objRecord = Objectfactory::getInstance()->getObject($this->getSystemid());

        if ($objRecord !== null) {
            $objLockmanager = $objRecord->getLockManager();
            if ($objLockmanager->unlockRecord()) {
                return "<ok></ok>";
            }
        }
        ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_FORBIDDEN);
        return "<error></error>";
    }

    /**
     * Updates the aboslute position of a single record, relative to its siblings
     *
     * @return string
     * @permissions edit
     */
    protected function actionSetAbsolutePosition()
    {
        $strReturn = "";

        $objObject = Objectfactory::getInstance()->getObject($this->getSystemid());
        $intNewPos = $this->getParam("listPos");
        //check permissions
        if ($objObject != null && $objObject->rightEdit() && $intNewPos != "") {
            //store edit date
            $this->objLifeCycleFactory->factory(get_class($objObject))->update($objObject);
            $objObject->setAbsolutePosition($intNewPos);
            $strReturn .= "<message>" . $objObject->getStrDisplayName() . " - " . $this->getLang("setAbsolutePosOk") . "</message>";
        } else {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
            $strReturn .= "<message><error>" . xmlSafeString($this->getLang("commons_error_permissions")) . "</error></message>";
        }

        return $strReturn;
    }

    /**
     * Changes the status of the current systemid
     *
     * @return string
     * @permissions edit
     * @throws Exception
     */
    protected function actionSetStatus()
    {
        $strReturn = "";
        $objCommon = Objectfactory::getInstance()->getObject($this->getSystemid());
        if ($objCommon != null && $objCommon->rightEdit()) {
            $intNewStatus = $this->getParam("status");
            if ($intNewStatus == "") {
                $intNewStatus = $objCommon->getIntRecordStatus() == 0 ? 1 : 0;
            }

            try {
                $objCommon->setIntRecordStatus($intNewStatus);
                $this->objLifeCycleFactory->factory(get_class($objCommon))->update($objCommon);
                $strReturn .= "<message>" . $objCommon->getStrDisplayName() . " - " . $this->getLang("setStatusOk") . "<newstatus>" . $intNewStatus . "</newstatus></message>";
            } catch (\Exception $objE) {
                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_INTERNAL_SERVER_ERROR);
                $strReturn .= "<message><error>" . xmlSafeString($objE->getMessage()) . "</error></message>";
            }
        } else {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_FORBIDDEN);
            $strReturn .= "<message><error>" . xmlSafeString($this->getLang("commons_error_permissions")) . "</error></message>";
        }

        return $strReturn;
    }

    /**
     * Updates a single property of an object. used by the js-insite-editor.
     * @permissions edit
     * @return string
     * @throws Exception
     */
    protected function actionUpdateObjectProperty()
    {
        //get the object to update
        $objObject = Objectfactory::getInstance()->getObject($this->getSystemid());
        if ($objObject->rightEdit()) {
            //any other object - try to find the matching property and write the value
            if ($this->getParam("property") == "") {
                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_BADREQUEST);
                return "<message><error>missing property param</error></message>";
            }

            $objReflection = new Reflection($objObject);
            $strSetter = $objReflection->getSetter($this->getParam("property"));
            if ($strSetter == null) {
                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_BADREQUEST);
                return "<message><error>setter not found</error></message>";
            }

            $objObject->{$strSetter}($this->getParam("value"));

            try {
                $this->objLifeCycleFactory->factory(get_class($objObject))->update($objObject);
                $strReturn = "<message><success>object update succeeded</success></message>";
            } catch (ServiceLifeCycleUpdateException $e) {
                $strReturn = "<message><error>object update failed</error></message>";
            }
        } else {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
            $strReturn = "<message><error>" . $this->getLang("ds_gesperrt") . "." . $this->getLang("commons_error_permissions") . "</error></message>";
        }
        return $strReturn;
    }

    /**
     * Deletes are record identified by its systemid
     *
     * @return string
     * @permissions delete
     * @throws Exception
     */
    protected function actionDelete()
    {
        if (ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::XML())) {
            $strReturn = "";
            $objCommon = Objectfactory::getInstance()->getObject($this->getSystemid());
            if ($objCommon != null && $objCommon->rightDelete() && $objCommon->getLockManager()->isAccessibleForCurrentUser()) {
                $strName = $objCommon->getStrDisplayName();
                if ($objCommon->deleteObject()) {
                    $strReturn .= "<message>" . $strName . " - " . $this->getLang("commons_delete_ok") . "</message>";
                } else {
                    $strReturn .= "<error>" . $strName . " - " . $this->getLang("commons_delete_error") . "</error>";
                }
            } else {
                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_FORBIDDEN);
                $strReturn .= "<message><error>" . xmlSafeString($this->getLang("commons_error_permissions")) . "</error></message>";
            }

            return $strReturn;
        } else {
            parent::actionDelete();
        }
        return "";
    }

    /**
     * Sets the prev-id of a record.
     * expects the param prevId
     *
     * @return string
     * @permissions edit
     */
    protected function actionSetPrevid()
    {
        $strReturn = "";

        $objRecord = Objectfactory::getInstance()->getObject($this->getSystemid());
        $strNewPrevId = $this->getParam("prevId");
        //check permissions
        if ($objRecord != null && $objRecord->rightEdit() && validateSystemid($strNewPrevId)) {
            if ($objRecord->getStrPrevId() != $strNewPrevId) {
                $this->objLifeCycleFactory->factory(get_class($objRecord))->update($objRecord, $strNewPrevId);
            }

            $strReturn .= "<message>" . $objRecord->getStrDisplayName() . " - " . $this->getLang("setPrevIdOk") . "</message>";
        } else {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_FORBIDDEN);
            $strReturn .= "<message><error>" . xmlSafeString($this->getLang("commons_error_permissions")) . "</error></message>";
        }

        return $strReturn;
    }

    /**
     * Executes a systemtask.
     * Returns the progress-info or the error-/success message and the reload-infos using a
     * custom xml-structure:
     * <statusinfo></statusinfo><reloadurl></reloadurl>
     *
     * @permissions right2
     * @return string
     */
    protected function actionExecuteSystemTask()
    {
        $strReturn = "";
        $strTaskOutput = "";

        if ($this->getParam("task") != "") {
            //include the list of possible tasks
            $arrFiles = SystemtaskBase::getAllSystemtasks();

            //search for the matching task
            /** @var AdminSystemtaskInterface|SystemtaskBase $objTask */
            foreach ($arrFiles as $objTask) {
                //instantiate the current task
                if ($objTask->getStrInternalTaskname() == $this->getParam("task")) {
                    Logger::getInstance(Logger::ADMINTASKS)->warning("executing task " . $objTask->getStrInternalTaskname());

                    //let the work begin...
                    $strTempOutput = trim($objTask->executeTask());

                    //progress information?
                    if ($objTask->getStrProgressInformation() != "") {
                        $strTaskOutput .= $objTask->getStrProgressInformation();
                    }

                    if (is_numeric($strTempOutput) && ($strTempOutput >= 0 && $strTempOutput <= 100)) {
                        $strTaskOutput .= "<br />" . $this->getLang("systemtask_progress") . "<br />" . $this->objToolkit->percentBeam($strTempOutput);
                    } else {
                        $strTaskOutput .= $strTempOutput;
                    }

                    //create response-content
                    $strReturn .= "<statusinfo>" . $strTaskOutput . "</statusinfo>\n";

                    //reload requested by worker?
                    if ($objTask->getStrReloadUrl() != "") {
                        $strReturn .= "<reloadurl>" . ("&task=" . $this->getParam("task") . $objTask->getStrReloadParam()) . "</reloadurl>";
                    }

                    break;
                }
            }
        }

        return $strReturn;
    }

    /**
     * Returns all properties for the given module
     *
     * @permissions loggedin
     * @return string
     * @responseType json
     */
    public function actionFetchProperty()
    {
        $strTargetModule = $this->getParam("target_module");
        $strLanguage = null;
        if ($this->getParam("language") !== "") {
            $strLanguage = $this->getParam("language");
        }
        $strReturn = Lang::getInstance()->getProperties($strTargetModule, $strLanguage);
        return json_encode($strReturn);
    }

    /**
     * Returns the properties of an object for a specific date json encoded
     *
     * @return string
     * @permissions changelog
     * @throws Exception
     * @responseType json
     */
    protected function actionChangelogPropertiesForDate()
    {
        $objObject = Objectfactory::getInstance()->getObject($this->getSystemid());
        $strDate = new Date($this->getParam("date"));

        if ($objObject instanceof VersionableInterface) {
            $objChangelog = new SystemChangelogRestorer();
            $objChangelog->restoreObject($objObject, $strDate);

            $objReflection = new Reflection($objObject);
            $arrProps = $objReflection->getPropertiesWithAnnotation(SystemChangelog::ANNOTATION_PROPERTY_VERSIONABLE);
            $arrData = array();

            foreach ($arrProps as $strPropertyName => $strValue) {
                $strGetter = $objReflection->getGetter($strPropertyName);
                if (!empty($strGetter)) {
                    $strValue = $objObject->$strGetter();
                    $arrData[$strPropertyName] = strval($objObject->renderVersionValue($strPropertyName, $strValue));
                }
            }

            return json_encode(array(
                "systemid" => $objObject->getStrSystemid(),
                "date" => date("d.m.Y", $strDate->getTimeInOldStyle()),
                "properties" => $arrData,
            ));
        } else {
            throw new Exception("Invalid object type", Exception::$level_ERROR);
        }
    }

    /**
     * @permissions changelog
     * @since 5.1
     * @return string
     * @responseType json
     */
    protected function actionChangelogChartData()
    {
        $objNow = new Date($this->getParam("now"));
        $objYearAgo = new Date($this->getParam("yearAgo"));
        $strSystemId = $this->getSystemid();

        $arrDates = SystemChangelog::getDatesForSystemid($strSystemId, $objYearAgo, $objNow);

        $arrResult = array();
        $arrChart = array();
        foreach ($arrDates as $arrDate) {
            $objDate = new Date($arrDate["change_date"]);
            $strDate = substr($objDate->getLongTimestamp(), 0, 8);
            $arrResult[$objDate->getLongTimestamp()] = date("d.m.Y", $objDate->getTimeInOldStyle());
            if (isset($arrChart[$strDate])) {
                $arrChart[$strDate]++;
            } else {
                $arrChart[$strDate] = 1;
            }
        }

        return json_encode($arrChart);
    }

    /**
     * @return string
     * @permissions view
     * @autoTestable
     * @throws Exception
     */
    protected function actionShowComponents()
    {
        $result = [];

        $inputText = new Inputtext("input_text", "Text", "foo");
        $result[] = $inputText;

        $inputText = new Inputtext("input_text_disabled", "Text (disabled)", "foo");
        $inputText->setReadOnly(true);
        $result[] = $inputText;

        $inputText = new Inputtext("input_text_password", "Text (password)", "foo");
        $inputText->setType("password");
        $result[] = $inputText;

        $inputText = new Inputtext("input_text_date", "Text (date)", "");
        $inputText->setType("date");
        $result[] = $inputText;

        $inputColorpicker = new Inputcolorpicker("input_colorpicker", "Colorpicker", "#852a2a");
        $result[] = $inputColorpicker;

        $inputColorpicker = new Inputcolorpicker("input_colorpicker_disabled", "Colorpicker (disabled)", "#852a2a");
        $inputColorpicker->setReadOnly(true);
        $result[] = $inputColorpicker;

        $inputCheckbox = new Inputcheckbox("input_checkbox", "Checkbox", true);
        $result[] = $inputCheckbox;

        $inputCheckbox = new Inputcheckbox("input_checkbox_disabled", "Checkbox (disabled)", true);
        $inputCheckbox->setReadOnly(true);
        $result[] = $inputCheckbox;

        $inputOnOff = new Inputonoff("input_onoff", "Onoff", true);
        $result[] = $inputOnOff;

        $inputOnOff = new Inputonoff("input_onoff_disabled", "Onoff (disabled)", true);
        $inputOnOff->setReadOnly(true);
        $result[] = $inputOnOff;

        $buttonBar = new Buttonbar("buttonbar", "Buttonbar", range(0, 10), [5]);
        $result[] = $buttonBar;

        $buttonBar = new Buttonbar("buttonbar_radio", "Buttonbar (radio)", range(0, 10), [5], Buttonbar::TYPE_RADIO);
        $result[] = $buttonBar;

        $buttonBar = new Buttonbar("buttonbar_disabled", "Buttonbar (disabled)", range(0, 10), [5]);
        $buttonBar->setReadOnly(true);
        $result[] = $buttonBar;

        $radioGroup = new Radiogroup("radiogroup", "Radiogroup", range(0, 10), 5);
        $result[] = $radioGroup;

        $radioGroup = new Radiogroup("radiogroup_disabled", "Radiogroup (disabled)", range(0, 10), 5);
        $radioGroup->setReadOnly(true);
        $result[] = $radioGroup;

        $dateSingle = new Datesingle("datesingle", "Datesingle", new Date());
        $result[] = $dateSingle;

        $dateSingle = new Datesingle("datesingle_readonly", "Datesingle (readonly)", new Date());
        $dateSingle->setReadOnly(true);
        $result[] = $dateSingle;

        $dateTime = new Datetime("datetime", "Datetime", new Date());
        $result[] = $dateTime;

        $dateTime = new Datetime("datetime_readonly", "Datetime (readonly)", new Date());
        $dateTime->setReadOnly(true);
        $result[] = $dateTime;

        $inputDropdown = new Dropdown("input_dropdown", "Dropdown", range(0, 10), 5);
        $result[] = $inputDropdown;

        $inputDropdown = new Dropdown("input_dropdown_disabled", "Dropdown (disabled)", range(0, 10), 5);
        $inputDropdown->setReadOnly(true);
        $result[] = $inputDropdown;

        $listEditor = new Listeditor("listeditor", "Listeditor", ["foo", "bar"]);
        $result[] = $listEditor;

        $listEditor = new Listeditor("listeditor_disabled", "Listeditor (disabled)", ["foo", "bar"]);
        $listEditor->setReadOnly(true);
        $result[] = $listEditor;

        $objectList = new Objectlist("objectlist", "Objectlist", UserGroup::getObjectListFiltered(null, "", 0, 3));
        $result[] = $objectList;

        $objectList = new Objectlist("objectlist_disabled", "Objectlist (disabled)", UserGroup::getObjectListFiltered(null, "", 0, 3));
        $objectList->setReadOnly(true);
        $result[] = $objectList;

        $objectSelector = new Objectselector("objectselector", "Objectselector", null, UserGroup::getGroupByName("Admins"));
        $result[] = $objectSelector;

        $objectSelector = new Objectselector("objectselector_disabled", "Objectselector (disabled)", null, UserGroup::getGroupByName("Admins"));
        $objectSelector->setReadOnly(true);
        $result[] = $objectSelector;

        $html = "";
        $html .= "<pre>" . json_encode($this->getAllParams(), JSON_PRETTY_PRINT) . "</pre>";
        $html .= "<hr>";

        $html .= $this->objToolkit->formHeader("");
        foreach ($result as $component) {
            $html .= $component->renderComponent();
            $html .= "<hr>";
        }
        $html .= $this->objToolkit->formInputSubmit();
        $html .= $this->objToolkit->formClose();

        return $html;
    }

}
