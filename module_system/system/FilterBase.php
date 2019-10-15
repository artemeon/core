<?php
/*"******************************************************************************************************
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\AdminFormgeneratorContainerInterface;
use Kajona\System\Admin\AdminFormgeneratorFilter;
use ReflectionClass;


/**
 * Base filter class
 * If you want to remove fields from a filter, you may use the common "remove/default" config logic similar to common form objects.
 * Therefore the filter looks for a config entry named
 * <code>$config["filter_field_config"][FilterClass::class]["fieldname"] => ["action"]</code>
 * The config location is taken from the <code>@module</code> annotation provided by the current filter class.
 *
 * @package module_system
 * @author stefan.meyer@artemeon.de
 * @author christoph.kappestein@artemeon.de
 * @author stefan.idler@artemeon.de
 */
abstract class FilterBase
{
    const STR_ANNOTATION_FILTER_COMPARE_OPERATOR = "@filterCompareOperator";
    const STR_CONFIG_ENTRY = "filter_field_config";

    const STR_COMPAREOPERATOR_EQ = "EQ";
    const STR_COMPAREOPERATOR_GT = "GT";
    const STR_COMPAREOPERATOR_LT = "LT";
    const STR_COMPAREOPERATOR_GE = "GE";
    const STR_COMPAREOPERATOR_LE = "LE";
    const STR_COMPAREOPERATOR_NE = "NE";
    const STR_COMPAREOPERATOR_LIKE = "LIKE";
    const STR_COMPAREOPERATOR_IN = "IN";
    const STR_COMPAREOPERATOR_NOTIN = "NOTIN";
    const STR_COMPAREOPERATOR_IN_OR_EMPTY = "IN_OR_EMPTY";
    const STR_COMPAREOPERATOR_NOTIN_OR_EMPTY = "NOTIN_OR_EMPTY";

    /**
     * bit to indicate if a redirect should be executed
     * Value is set to true if a filter is being submitted (filter or reset)
     *
     * @var bool
     */
    private $bitFilterUpdated = false;

    /**
     * @var string
     */
    private $strFilterId = null;

    /**
     * @var string
     */
    private $strSessionId = null;

    /**
     * Array to store additional conditions which are not provided by the filter itself
     *
     * @var array
     */
    private $arrAdditionalConditions = array();

    /**
     * FilterBase constructor.
     *
     * @param string $strFilterId
     * @param string $strSessionId
     */
    public function __construct($strFilterId = null, $strSessionId = null)
    {
        $this->strFilterId = $strFilterId;
        $this->strSessionId = $strSessionId;

        $this->initFilter();
    }

    /**
     * Initializes the filter
     */
    protected function initFilter()
    {

    }


    /**
     * Method for setting default values.
     *
     * Being called on filter reset or if filter is not in session yet.
     * May also be called manually.
     */
    public function configureDefaultValues()
    {

    }

    /**
     * Returns the ID of the filter.
     * This ID is also being used to store the filter in the session. Please make sure to use a unique ID.
     * By Default the class name (in lower case) is being returned
     *
     * @return string
     * @throws \ReflectionException
     */
    final public function getFilterId()
    {
        if ($this->strFilterId === null) {
            $objClass = new ReflectionClass(get_called_class());
            $this->strFilterId = StringUtil::toLowerCase($objClass->getShortName());
        }

        return $this->strFilterId;
    }

    /**
     * Returns the session id under which this filter is stored. By default this is the filter id but it is also
     * possible to provide another id in case you want to store the filter under a different session key
     *
     * @return string
     * @throws \ReflectionException
     */
    public function getSessionId()
    {
        if ($this->strSessionId !== null) {
            return $this->strSessionId;
        } else {
            return $this->getFilterId();
        }
    }

