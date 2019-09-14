<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *    $Id$                                *
 ********************************************************************************************************/

namespace Kajona\Dashboard\Admin;

use Kajona\Dashboard\Admin\Widgets\Adminwidget;
use Kajona\Dashboard\Admin\Widgets\AdminwidgetInterface;
use Kajona\Dashboard\System\DashboardConfig;
use Kajona\Dashboard\System\DashboardUserRoot;
use Kajona\Dashboard\System\DashboardWidget;
use Kajona\Dashboard\System\EventEntry;
use Kajona\Dashboard\System\EventRepository;
use Kajona\Dashboard\System\Filter\DashboardICalendarFilter;
use Kajona\Dashboard\System\ICalendar;
use Kajona\Dashboard\System\Lifecycle\ConfigLifecycle;
use Kajona\Dashboard\System\TodoJstreeNodeLoader;
use Kajona\Dashboard\System\TodoRepository;
use Kajona\Dashboard\View\Components\Dashboard\Dashboard;
use Kajona\Dashboard\View\Components\Widget\Widget;
use Kajona\Dashboard\View\Components\Widgetlist\WidgetList;
use Kajona\System\Admin\AdminEvensimpler;
use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\AdminInterface;
use Kajona\System\Admin\Formentries\FormentryText;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\Exception;
use Kajona\System\System\HttpStatuscodes;
use Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemChangelog;
use Kajona\System\System\SystemJSTreeBuilder;
use Kajona\System\System\SystemJSTreeConfig;
use Kajona\System\View\Components\Datatable\Datatable;
use Kajona\System\View\Components\Dynamicmenu\DynamicMenu;
use Kajona\System\View\Components\Menu\Item\Separator;
use Kajona\System\View\Components\Menu\Item\Text;
use Kajona\System\View\Components\Menu\Menu;
use Kajona\V4skin\Admin\Skins\Kajona_V4\AdminskinImageresolver;

/**
 * The dashboard admin class
 *
 * @package module_dashboard
 * @author sidler@mulchprod.de
 *
 * @module dashboard
 * @moduleId _dashboard_module_id_
 *
 * @objectListConfig Kajona\Dashboard\System\DashboardConfig
 * @objectEditConfig Kajona\Dashboard\System\DashboardConfig
 * @objectNewConfig Kajona\Dashboard\System\DashboardConfig
 */
class DashboardAdmin extends AdminEvensimpler implements AdminInterface
{

    protected $arrColumnsOnDashboard = array("column1", "column2", "column3");

    /**
     * @return array
     */
    public function getOutputModuleNavi()
    {
        $arrReturn = array();
        $arrReturn[] = array("view", Link::getLinkAdmin($this->getArrModule("modul"), "list", "", $this->getLang("modul_titel"), "", "", true, "adminnavi"));
        $arrReturn[] = array("view", Link::getLinkAdmin($this->getArrModule("modul"), "calendar", "", $this->getLang("action_calendar"), "", "", true, "adminnavi"));
        $arrReturn[] = array("view", Link::getLinkAdmin($this->getArrModule("modul"), "todo", "", $this->getLang("action_todo"), "", "", true, "adminnavi"));

        return $arrReturn;
    }

    /**
     * @return array
     */
    public function getArrOutputNaviEntries()
    {
        $arrReturn = parent::getArrOutputNaviEntries();
        if (isset($arrReturn[count($arrReturn) - 2])) {
            unset($arrReturn[count($arrReturn) - 2]);
        }
        return $arrReturn;
    }

    /**
     * @inheritDoc
     */
    protected function renderStatusAction(Model $objListEntry, $strAltActive = "", $strAltInactive = "")
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    protected function renderCopyAction(Model $objListEntry)
    {
        return "";
    }

