<?php
/*"******************************************************************************************************
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                            *
********************************************************************************************************/

namespace Kajona\System\Admin;

use Kajona\Admin\Exceptions\ModelNotFoundException;
use Kajona\System\Admin\Formentries\FormentryHidden;
use Kajona\System\System\ArraySectionIterator;
use Kajona\System\System\Exception;
use Kajona\System\System\FilterBase;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Reflection;
use Kajona\System\System\Root;
use Kajona\System\System\StringUtil;
use ReflectionMethod;

/**
 * Class holding common methods for extended and simplified admin-guis.
 * Compared to AdminSimple, this implementation is based on a declarative approach,
 * reducing the amount of code required to implement a modules' admin-views.
 *
 * Subclasses are able to declare actions and the matching objects with annotations.
 * Have a look at the demo-module on how to use it.
 *
 * @module module_system
 * @since 4.1
 *
 * @author tim.kiefer@kojikui.de, ph.wolfer@googlemail.com
 */
abstract class AdminEvensimpler extends AdminSimple
{
    const   STR_OBJECT_LIST_ANNOTATION = "@objectList";
    const   STR_OBJECT_NEW_ANNOTATION = "@objectNew";
    const   STR_OBJECT_EDIT_ANNOTATION = "@objectEdit";

    const   STR_OBJECT_LISTFILTER_ANNOTATION = "@objectFilter";

    private static $arrActionNameMapping = array(
        "list" => self::STR_OBJECT_LIST_ANNOTATION,
        "new" => self::STR_OBJECT_NEW_ANNOTATION,
        "edit" => self::STR_OBJECT_EDIT_ANNOTATION,
        "save" => self::STR_OBJECT_EDIT_ANNOTATION,
        "delete" => self::STR_OBJECT_EDIT_ANNOTATION
    );

    private $strCurObjectClassName;
    private $strCurObjectTypeName = "";

    private $strOriginalAction = "";

    /**
     * @var AdminFormgenerator
     */
    private $objCurAdminForm = null;


    /**
     * Redefined action-handler to match the declarative action to a "real", implemented action.
     * The permission handling and other stuff is checked by the base-class.
     *
     * @param string $strAction
     *
     * @return string
     * @throws Exception
     * @throws \ReflectionException
     */
    public function action($strAction = "")
    {
        if ($strAction == "") {
            $strActionName = $this->getAction();
        } else {
            $strActionName = $strAction;
        }

        $this->strOriginalAction = $strActionName;

        if (!$this->checkMethodExistsInConcreteClass("action".ucfirst($strActionName))) {
            foreach (self::$arrActionNameMapping as $strAutoMatchAction => $strAnnotation) {
                $this->autoMatchAction($strAutoMatchAction, $strAnnotation, $strActionName);
            }
        }

        return parent::action($strActionName);
    }

    /**
     * Tries to get the name of an action (edit, delete, list, new, save) for a given object-type.
     * Example: Converts list to listOtherObject for the object DemoDemo if the annotation
     *          @ objectListOtherObject Kajona\Demo\System\DemoDemo is declared
     *
     * @param string $strAction
     * @param $objInstance - may be either an object instance or the class name as string
     *
     * @return string
     * @throws Exception
     */
    protected function getActionNameForClass($strAction, $objInstance)
    {
        if (isset(self::$arrActionNameMapping[$strAction])) {
            $strAnnotationPrefix = self::$arrActionNameMapping[$strAction];

            if ($strAction == "new" && $objInstance == null) {
                return $strAction.$this->getStrCurObjectTypeName();
            } else {
                $objReflection = new Reflection($this);

                $strClassName = $objInstance;
                if (is_object($objInstance)) {
                    $strClassName = get_class($objInstance);
                }
                $arrAnnotations = $objReflection->getAnnotationsWithValueFromClass($strClassName);

                foreach ($arrAnnotations as $strProperty) {
                    if (StringUtil::indexOf($strProperty, $strAnnotationPrefix) === 0) {
                        return $strAction.StringUtil::substring($strProperty, StringUtil::length($strAnnotationPrefix));
                    }
                }
            }
        }

        return parent::getActionNameForClass($strAction, $objInstance);
    }


