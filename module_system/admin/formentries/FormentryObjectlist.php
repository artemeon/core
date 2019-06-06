<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Reflection;
use Kajona\System\System\Root;
use Kajona\System\System\SystemModule;
use Kajona\System\View\Components\Formentry\Objectlist\Objectlist;
use ReflectionClass;
use Traversable;

/**
 * An list of objects which can be added or removed.
 *
 * @author christoph.kappestein@gmail.com
 * @since 4.7
 * @package module_formgenerator
 */
class FormentryObjectlist extends FormentryBase implements FormentryPrintableInterface
{
    const OPTION_SKIP_RIGHT_CHECK = 1;

    /**
     * @var string
     */
    protected $strAddLink;

    /**
     * @var string
     */
    protected $endpointUrl;

    /**
     * @var array
     */
    protected $objectTypes;

    /**
     * @var array
     */
    protected $arrKeyValues = array();

    /**
     * @var int
     */
    protected $options = 0;

    /** @var bool */
    protected $showAddButton = true;

    /** @var bool */
    protected $showDeleteAllButton = true;

    /** @var bool */
    protected $showEditButton = false;

    /**
     * A closure which generates the fitting link button for the entry
     *
     * @var \Closure
     */
    protected $showDetailButton;

    /**
     * @param string $strAddLink
     */
    public function setStrAddLink($strAddLink)
    {
        $this->strAddLink = $strAddLink;
    }

    /**
     * @return string
     */
    public function getStrAddLink()
    {
        return $this->strAddLink;
    }

    /**
     * @param string $endpointUrl
     * @param array $objectTypes
     */
    public function setEndpointUrl($endpointUrl, array $objectTypes = [])
    {
        $this->endpointUrl = $endpointUrl;
        $this->objectTypes = $objectTypes;
    }

    /**
     * Bitmask consisting of OPTION_SKIP_RIGHT_CHECK
     *
     * @param int $options
     */
    public function setOptions(int $options)
    {
        $this->options = $options;
    }

    protected function updateValue()
    {
        $arrParams = Carrier::getAllParams();

        $strEntryName = $this->getStrEntryName();
        $strEntryNameEmpty = $strEntryName."_empty";

        if (isset($arrParams[$strEntryName])) {
            $this->setStrValue($arrParams[$strEntryName]);
        } elseif (isset($arrParams[$strEntryNameEmpty])) {
            $this->setStrValue("");
        } else {
            $this->setStrValue($this->getValueFromObject());
        }
    }

    /**
     * Renders the field itself.
     * In most cases, based on the current toolkit.
     *
     * @return string
     */
    public function renderField()
    {
        $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $strReturn = "";
        if ($this->getStrHint() != null) {
            $strReturn .= $objToolkit->formTextRow($this->getStrHint());
        }

        // filter objects
        $skipRightCheck = $this->options & self::OPTION_SKIP_RIGHT_CHECK;

        if ($skipRightCheck) {
            $arrObjects = $this->arrKeyValues;
        } else {
            $arrObjects = array_values(array_filter($this->arrKeyValues, function ($objObject) {
                if ($objObject instanceof Root) {
                    return $objObject->rightView();
                }
                return false;
            }));
        }

        $this->orderObject($arrObjects);

        $objectList = new Objectlist($this->getStrEntryName(), $this->getStrLabel(), $arrObjects);
        $objectList->setReadOnly($this->getBitReadonly());
        $objectList->setAddLink($this->strAddLink);
        $objectList->setSearchInput($this->endpointUrl, $this->objectTypes);
        $objectList->setShowAddButton($this->isShowAddButton());
        $objectList->setShowDeleteAllButton($this->isShowDeleteAllButton());
        $objectList->setShowEditButton($this->isShowEditButton());
        $objectList->setShowDetailButton($this->showDetailButton);
        $strReturn .= $objectList->renderComponent();

        return $strReturn;
    }