    /**
     * Generates the dashboard itself.
     * Loads all widgets placed on the dashboard
     *
     * @return string
     * @autoTestable
     * @permissions view
     * @throws Exception
     */
    protected function actionList()
    {

        if ($this->getParam("action") == "listConfig") {
            $this->setSystemid(DashboardUserRoot::getOrCreateForUser(Carrier::getInstance()->getObjSession()->getUserID())->getSystemid());
            $this->setStrCurObjectTypeName("Config");
            $this->setCurObjectClassName(DashboardConfig::class);
            return parent::actionList();
        }

        /** @var ConfigLifecycle $lc */
        $lc = $this->objLifeCycleFactory->factory(DashboardConfig::class);

        //need to react on a new configid?
        if (validateSystemid($this->getParam("configid"))) {
            $lc->setActiveConfigId($this->getParam("configid"));
        }

        $widgets = [];
        foreach ($this->arrColumnsOnDashboard as $strColumnName) {
            $widgets[$strColumnName] = [];
        }

        $root = DashboardUserRoot::getOrCreateForUser(Carrier::getInstance()->getObjSession()->getUserID());
        $cfg = $lc->getActiveConfig($root);

        $widgets = [];
        if ($cfg instanceof DashboardConfig) {

            /** @var DashboardWidget $widget */
            foreach (DashboardWidget::getObjectListFiltered(null, $cfg->getSystemid()) as $widget) {
                $widgets[$widget->getStrColumn()][] = $this->layoutAdminWidget($widget)->renderComponent();
            }
        }

        $board = new Dashboard();
        $board->setWidgets($widgets);
        $return = $board->renderComponent();

        //add a toolbar
        if ($cfg->rightEdit()) {
            $return .= $this->objToolkit->addToContentToolbar(Link::getLinkAdminDialog("dashboard", "listWidgets", [], $this->getLang("action_add_widget_to_dashboard"), $this->getLang("action_add_widget_to_dashboard"), "icon_new"));
        }

        $params = Carrier::getAllParams();
        unset($params["module"]);
        unset($params["action"]);


        if ($root->rightEdit() || DashboardConfig::getObjectCountFiltered(null, $root->getSystemid()) > 1) {
            $menu = new DynamicMenu(
                "{$cfg->getStrDisplayName()}<i class='fa fa-caret-down'></i>",
                Link::getLinkAdminXml("dashboard", "apiGetDashboardMenu", $params)
            );
            $menu = $menu->renderComponent();
            $return .= $this->objToolkit->addToContentToolbar($menu);
        }
        return $return;
    }

    /**
     * Renders the status menu
     *
     * @return string
     * @permissions view
     * @responseType html
     * @throws Exception
     */
    protected function actionApiGetDashboardMenu()
    {
        $root = DashboardUserRoot::getOrCreateForUser(Carrier::getInstance()->getObjSession()->getUserID());

        $dd = [];
        $items = [];

        $params = Carrier::getAllParams();
        unset($params["admin"]);
        unset($params["contentFill"]);
        unset($params["module"]);
        unset($params["action"]);

        $return = "";
        /** @var DashboardConfig $singleCfg */
        foreach (DashboardConfig::getObjectListFiltered(null, $root->getSystemid()) as $singleCfg) {
            $dd[$singleCfg->getSystemid()] = $singleCfg->getStrDisplayName();

            $params["configid"] = $singleCfg->getSystemid();

            $text = new Text(Link::getLinkAdmin("dashboard", "list", $params, $this->objToolkit->listButton(AdminskinHelper::getAdminImage("icon_dashboard")) . " " . $singleCfg->getStrDisplayName(), "", "", false));
            $items[] = $text;
        }

        if ($this->getObjModule()->rightEdit()) {
            $items[] = new Separator();
            $items[] = new Text(Link::getLinkAdmin("dashboard", "listConfig", [], $this->objToolkit->listButton(AdminskinHelper::getAdminImage("icon_edit")) . " " . $this->getLang("commons_list_edit"), "", "", false));
        }

        //create a switch-menu
        $menu = new Menu();
        $menu->setItems($items);
        $menu->setRenderMenuContainer(false);

        return $menu->renderComponent();
    }