    /**
     * Returns the module name.
     * The module name is being retrieved via the class annotation @module
     *
     * @param $strKey
     *
     * @return mixed
     * @throws Exception
     */
    public function getArrModule($strKey = "")
    {
        $objReflection = new Reflection($this);
        $arrAnnotationValues = $objReflection->getAnnotationValuesFromClass(AbstractController::STR_MODULE_ANNOTATION);
        if (count($arrAnnotationValues) > 0) {
            return trim($arrAnnotationValues[0]);
        }

        throw new Exception("Missing ".AbstractController::STR_MODULE_ANNOTATION." annotation for class ".__CLASS__, Exception::$level_ERROR);
    }


    /**
     * Creates a new filter object or retrieves a filter object from the session.
     * If retrieved from session a clone is being returned.
     *
     * @param string $strClass
     * @param string $strSessionId
     * @return self
     * @throws Exception
     * @throws \ReflectionException
     */
    public static function getOrCreateFromSession($strClass = null, $strSessionId = null)
    {
        /** @var FilterBase $objFilter */
        $strCalledClass = $strClass === null ? get_called_class() : $strClass;
        $objFilter = new $strCalledClass(null, $strSessionId);
        $strSessionId = $objFilter->getSessionId();

        /*
         * Check if filter form was submitted.
         * If not try to get filter from session
         */
        if (Carrier::getInstance()->getParam($objFilter->getFullParamName(AdminFormgeneratorFilter::STR_FORM_PARAM_FILTER)) != "") {
            /*
             * In case filter was reset reset, remove from session
             */
            if (Carrier::getInstance()->getParam(AdminFormgeneratorFilter::STR_FORM_PARAM_RESET) != "") {
                Session::getInstance()->sessionUnset($strSessionId);

                //if filter was reset, set default values
                $objFilter->configureDefaultValues();
            } else {
                //If no reset was triggered -> Update filter with params which have been set
                $objFilter->updateFilterPropertiesFromParams();
            }
        } else {
            /*
             * Get objFilter from Session
             */
            if (Session::getInstance()->sessionIsset($strSessionId)) {
                $objFilter = Session::getInstance()->getSession($strSessionId);
            } else {
                //in case filter is not in session yet -> set default values
                $objFilter->configureDefaultValues();
            }
        }

        /*
         * Write filter to session
         */
        $objFilter->writeFilterToSession();

        /**
         * return a clone of the filter
         * (reason to return a clone: it might be that $objFilter is the filter session object.
         *                            So changes in the object are not reflected to the session object)
         */
        return clone $objFilter;
    }


    /**
     * Updates the filter object with the values of the passed parameters
     *
     */
    public function updateFilterPropertiesFromParams()
    {
        //get properties
        $objReflection = new Reflection($this);
        $arrProperties = $objReflection->getPropertiesWithAnnotation(AdminFormgenerator::STR_TYPE_ANNOTATION);

        //get params
        $arrParams = Carrier::getAllParams();

        //set param values to filter object
        foreach ($arrProperties as $strPropertyName => $strColumnName) {
            $strSetter = $objReflection->getSetter($strPropertyName);
            if ($strSetter === null) {
                throw new Exception("unable to find setter for property ".$strPropertyName."@".get_class($this), Exception::$level_ERROR);
            }

            //create param key string
            $strPropertyWithoutPrefix = Lang::getInstance()->propertyWithoutPrefix($strPropertyName);
            $strPropertyWithoutPrefix = $this->getFullParamName($strPropertyWithoutPrefix);

            //set values to filter object
            if (array_key_exists($strPropertyWithoutPrefix, $arrParams) || array_key_exists($strPropertyWithoutPrefix."_prescheck", $arrParams)) {
                $strValueToSet = $this->convertParamValue($strPropertyWithoutPrefix, $arrParams);
                $this->$strSetter($strValueToSet);
            }
        }
    }