    public function setStrValue($strValue)
    {
        $arrValuesIds = array();
        if (is_array($strValue) || $strValue instanceof Traversable) {
            foreach ($strValue as $objValue) {
                if ($objValue instanceof Model) {
                    $arrValuesIds[] = $objValue->getStrSystemid();
                } else {
                    $arrValuesIds[] = $objValue;
                }
            }
        }
        $strValue = implode(",", $arrValuesIds);

        $objReturn = parent::setStrValue($strValue);
        $this->setArrKeyValues($this->toObjectArray());

        return $objReturn;
    }

    public function setValueToObject()
    {
        $objSourceObject = $this->getObjSourceObject();
        if ($objSourceObject == null) {
            return "";
        }

        $objReflection = new Reflection($objSourceObject);

        // get database object which we can not change
        $strGetter = $objReflection->getGetter($this->getStrSourceProperty());
        if ($strGetter === null) {
            throw new Exception("unable to find getter for value-property ".$this->getStrSourceProperty()."@".get_class($objSourceObject), Exception::$level_ERROR);
        }


        $arrObjects = $objSourceObject->{$strGetter}();
        count($arrObjects);//Keep this code here! Just to initializes the array due to lazy load
        $arrNotObjects = array_values(array_filter((array)$arrObjects, function (Model $objObject) {
            return !$objObject->rightView();
        }));

        // merge objects
        $arrNewObjects = array_merge($this->toObjectArray(), $arrNotObjects);

        // filter double object ids
        $arrObjects = array();
        foreach ($arrNewObjects as $objObject) {
            if ($objObject instanceof Root) {
                $arrObjects[$objObject->getStrSystemid()] = $objObject;
            }
        }
        $arrObjects = array_values($arrObjects);

        // set value
        $strSetter = $objReflection->getSetter($this->getStrSourceProperty());
        if ($strSetter === null) {
            throw new Exception("unable to find setter for value-property ".$this->getStrSourceProperty()."@".get_class($objSourceObject), Exception::$level_ERROR);
        }

        return $objSourceObject->{$strSetter}($arrObjects);
    }

    public function validateValue()
    {
        $arrIds = explode(",", $this->getStrValue());
        foreach ($arrIds as $strId) {
            if (!validateSystemid($strId)) {
                return false;
            }
        }

        return true;
    }

    public function getValueAsText()
    {

        if (!empty($this->arrKeyValues)) {
            $strHtml = "";

            //Collect object and sort them by create date
            $skipRightCheck = $this->options & self::OPTION_SKIP_RIGHT_CHECK;
            $objects = [];
            foreach ($this->arrKeyValues as $objObject) {
                if ($objObject instanceof Model && $objObject instanceof ModelInterface) {
                    if ($skipRightCheck || $objObject->rightView()) {
                        $objects[] = $objObject;
                    }
                } else {
                    throw new Exception("Array must contain objects", Exception::$level_ERROR);
                }
            }
            $this->orderObject($objects);

            //Render content
            foreach ($objects as $objObject) {
                $strTitle = self::getDisplayName($objObject);
                if ($objObject->rightView()) {
                    //see, if the matching target-module provides a showSummary method
                    $objModule = SystemModule::getModuleByName($objObject->getArrModule("modul"));
                    if ($objModule != null) {
                        $objAdmin = $objModule->getAdminInstanceOfConcreteModule($objObject->getSystemid());

                        if ($objAdmin !== null && method_exists($objAdmin, "actionShowSummary")) {
                            $strTitle = Link::getLinkAdminDialog($objObject->getArrModule("modul"), "showSummary", "&systemid=".$objObject->getSystemid()."&folderview=".Carrier::getInstance()->getParam("folderview"), $strTitle);
                        }
                    }
                }
                $strHtml .= $strTitle;
                if ($objObject instanceof AdminListableInterface && $objObject->rightView()) {
                    $strHtml .= " ".$objObject->getStrAdditionalInfo();
                }
                $strHtml .= "<br />";
            }
            return $strHtml;
        }

        return "-";
    }