    /**
     * Shows wiget list.
     * Loads all widgets placed on the dashboard
     *
     * @return string
     * @permissions view
     */
    protected function actionListWidgets()
    {
        $arrWidgetsAvailable = DashboardWidget::getListOfWidgetsAvailable();
        /** @var $objWidget AdminwidgetInterface|Adminwidget */
        foreach ($arrWidgetsAvailable as $objWidget) {
            $img = "<img src='" . _webpath_ . "/image.php?image=" . urlencode($objWidget->getWidgetImg()) . "&amp;maxWidth=100&amp;maxHeight=60' />";
            $module = $this->getLang("modul_titel", StringUtil::replace("module_", "", $objWidget->getModuleName()));
            $arrWidget[] = ['name' => $objWidget->getWidgetName(), 'info' => $objWidget->getWidgetDescription(), 'img' => $img, 'class' => get_class($objWidget), "module" => $module];
        }

        $wListService = new WidgetList($arrWidget);
        return $wListService->renderComponent();
    }

    /**
     * Creates the layout of a dashboard-entry. loads the widget to fetch the contents of the concrete widget.
     *
     * @param DashboardWidget $objDashboardWidget
     *
     * @return Widget
     * @throws Exception
     */
    protected function layoutAdminWidget(DashboardWidget $objDashboardWidget): Widget
    {
        $objConcreteWidget = $objDashboardWidget->getConcreteAdminwidget();

        $arrActions = array();
        if ($objDashboardWidget->rightEdit()) {
            $strWidgetClass = $objDashboardWidget->getStrClass();
            if ($strWidgetClass::isEditable()) {
                $arrActions[] =
                    Link::getLinkAdminManual(
                        "href=\"#\" onclick=\"Dashboard.editWidget('{$objDashboardWidget->getSystemid()}'); return false;\"",
                        (AdminskinHelper::getAdminImage("icon_edit")) . " " . $this->getLang("editWidget"),
                        "",
                        "",
                        "",
                        "",
                        false
                    );
            }
        }
        if ($objDashboardWidget->rightDelete()) {
            $strQuestion = StringUtil::replace("%%element_name%%", StringUtil::jsSafeString($objConcreteWidget->getWidgetName()), $this->getLang("widgetDeleteQuestion"));

            $strHeader = Carrier::getInstance()->getObjLang()->getLang("dialog_deleteHeader", "system");
            $strConfirmationButtonLabel = Carrier::getInstance()->getObjLang()->getLang("dialog_deleteButton", "system");
            $strConfirmationLinkHref = "javascript:Dashboard.removeWidget(\'" . $objDashboardWidget->getSystemid() . "\');";

            $arrActions[] =
                Link::getLinkAdminManual(
                    "href=\"#\" onclick=\"DialogHelper.showConfirmationDialog('{$strHeader}', '{$strQuestion}', '{$strConfirmationButtonLabel}', '{$strConfirmationLinkHref}'); return false;\"",
                    (AdminskinHelper::getAdminImage("icon_delete")) . " " . Carrier::getInstance()->getObjLang()->getLang("commons_delete", "system"), "", "", "", "", false
                );
        }

        $widget = new Widget();
        $widget
            ->setTitle($objConcreteWidget->getWidgetName())
            ->setSubTitle($objConcreteWidget->getWidgetNameAdditionalContent())
            ->setActions($arrActions)
            ->setId($objConcreteWidget->getSystemid());

        return $widget;
    }

    /**
     * Create a calendar based on the jquery fullcalendar. Loads all events from the XML action actionGetCalendarEvents
     *
     * @return string
     * @since 3.4
     * @autoTestable
     * @permissions view
     */
    protected function actionCalendar()
    {
        $return = "";

        $return .= "<div id='dashboard-calendar' class='core-component-calendar'></div>";
        $return .= "<script type=\"text/javascript\">";
        $return .= <<<JS

        DashboardCalendar.init();
JS;
        $return .= "</script>";

        $icalLink = Link::getLinkAdminManual(["href" => "#", "onclick" => "DashboardCalendar.getICalendarURL();return false"], AdminskinHelper::getAdminImage("icon_downloads").' '. $this->getLang("dashboard_ical_url", "dashboard"));
        $return .= $this->objToolkit->addToContentToolbar($icalLink);

        return $return;
    }