    /**
     * Converts the param value to the expected date
     *
     * if $strParamName contains the word "date", then $arrParams[$strParamName] will be converted to Date
     * if $strParamName"_id" exists, take this as the value
     *
     *
     * @param $strParamName
     * @param $arrParams
     *
     * @return Date|null
     */
    protected function convertParamValue($strParamName, $arrParams)
    {
        $strValue = $arrParams[$strParamName] == "" ? null : (is_string($arrParams[$strParamName]) ? urldecode($arrParams[$strParamName]) : $arrParams[$strParamName]);

        //check if _id param exists, if yes take that one
        if (array_key_exists($strParamName."_id", $arrParams)) {
            $strValue = $arrParams[$strParamName."_id"] == "" ? null : $arrParams[$strParamName."_id"];
        }

        //if no value is set, return null
        if ($strValue === null) {
            return $strValue;
        }

        //if paramname contains the word "date" -> convert to date
        if (StringUtil::indexOf($strParamName, "date") !== false) {
            $objDate = new Date();
            $objDate->generateDateFromParams($strParamName, $arrParams);
            return $objDate;
        }

        return $strValue;
    }

    /**
     * Generates ORM restrictions based on the properties of the filter.
     *
     * @return OrmCondition[]
     * @throws Exception
     */
    public function getOrmConditions()
    {
        $arrConditions = array();

        /*Handle configured conditions*/
        $objReflection = new Reflection($this);
        $arrProperties = $objReflection->getPropertiesWithAnnotation(OrmBase::STR_ANNOTATION_TABLECOLUMN);
        $arrPropertiesFilterComparator = $objReflection->getPropertiesWithAnnotation(self::STR_ANNOTATION_FILTER_COMPARE_OPERATOR);

        foreach ($arrProperties as $strAttributeName => $strTableColumn) {
            $strGetter = $objReflection->getGetter($strAttributeName);

            $enumFilterCompareOperator = null;
            if (array_key_exists($strAttributeName, $arrPropertiesFilterComparator)) {
                $enumFilterCompareOperator = $this->getFilterCompareOperator($arrPropertiesFilterComparator[$strAttributeName]);
            }

            if ($strGetter !== null) {
                $strValue = $this->$strGetter();
                if ($strValue !== null && $strValue !== "") {
                    $objRestriction = $this->getSingleOrmCondition($strAttributeName, $strValue, $strTableColumn, $enumFilterCompareOperator);
                    if ($objRestriction !== null) {
                        $arrConditions[] = $objRestriction;
                    }
                }
            }
        }

        /*Handle additional conditions*/
        foreach ($this->arrAdditionalConditions as $objCondition) {
            $arrConditions[] = $objCondition;
        }

        return $arrConditions;
    }

    /**
     * Override this method to add specific logic for certain filter attributes
     *
     * @param $strAttributeName
     * @param $strValue
     * @param $strTableColumn
     * @param OrmComparatorEnum|null $enumFilterCompareOperator
     * @param string $strCondition
     *
     * @return OrmCondition|null
     */
    protected function getSingleOrmCondition($strAttributeName, $strValue, $strTableColumn, OrmComparatorEnum $enumFilterCompareOperator = null)
    {
        return OrmCondition::getORMConditionForValue($strValue, $strTableColumn, $enumFilterCompareOperator);
    }


    /**
     * Adds all ORM restrictions to the given $objORM
     *
     * @param OrmObjectlist $objORM
     * @throws Exception
     */
    public function addWhereConditionToORM(OrmObjectlist $objORM)
    {
        /*Add configured conditions*/
        $arrConditions = $this->getOrmConditions();
        foreach ($arrConditions as $objCondition) {
            $objORM->addWhereRestriction($objCondition);
        }
    }

    /**
     * Adds an additional condition to the filter
     *
     * @param OrmConditionInterface $objCondition
     */
    public function addAdditionalCondition(OrmConditionInterface $objCondition)
    {
        $this->arrAdditionalConditions[] = $objCondition;
    }

    /**
     * Hook method to add order by conditions to the orm objectlist.
     * By default empty, but may be overwritten in case it is required
     * @param OrmObjectlist $objORM
     */
    public function addOrderByConditionToORM(OrmObjectlist $objORM)
    {

    }

