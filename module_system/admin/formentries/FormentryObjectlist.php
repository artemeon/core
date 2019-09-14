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

    /**
     * @var bool
     */
    protected $showAddButton = true;

    /**
     * @var bool
     */
    protected $showDeleteAllButton = true;

    /**
     * @var bool
     */
    protected $showEditButton = false;

    /**
     * @var bool
     */
    protected $showAdditionalLinkData = true;

    /**
     * @var bool
     */
    protected $showLinkObjectType = true;

    /**
     * A closure which generates the fitting link button for the entry
     *
     * @var \Closure
     */
    protected $showDetailButton;

    /**
     * @param string $strAddLink
     * @return FormentryObjectlist
     */
    public function setStrAddLink(string $strAddLink): FormentryObjectlist
    {
        $this->strAddLink = $strAddLink;

        return $this;
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
     * @return FormentryObjectlist
     */
    public function setEndpointUrl(string $endpointUrl, array $objectTypes = []): FormentryObjectlist
    {
        $this->endpointUrl = $endpointUrl;
        $this->objectTypes = $objectTypes;

        return $this;
    }

    /**
     * Bitmask consisting of OPTION_SKIP_RIGHT_CHECK
     *
     * @param int $options
     * @return FormentryObjectlist
     */
    public function setOptions(int $options): FormentryObjectlist
    {
        $this->options = $options;

        return $this;
    }

    public function setShowAdditionalLinkData(bool $showAdditionalLinkData): FormentryObjectlist
    {
        $this->showAdditionalLinkData = $showAdditionalLinkData;

        return $this;
    }

    public function setShowLinkObjectType(bool $showLinkObjectType): FormentryObjectlist
    {
        $this->showLinkObjectType = $showLinkObjectType;

        return $this;
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

    /**
     * @return mixed|string
     * @throws Exception
     */
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

    /**
     * @return bool
     */
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

    /**
     * @return string
     * @throws Exception
     * @throws \ReflectionException
     */
    public function getValueAsText()
    {
        if (empty($this->arrKeyValues)) {
            return '-';
        }

        $htmlResponse = [];

        //Collect object and sort them by create date
        $skipRightCheck = $this->options & self::OPTION_SKIP_RIGHT_CHECK;
        $objects = [];
        foreach ($this->arrKeyValues as $object) {
            if ($object instanceof Model && $object instanceof ModelInterface) {
                if ($skipRightCheck || $object->rightView()) {
                    $objects[] = $object;
                }
            } else {
                throw new Exception('Array must contain objects', Exception::$level_ERROR);
            }
        }
        $this->orderObject($objects);

        //Render content
        foreach ($objects as $object) {
            $htmlResponse[] =  $this->createDisplayLinkTextForObject($object);
        }

        return implode('<br>', $htmlResponse);
    }


    /**
     * @param ModelInterface $modelObject
     * @return string
     * @throws \ReflectionException
     */
    private function createDisplayLinkTextForObject(ModelInterface $modelObject): string
    {
        $displayLinkText = $modelObject->getStrDisplayName();

        // TODO: get rid of deprecated function usage once it is gone
        if ($this->showLinkObjectType && method_exists($this, 'getDisplayName')) {
            $displayLinkText = $this->getDisplayName($modelObject);
        }

        if ($modelObject->rightView()) {
            //see, if the matching target-module provides a showSummary method
            $moduleByName = SystemModule::getModuleByName($modelObject->getArrModule('modul'));
            if ($moduleByName !== null) {
                $moduleAdmin = $moduleByName->getAdminInstanceOfConcreteModule($modelObject->getSystemid());

                if ($moduleAdmin !== null && method_exists($moduleAdmin, 'actionShowSummary')) {
                    $displayLinkText = Link::getLinkAdminDialog($modelObject->getArrModule('modul'), 'showSummary', '&systemid='.$modelObject->getSystemid().'&folderview='.Carrier::getInstance()->getParam('folderview'), $displayLinkText);
                }
            }
        }
        if ($this->showAdditionalLinkData && $modelObject instanceof AdminListableInterface && $modelObject->rightView()) {
            $displayLinkText .= ' '.$modelObject->getStrAdditionalInfo();
        }

        return $displayLinkText;
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

        $strObjectName .= ($objObject->getIntRecordDeleted() === 1 ? ' (' .Carrier::getInstance()->getObjLang()->getLang('commons_deleted', 'system'). ')' : '');

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

        return implode(" &gt; ", array_reverse($arrPath));
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