    /**
     * Returns a iCal URL
     * @return array
     * @throws Exception
     * @permissions view
     * @responseType json
     */
    public function actionApiGetOrCreateICalUrl()
    {
        $filter = new DashboardICalendarFilter();
        $userId = Carrier::getInstance()->getObjSession()->getUserID();
        $filter->setStrUserSystemId($userId);
        $iCal = ICalendar::getSingleObjectFiltered($filter);
        if (empty($iCal)) {
            $iCal = new ICalendar();
            $iCal->setStrUserId($userId);
            $this->objLifeCycleFactory->factory(get_class($iCal))->update($iCal);
        }
        $iCalLink = _apipath_ . '/v1/calendar/export/caldav/' . $iCal->getStrSystemid();
        return ["url" => $iCalLink];
    }

    /**
     * @permissions view
     */
    protected function actionTodo()
    {

        $objConfig = new SystemJSTreeConfig();
        $objConfig->setBitDndEnabled(false);
        $objConfig->setStrNodeEndpoint(Link::getLinkAdminXml("dashboard", "treeEndpoint"));
        $objConfig->setArrNodesToExpand(array(""));

        $strCategory = $this->getParam("listfilter_category");

        $strContent = $this->getListTodoFilter();
        $strContent .= "<div id='todo-table'></div>";
        $strContent .= "<script type=\"text/javascript\">";
        $strContent .= <<<JS
             Dashboard.todo.loadCategory('{$strCategory}', '');
JS;

        $strContent .= "</script>";

        return $this->objToolkit->getTreeview($objConfig, $strContent);
    }

    protected function getListTodoFilter()
    {
        // create the form
        $objFormgenerator = new AdminFormgenerator("listfilter", null);
        $objFormgenerator->setStrOnSubmit("Dashboard.todo.formSearch();return false");

        $objFormgenerator->addField(new FormentryText("listfilter", "search"))
            ->setStrLabel($this->getLang("filter_search"));

        //render filter
        $strReturn = $objFormgenerator->renderForm(Link::getLinkAdminHref("dashboard", "todo"), AdminFormgenerator::BIT_BUTTON_SUBMIT);

        return $strReturn;
    }

    /**
     * @return string
     * @throws Exception
     * @permissions edit
     * @throws Exception
     */
    protected function actionAddWidget()
    {
        //instantiate the concrete widget
        $strWidgetClass = $this->getParam("widget");
        /** @var Adminwidget|AdminwidgetInterface $objWidget */
        $objWidget = $this->objBuilder->factory($strWidgetClass);

        //let it process its fields
        $objWidget->loadFieldsFromArray($this->getAllParams());

        //and save the dashboard-entry
        $objDashboard = new DashboardWidget();
        $objDashboard->setStrClass($strWidgetClass);
        $objDashboard->setStrContent($objWidget->getFieldsAsString());
        $objDashboard->setStrColumn($this->getParam("column"));

        try {
            $userNode = DashboardUserRoot::getOrCreateForUser(Session::getInstance()->getUserID());
            /** @var ConfigLifecycle $lc */
            $lc = $this->objLifeCycleFactory->factory(DashboardConfig::class);
            $this->objLifeCycleFactory->factory(get_class($objDashboard))->update($objDashboard, $lc->getActiveConfig($userNode)->getSystemid());
            $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul")));
        } catch (ServiceLifeCycleUpdateException $e) {
            return $this->getLang("errorSavingWidget");
        }

        return "";
    }