    /**
     * @param array $arrObjects
     */
    private function orderObject(array &$arrObjects) {
        //Name
        uasort($arrObjects, function (ModelInterface $a, ModelInterface $b) {
            return strcmp($a->getStrDisplayName(), $b->getStrDisplayName());
        });
    }

    private function toObjectArray()
    {
        $strValue = $this->getStrValue();
        if (!empty($strValue)) {
            $arrIds = explode(",", $strValue);
            $arrObjects = array_map(function ($strId) {
                return Objectfactory::getInstance()->getObject($strId);
            }, $arrIds);
            return $arrObjects;
        }

        return array();
    }

    /**
     * Renders the display name for the object and, if possible, also the object type
     *
     * @param ModelInterface|AdminListableInterface $objObject
     * @return string
     * @throws \ReflectionException
     * @deprecated
     */
    public static function getDisplayName(ModelInterface $objObject)
    {
        $strObjectName = "";

        $objClass = new ReflectionClass(get_class($objObject)); //TODO remove hardcoded cross-module dependencies
        if (SystemModule::getModuleByName("aufgaben") !== null && $objClass->implementsInterface('AGP\Aufgaben\System\AufgabenTaskableInterface')) {
            $strObjectName .= "[".$objObject->getStrTaskCategory()."] ";
        } elseif ($objClass->implementsInterface('Kajona\System\System\VersionableInterface')) {
            $strObjectName .= "[".$objObject->getVersionRecordName()."] ";
        }

        $strObjectName .= strip_tags($objObject->getStrDisplayName());

        return $strObjectName;
    }


    /**
     * @param ModelInterface $objOneElement
     * @param string $intAllowedLevel
     * @return string
     * @deprecated
     */
    public static function getPathName(ModelInterface $objOneElement)
    {
        //fetch the process-path, at least two levels
        $arrParents = $objOneElement->getPathArray();

        // remove first two nodes
        if (count($arrParents) >= 2) {
            array_shift($arrParents);
            array_shift($arrParents);
        }

        //remove current element
        array_pop($arrParents);


        //Only return three levels
        $arrPath = array();
        for ($intI = 0; $intI < 3; $intI++) {
            $strPathId = array_pop($arrParents);
            if (!validateSystemid($strPathId)) {
                break;
            }

            $objObject = Objectfactory::getInstance()->getObject($strPathId);
            $arrPath[] = $objObject->getStrDisplayName();
        }

        if (count($arrPath) == 0) {
            return "";
        }

        $strPath = implode(" &gt; ", array_reverse($arrPath));
        return $strPath;
    }

    /**
     * @param $arrKeyValues
     *
     * @return FormentryObjectlist
     */
    public function setArrKeyValues($arrKeyValues)
    {
        $this->arrKeyValues = $arrKeyValues;
        return $this;
    }

    public function getArrKeyValues()
    {
        return $this->arrKeyValues;
    }

    /**
     * @return bool
     */
    public function isShowAddButton(): bool
    {
        return $this->showAddButton;
    }

    /**
     * @param bool $showAddButton
     * @return FormentryObjectlist
     */
    public function setShowAddButton(bool $showAddButton)
    {
        $this->showAddButton = $showAddButton;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowDeleteAllButton(): bool
    {
        return $this->showDeleteAllButton;
    }

    /**
     * @param bool $showDeleteAllButton
     * @return FormentryObjectlist
     */
    public function setShowDeleteAllButton(bool $showDeleteAllButton)
    {
        $this->showDeleteAllButton = $showDeleteAllButton;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowEditButton(): bool
    {
        return $this->showEditButton;
    }

    /**
     * @param bool $showEditButton
     * @return FormentryObjectlist
     */
    public function setShowEditButton(bool $showEditButton): FormentryObjectlist
    {
        $this->showEditButton = $showEditButton;
        return $this;
    }

    /**
     * @param \Closure $showDetailButton
     * @return FormentryObjectlist
     */
    public function setShowDetailButton(\Closure $showDetailButton): FormentryObjectlist
    {
        $this->showDetailButton = $showDetailButton;
        return $this;
    }
}