    /**
     * Helper method to resolve the declared action to a real action, so to make list out of listOtherObject.
     * If possible, the current object-type class (based on the annotation is stored, too.
     *
     * @param string $strAutoMatchAction
     * @param string $strAnnotation
     * @param string $strActionName
     *
     * @return void
     * @throws Exception
     */
    private function autoMatchAction($strAutoMatchAction, $strAnnotation, &$strActionName)
    {

        if (StringUtil::indexOf($strActionName, $strAutoMatchAction) === 0) {
            // Set name of current list object
            $this->setStrCurObjectTypeName(StringUtil::replace($strAutoMatchAction, "", $strActionName));
            $strActionName = $strAutoMatchAction;

            $objReflection = new Reflection($this);
            $arrAnnotations = $objReflection->getAnnotationValuesFromClass($strAnnotation.$this->getStrCurObjectTypeName());
            if (count($arrAnnotations) > 0) {
                $this->setCurObjectClassName(reset($arrAnnotations));
            } else {
                $this->setCurObjectClassName(null);
            }
        }
    }

    /**
     * Check if method exists in concrete class and not only in AdminSimple
     *
     * @param string $strMethod
     *
     * @internal param $strActionName
     * @return bool
     * @throws \ReflectionException
     */
    protected function checkMethodExistsInConcreteClass($strMethod)
    {

        if (method_exists($this, $strMethod)) {
            $objRefl = new ReflectionMethod($this, $strMethod);

            if ($objRefl->class != AdminEvensimpler::class) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }


    /**
     * Renders the form to create a new entry
     *
     * @throws Exception
     * @return string
     * @permissions edit
     */
    protected function actionNew()
    {
        $strType = $this->getCurObjectClassName();

        if (!is_null($strType)) {
            /** @var $objEdit ModelInterface|Model */
            $objEdit = new $strType();

            $objForm = AdminFormgeneratorFactory::getFormForModel($objEdit);
            if ($objForm !== null) {
                $objEdit = $objForm->getObjSourceobject();
            }

            //reset the current object reference to an object created before (e.g. during actionSave)
            $objForm = $this->getAdminForm($objEdit);
            $objForm->getObjSourceobject()->setSystemid($this->getParam("systemid"));
            $objForm->addField(new FormentryHidden("", "mode"))->setStrValue("new");

            return $objForm->renderForm(Link::getLinkAdminHref($this->getArrModule("modul"), "save".$this->getStrCurObjectTypeName()));
        } else {
            throw new Exception("error creating new entry current object type not known ", Exception::$level_ERROR);
        }
    }


    /**
     * Renders the form to edit an existing entry
     *
     * @throws Exception
     * @return string
     * @permissions edit
     */
    protected function actionEdit()
    {

        //try 1: get the object type and names based on the current object
        $objInstance = $this->objFactory->getObject($this->getSystemid());

        if ($objInstance == null) {
            throw new Exception("given object with system id {$this->getSystemid()} does not exist", Exception::$level_ERROR);
        }

        $strObjectTypeName = StringUtil::substring($this->getActionNameForClass("edit", $objInstance), 4);
        if ($strObjectTypeName != "") {
            $strType = get_class($objInstance);
            $this->setCurObjectClassName($strType);
            $this->setStrCurObjectTypeName($strObjectTypeName);
        }

        //reset the current object reference to an object created before (e.g. during actionSave)
        $objForm = AdminFormgeneratorFactory::getFormForModel($objInstance);
        if ($objForm !== null) {
            $objInstance = $objForm->getObjSourceobject();
        }

        $objForm = $this->getAdminForm($objInstance);
        $objForm->addField(new FormentryHidden("", "mode"))->setStrValue("edit");

        $bitValidate = $this->getParam("validate") === "true";
        if ($bitValidate) {
            $objForm->validateForm();
        }

        return $objForm->renderForm(Link::getLinkAdminHref($this->getArrModule("modul"), "save".$this->getStrCurObjectTypeName()));
    }


    /**
     * Checks if for the current $strCurObjectTypeName a filter object was defined in Annotation STR_OBJECT_LISTFILTER_ANNOTATION.
     *
     * @param $strCurObjectTypeName
     *
     * @return null|string
     * @throws Exception
     */
    protected function getObjectFilterClass($strCurObjectTypeName)
    {

        $objReflection = new Reflection($this);
        $arrAnnotations = $objReflection->getAnnotationValuesFromClass(self::STR_OBJECT_LISTFILTER_ANNOTATION.$strCurObjectTypeName);

        if (count($arrAnnotations) > 0) {
            return reset($arrAnnotations);
        }

        return null;
    }

    /**
     * Renders the general list of records
     *
     * @throws Exception
     * @return string
     * @permissions view
     */
    protected function actionList()
    {
        /** @var $strType ModelInterface|Model */
        $strType = $this->getCurObjectClassName();

        if (!is_null($strType)) {
            /* pass the internal action in order to get a proper paging */
            $strOriginalAction = $this->getAction();
            $this->setAction($this->strOriginalAction);


            /* Create filter for list */
            $strSessionId = null;
            $objFilter = null;
            $strFilterForm = "";
            $strObjectFilterClass = $this->getObjectFilterClass($this->getStrCurObjectTypeName());
            if ($strObjectFilterClass !== null) {
                $strSessionId = $this->getParam(AdminFormgeneratorFilter::STR_FORM_PARAM_SESSION) ?: null;
                $objFilter = FilterBase::getOrCreateFromSession($strObjectFilterClass, $strSessionId);
                $strFilterForm = $this->renderFilter($objFilter);
            }

            /* Create list */
            $objArraySectionIterator = new ArraySectionIterator($strType::getObjectCountFiltered($objFilter, $this->getSystemid()));
            $objArraySectionIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
            $objArraySectionIterator->setArraySection($strType::getObjectListFiltered($objFilter, $this->getSystemid(), $objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

            /* Render list and filter */
            $strPagerAddon = "";
            if (!empty($strSessionId)) {
                $strPagerAddon = "&".AdminFormgeneratorFilter::STR_FORM_PARAM_SESSION."=".$strSessionId;
            }


            $strList = $this->renderList($objArraySectionIterator, $this->isListSortable("list".$this->getStrCurObjectTypeName(), $strType), "list".$this->getStrCurObjectTypeName(), false, $strPagerAddon);
            $strList = $strFilterForm.$strList;

            $this->setAction($strOriginalAction);
            return $strList;
        } else {
            throw new ModelNotFoundException($this->getLang("error_model_not_found", "system"));
        }
    }

    /**
     * Callback/hook to detect sorting capabilities within a list-view
     * @param string $listIdentifier
     * @param string|null $type
     * @return bool
     * @throws Exception
     */
    protected function isListSortable(string $listIdentifier, ?string $type): bool
    {
        $sortable = false;
        $ref = new Reflection($type);
        if ($ref->hasClassAnnotation(Root::STR_SORTMANAGER_ANNOTATION)) {
            $sortable = true;
        }
        return $sortable;
    }


    /**
     * Renders the filter form.
     *
     * @param FilterBase $objFilter
     * @param string|null $strFilterUrl
     * @param boolean $bitInitiallyVisible
     * @param string $strLangActive
     * @param string $strLangInactive
     * @return string
     * @throws Exception
     */
    public function renderFilter(FilterBase $objFilter, $strFilterUrl = null, $bitInitiallyVisible = false, $strLangActive = null, $strLangInactive = null)
    {
        if ($strFilterUrl === null) {
            $arrParams = array(
                "systemid" => $this->getSystemid()
            );

            $strFilterUrl = Link::getLinkAdminHref($this->getArrModule("module"), $this->getAction(), $arrParams);
        }

        $objFilterForm = new AdminFormgeneratorFilter($objFilter->getFilterId(), $objFilter);
        $objFilterForm->setBitInitiallyVisible($bitInitiallyVisible);
        $objFilterForm->setStrLangActive($strLangActive);
        $objFilterForm->setStrLangInactive($strLangInactive);
        $objFilterForm->addField(new FormentryHidden("", AdminFormgeneratorFilter::STR_FORM_PARAM_SESSION))->setStrValue($objFilter->getSessionId());

        $strFilterForm = $objFilterForm->renderForm($strFilterUrl);

        return $strFilterForm;
    }

    /**
     * Creates the admin-form for a given object. You should specify a @formGenerator annotation in your model if you
     * want to override the default form
     *
     * @param ModelInterface|Model $objInstance
     *
     * @return AdminFormgenerator
     * @throws Exception
     */
    protected function getAdminForm(ModelInterface $objInstance)
    {
        return $this->objCurAdminForm = AdminFormgeneratorFactory::createByModel($objInstance);
    }

    /**
     * Updates the source-object based on the passed form-params
     * and synchronizes it with the database.
     *
     * @throws Exception
     * @return string "" in case of success
     * @permissions edit
     */
    protected function actionSave()
    {
        /** @var $objRecord ModelInterface|Model */
        $objRecord = null;
        $strSystemId = "";

        if ($this->getParam("mode") == "new") {
            $strType = $this->getCurObjectClassName();

            if (!is_null($strType)) {
                $objRecord = new $strType();
                $strSystemId = $this->getSystemid();
            }
        } elseif ($this->getParam("mode") == "edit") {
            $objRecord = Objectfactory::getInstance()->getObject($this->getSystemid());
        }


        if ($objRecord === null) {
            throw new ModelNotFoundException($this->getLang("error_model_not_found", "system"));
        }

        $objForm = $this->getAdminForm($objRecord);
        if (!$objForm->validateForm()) {
            if ($this->getParam("mode") === "new") {
                return $this->actionNew();
            }
            if ($this->getParam("mode") === "edit") {
                return $this->actionEdit();
            }
        }

        $objForm->updateSourceObject();
        $objRecord = $objForm->getObjSourceobject();

        $this->persistModel($objRecord, $strSystemId);

        $this->setSystemid($objRecord->getStrSystemid());

        //Check if save reload param is set
        $strReloadAction = $this->getParam(AdminFormgenerator::STR_FORM_ON_SAVE_RELOAD_PARAM);
        if ($strReloadAction == "") {
            $strReloadAction = Link::getLinkAdminHref($this->getArrModule("modul"), $this->getActionNameForClass("list", $objRecord), "&systemid=".$objRecord->getStrPrevId().($this->getParam("pe") != "" ? "&peClose=1&blockAction=1" : ""));
        }

        $this->adminReload($strReloadAction);
        return "";
    }

    /**
     * Method which persists the record to the database
     *
     * @param Model $objModel
     * @param boolean $strPrevId
     *
     * @throws Exception
     */
    protected function persistModel(Model $objModel, $strPrevId = false)
    {
        $this->objLifeCycleFactory->factory(get_class($objModel))->update($objModel, $strPrevId);
    }

    /**
     * Builds the object-path of the currently selected record.
     * Used to render the path-navigation in the backend.
     * Therefore the path from the current record up to the module-record is created based on the
     * common prev-id relation.
     * Each node is rendered using getOutputNaviEntry, so you may overwrite getOutputNaviEntry in
     * order to create the links based on an object.
     *
     * @return array
     * @see AdminEvensimpler::getOutputNaviEntry()
     */
    public function getArrOutputNaviEntries()
    {

        $strOldAction = $this->getAction();
        $this->setAction($this->strOriginalAction);
        $arrPathLinks = parent::getArrOutputNaviEntries();
        $this->setAction($strOldAction);

        $arrPath = $this->getPathArray($this->getSystemid());

        // Render additional navigation path entries for child objects.
        foreach ($arrPath as $strOneSystemid) {

            if (!validateSystemid($strOneSystemid)) {
                continue;
            }

            $objInstance = $this->objFactory->getObject($strOneSystemid);
            if ($objInstance != null) {
                $objEntry = $this->getOutputNaviEntry($objInstance);
                if ($objEntry != null) {
//                    $arrLink = splitUpLink($objEntry);
//                    if(uniStrlen($arrLink["name"] > 50))
//                        $objEntry = uniStrReplace($arrLink["name"], uniStrTrim($arrLink["name"], 50), $objEntry);
                    $arrPathLinks[] = $objEntry;
                }
            }
        }

        return $arrPathLinks;
    }


    /**
     * Overwrite to generate path navigation entries for the given object.
     * If not overwritten, the entries will be skipped and won't be included into the
     * path navigation.
     *
     * @param ModelInterface $objInstance
     *
     * @return string Navigation link.
     */
    protected function getOutputNaviEntry(ModelInterface $objInstance)
    {
        return null;
    }

    /**
     * Internal redefinition in order to match the internal, "real" action to
     * the action based on the passed, declarative action.
     *
     * @return string
     */
    public function getQuickHelp()
    {
        $strOldAction = $this->getAction();
        $this->setAction($this->strOriginalAction);
        $strQuickhelp = parent::getQuickHelp();
        $this->setAction($strOldAction);
        return $strQuickhelp;
    }


    /**
     * Internal redefinition in order to match the internal, "real" action to
     * the action based on the passed, declarative action.
     *
     * @return string
     */
    protected function getOutputActionTitle()
    {
        if ($this->getStrCurObjectTypeName() == "") {
            return $this->getOutputModuleTitle();
        } else {
            return $this->getLang($this->getObjLang()->stringToPlaceholder("modul_titel_".$this->getStrCurObjectTypeName()));
        }
    }


    public function setStrCurObjectTypeName($strCurObjectTypeName)
    {
        $this->strCurObjectTypeName = $strCurObjectTypeName;
    }

    public function getStrCurObjectTypeName()
    {
        return $this->strCurObjectTypeName;
    }

    public function setCurObjectClassName($strCurObjectTyp)
    {
        $this->strCurObjectClassName = $strCurObjectTyp;
    }

    public function getCurObjectClassName()
    {
        return $this->strCurObjectClassName;
    }

    /**
     * @param AdminFormgenerator $objCurAdminForm
     *
     * @deprecated
     */
    public function setObjCurAdminForm($objCurAdminForm)
    {
        $this->objCurAdminForm = $objCurAdminForm;
    }

    /**
     * @return AdminFormgenerator
     * @deprecated
     */
    public function getObjCurAdminForm()
    {
        return $this->objCurAdminForm;
    }


}