    /**
     * Creates the form to edit a widget (NOT the dashboard entry!)
     *
     * @return string "" in case of success
     * @permissions edit
     * @throws Exception
     */
    protected function actionEditWidget()
    {
        $strReturn = "";
        if ($this->getParam("saveWidget") == "") {
            $objDashboardwidget = new DashboardWidget($this->getSystemid());
            $objWidget = $objDashboardwidget->getConcreteAdminwidget();

            //ask the widget to generate its form-parts and wrap our elements around
            $strReturn .= $this->objToolkit->formHeader(Link::getLinkAdminHref("dashboard", "editWidget"));
            $strReturn .= $objWidget->getEditForm();
            $strReturn .= $this->objToolkit->formInputHidden("systemid", $this->getSystemid());
            $strReturn .= $this->objToolkit->formInputHidden("saveWidget", "1");
            $strReturn .= $this->objToolkit->formInputSubmit($this->getLang("commons_save"));
            $strReturn .= $this->objToolkit->formClose();
        } elseif ($this->getParam("saveWidget") == "1") {
            //the dashboard entry
            $objDashboardwidget = new DashboardWidget($this->getSystemid());
            //the concrete widget
            $objConcreteWidget = $objDashboardwidget->getConcreteAdminwidget();
            $objConcreteWidget->loadFieldsFromArray($this->getAllParams());

            $objDashboardwidget->setStrContent($objConcreteWidget->getFieldsAsString());

            $this->objLifeCycleFactory->factory(get_class($objDashboardwidget))->update($objDashboardwidget);

            $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "", "&peClose=1&blockAction=1"));
        }

        return $strReturn;
    }

    /**
     * @return string
     * @throws Exception
     * @permissions edit
     * @responseType json
     */
    protected function actionSwitchOnEditMode()
    {
        $objDashboardwidget = new DashboardWidget($this->getSystemid());
        $objWidget = $objDashboardwidget->getConcreteAdminwidget();
        $strReturn = $objWidget->getEditWidgetForm();

        return json_encode($strReturn);
    }

    /**
     * Removes a single widget, called by the xml-handler
     *
     * @permissions delete
     * @return string
     * @throws Exception
     */
    protected function actionDeleteWidget()
    {
        $objWidget = new DashboardWidget($this->getSystemid());
        $objConcreteWidget = $objWidget->getConcreteAdminwidget();
        $strWidgetName = $objConcreteWidget->getWidgetName();
        $objWidget->deleteObject();
        return "<message>" . $this->getLang("deleteWidgetSuccess", array(StringUtil::jsSafeString($strWidgetName))) . "</message>";
    }

    /**
     * saves the new position of a widget on the dashboard.
     * updates the sorting AND the assigned column
     *
     * @return string
     * @throws Exception
     * @throws ServiceLifeCycleUpdateException
     * @permissions edit
     */
    protected function actionSetDashboardPosition()
    {
        $strReturn = "";

        $objWidget = new DashboardWidget($this->getSystemid());
        $intNewPos = $this->getParam("listPos");
        $objWidget->setStrColumn($this->getParam("listId"));
        $this->objLifeCycleFactory->factory(get_class($objWidget))->update($objWidget);
        Carrier::getInstance()->getObjDB()->flushQueryCache();

        $objWidget = new DashboardWidget($this->getSystemid());
        if ($intNewPos != "") {
            $objWidget->setAbsolutePosition($intNewPos);
        }

        $strReturn .= "<message>" . $objWidget->getStrDisplayName() . " - " . $this->getLang("setDashboardPosition") . "</message>";

        return $strReturn;
    }

    /**
     * Renders the content of a single widget.
     *
     * @return string
     * @throws Exception
     * @permissions view
     * @responseType json
     * @throws Exception
     */
    protected function actionGetWidgetContent()
    {

        //load the aspect and close the session afterwards
        SystemAspect::getCurrentAspect();

        $objWidgetModel = new DashboardWidget($this->getSystemid());
        if ($objWidgetModel->rightView()) {
            $objConcreteWidget = $objWidgetModel->getConcreteAdminwidget();

            if (!$objConcreteWidget->getBitBlockSessionClose()) {
                Carrier::getInstance()->getObjSession()->sessionClose();
            }

            //disable the internal changelog
            SystemChangelog::$bitChangelogEnabled = false;
            $strReturn = json_encode($objConcreteWidget->generateWidgetOutput());
        } else {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
            $strReturn = "<message><error>" . xmlSafeString($this->getLang("commons_error_permissions")) . "</error></message>";
        }

        return $strReturn;
    }

    /**
     * Updates and renders the additional name content of a single widget.
     *
     * @return string
     * @throws Exception
     * @permissions view
     * @responseType json
     */
    protected function actionUpdateWidgetAdditionalContent()
    {
        $strReturn = "";
        //load the aspect and close the session afterwards
        SystemAspect::getCurrentAspect();
        $strSystemId = $this->getParam('systemid');
        $objWidgetModel = new DashboardWidget($strSystemId);
        if ($objWidgetModel->rightView()) {
            $objConcreteWidget = $objWidgetModel->getConcreteAdminwidget();
            $strReturn = json_encode($objConcreteWidget->getWidgetNameAdditionalContent());
        }

        return $strReturn;
    }

    /**
     * Updates and renders the content of a single widget.
     *
     * @return string
     * @throws Exception
     * @permissions view
     * @responseType json
     */
    protected function actionUpdateWidgetContent()
    {

        //load the aspect and close the session afterwards
        SystemAspect::getCurrentAspect();
        $strSystemId = $this->getParam('systemid');
        $objWidgetModel = new DashboardWidget($strSystemId);
        if ($objWidgetModel->rightView()) {
            $objConcreteWidget = $objWidgetModel->getConcreteAdminwidget();
            foreach ($this->getAllParams() as $key => $value) {
                $arrAllParams[rtrim($key, "_")] = $value;
            }
            $objConcreteWidget->loadFieldsFromArray($arrAllParams);
            $objWidgetModel->setStrContent($objConcreteWidget->getFieldsAsString());

            if (!$objConcreteWidget->getBitBlockSessionClose()) {
                Carrier::getInstance()->getObjSession()->sessionClose();
            }

            //disable the internal changelog
            SystemChangelog::$bitChangelogEnabled = false;

            try {
                $userNode = DashboardUserRoot::getOrCreateForUser(Session::getInstance()->getUserID());
                /** @var ConfigLifecycle $lc */
                $lc = $this->objLifeCycleFactory->factory(DashboardConfig::class);
                $this->objLifeCycleFactory->factory(get_class($objWidgetModel))->update($objWidgetModel, $lc->getActiveConfig($userNode)->getSystemid());
                $strReturn = json_encode($objConcreteWidget->generateWidgetOutput());
            } catch (ServiceLifeCycleUpdateException $e) {
                $strReturn = $this->getLang("errorSavingWidget");
            }
        } else {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
            $strReturn = "<message><error>" . xmlSafeString($this->getLang("commons_error_permissions")) . "</error></message>";
        }

        return $strReturn;
    }

    /**
     * @return string
     * @permissions view
     * @responseType json
     */
    protected function actionGetCalendarEvents()
    {

        $arrEvents = array();
        $arrCategories = EventRepository::getAllCategories();
        $objStartDate = new Date(strtotime($this->getParam("start")));
        $objEndDate = new Date(strtotime($this->getParam("end")));

        foreach ($arrCategories as $arrCategory) {
            foreach ($arrCategory as $strKey => $strValue) {
                if ($this->objSession->getSession($strKey) != "disabled") {
                    $arrEvents = array_merge($arrEvents, EventRepository::getEventsByCategoryAndDate($strKey, $objStartDate, $objEndDate));
                }
            }
        }

        $arrData = array();
        foreach ($arrEvents as $objEvent) {
            /** @var EventEntry $objEvent */
            $strIcon = AdminskinHelper::getAdminImage($objEvent->getStrIcon());
            $arrRow = array(
                "title" => '$ICON ' . strip_tags($objEvent->getStrDisplayName()),
                "tooltip" => $objEvent->getStrDisplayName(),
                "icon" => $strIcon,
                "allDay" => true,
                "url" => htmlspecialchars_decode($objEvent->getStrHref()),
                "className" => array("calendar-event"),
            );

            if ($objEvent->getObjStartDate() instanceof Date && $objEvent->getObjEndDate() instanceof Date) {
                $arrRow["start"] = date("Y-m-d", $objEvent->getObjStartDate()->getTimeInOldStyle());
                $arrRow["end"] = date("Y-m-d", $objEvent->getObjEndDate()->getTimeInOldStyle());
            } elseif ($objEvent->getObjValidDate() instanceof Date) {
                $arrRow["start"] = date("Y-m-d", $objEvent->getObjValidDate()->getTimeInOldStyle());
            } else {
                continue;
            }

            array_push($arrData, $arrRow);
        }

        return json_encode($arrData);
    }

    /**
     * @return string
     * @permissions view
     * @responseType html
     */
    protected function actionTodoCategory()
    {
        $strCategory = $this->getParam("category");
        if (empty($strCategory)) {
            $arrTodos = TodoRepository::getAllOpenTodos();
        } else {
            $arrCategories = explode(',', $strCategory);
            $arrTodos = array();
            foreach ($arrCategories as $strCategory) {
                $arrTodos = array_merge($arrTodos, TodoRepository::getOpenTodos($strCategory, false));
            }
        }

        if (empty($arrTodos)) {
            return $this->objToolkit->warningBox($this->getLang("todo_no_open_tasks"), "alert-info");
        }

        $strSearch = $this->getParam("search");
        $strDate = $this->getParam("date");

        $arrHeaders = array(
            "0 " => "",
            "1" => $this->getLang("todo_task_col_object"),
            "2 " => $this->getLang("todo_task_col_category"),
            "3 " => $this->getLang("todo_task_col_date"),
            "4 " => "",
        );
        $arrValues = array();

        foreach ($arrTodos as $objTodo) {
            $strActions = "";
            $arrModule = $objTodo->getArrModuleNavi();
            if (!empty($arrModule) && is_array($arrModule)) {
                foreach ($arrModule as $strLink) {
                    $strActions .= $this->objToolkit->listButton($strLink);
                }
            } elseif (is_string($arrModule)) {
                $strActions = $arrModule;
            }

            $strIcon = AdminskinHelper::getAdminImage($objTodo->getStrIcon());
            $strCategory = TodoRepository::getCategoryName($objTodo->getStrCategory());
            $strValidDate = $objTodo->getObjValidDate() !== null ? dateToString($objTodo->getObjValidDate(), false) : "-";

            $bitSearchMatch = empty($strSearch) || stripos($objTodo->getStrDisplayName(), $strSearch) !== false;
            $bitDateMatch = empty($strDate) || $strValidDate == $strDate;

            if ($bitSearchMatch && $bitDateMatch) {
                $arrValues[] = array(
                    $strIcon,
                    $objTodo->getStrDisplayName(),
                    $strCategory,
                    $strValidDate,
                    "4 align-right actions" => $strActions,
                );
            }
        }

        $cmp = new Datatable($arrHeaders, $arrValues);
        $cmp->setStrTableCssAddon("admintable");
        return $cmp->renderComponent();
    }

    /**
     * @return string
     * @permissions view
     * @responseType json
     */
    protected function actionTreeEndpoint()
    {
        $objJsTreeLoader = new SystemJSTreeBuilder(
            new TodoJstreeNodeLoader()
        );

        $arrSystemIdPath = $this->getParam(SystemJSTreeBuilder::STR_PARAM_INITIALTOGGLING);
        $bitInitialLoading = is_array($arrSystemIdPath);
        if (!$bitInitialLoading) {
            $arrSystemIdPath = array("");
        }

        $arrReturn = $objJsTreeLoader->getJson($arrSystemIdPath, $bitInitialLoading, $this->getParam(SystemJSTreeBuilder::STR_PARAM_LOADALLCHILDNOES) === "true");
        return $arrReturn;
    }
}