    /**
     * Gets OrmComparatorEnum by the given $strFilterCompareType
     *
     * @param string $strFilterCompareType
     *
     * @return OrmComparatorEnum
     */
    private function getFilterCompareOperator($strFilterCompareType)
    {

        switch ($strFilterCompareType) {
            case self::STR_COMPAREOPERATOR_EQ:
                return OrmComparatorEnum::Equal();
            case self::STR_COMPAREOPERATOR_GT:
                return OrmComparatorEnum::GreaterThen();
            case self::STR_COMPAREOPERATOR_LT:
                return OrmComparatorEnum::LessThen();
            case self::STR_COMPAREOPERATOR_GE:
                return OrmComparatorEnum::GreaterThenEquals();
            case self::STR_COMPAREOPERATOR_LE:
                return OrmComparatorEnum::LessThenEquals();
            case self::STR_COMPAREOPERATOR_NE:
                return OrmComparatorEnum::NotEqual();
            case self::STR_COMPAREOPERATOR_LIKE:
                return OrmComparatorEnum::Like();
            case self::STR_COMPAREOPERATOR_IN:
                return OrmComparatorEnum::In();
            case self::STR_COMPAREOPERATOR_NOTIN:
                return OrmComparatorEnum::NotIn();
            case self::STR_COMPAREOPERATOR_IN_OR_EMPTY:
                return OrmComparatorEnum::InOrEmpty();
            case self::STR_COMPAREOPERATOR_NOTIN_OR_EMPTY:
                return OrmComparatorEnum::NotInOrEmpty();
            default:
                return null;
        }
    }

    /**
     * Overwrite method if specific form handling is required.
     * Method is being called when the form for the filter is being generated.
     *
     * @param AdminFormgeneratorFilter $objFilterForm
     * @throws Exception
     */
    public function updateFilterForm(AdminFormgeneratorFilter $objFilterForm)
    {

        if (!empty($this->getArrModule())) {
            $cfg = Config::getInstance("module_".$this->getArrModule())->getConfig(self::STR_CONFIG_ENTRY);

            if (!empty($cfg[get_class($this)])) {
                foreach ($cfg[get_class($this)] as $strFieldName => $arrVisibility) {
                    $field = $objFilterForm->getField($strFieldName);

                    if (in_array("remove", $arrVisibility)) {
                        $objFilterForm->removeField($strFieldName);
                    }

                    if ($field === null) {
                        $this->removeNestedField($strFieldName, $objFilterForm);
                    }
                }
            }
        }
    }

    /**
     * Tries to find an entry in a nested form-entry, so a list of subentries
     * @param string $name
     * @param AdminFormgeneratorFilter $form
     */
    private function removeNestedField(string $name, AdminFormgeneratorFilter $form)
    {
        foreach ($form->getArrFields() as $field) {
            if ($field instanceof AdminFormgeneratorContainerInterface) {
                $field->removeField($name);
            }
        }
    }

    /**
     * Write the filter to the session.
     * A clone of the filter is being written to the session.
     *
     * @throws Exception
     * @throws \ReflectionException
     */
    public function writeFilterToSession()
    {
        $objFilter = clone $this;

        $strSessionId = $objFilter->getSessionId();
        Session::getInstance()->setSession($strSessionId, $objFilter);
    }

    /**
     * Method to get the full param name (inlcuding filter id)
     *
     * @param $strParam
     *
     * @return string
     * @throws \ReflectionException
     */
    public function getFullParamName($strParam)
    {
        return $this->getFilterId()."_".$strParam;
    }

    /**
     * @return boolean
     * @deprecated
     */
    public function getBitFilterUpdated()
    {
        return $this->bitFilterUpdated;
    }

    /**
     * @param boolean $bitFilterUpdated
     * @deprecated
     */
    public function setBitFilterUpdated($bitFilterUpdated)
    {
        $this->bitFilterUpdated = $bitFilterUpdated;
    }

    /**
     * @return array
     */
    public function getArrAdditionalConditions()
    {
        return $this->arrAdditionalConditions;
    }

    /**
     * @param array $arrAdditionalConditions
     */
    public function setArrAdditionalConditions($arrAdditionalConditions)
    {
        $this->arrAdditionalConditions = $arrAdditionalConditions;
    }
}
