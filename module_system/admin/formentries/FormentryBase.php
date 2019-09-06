<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use JsonSerializable;
use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\AdminFormgeneratorContainerInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\Lang;
use Kajona\System\System\Model;
use Kajona\System\System\Reflection;
use Kajona\System\System\ReflectionEnum;
use Kajona\System\System\StringUtil;
use Kajona\System\System\ValidationError;
use Kajona\System\System\ValidatorExtendedInterface;
use Kajona\System\System\ValidatorInterface;

/**
 * The base-class for all form-entries.
 * Holds common values and common method-logic to reduce the amount
 * of own code as much as possible.
 * In addition to extending FormentryBase, make sure to implement FormentryInterface, too.
 *
 * @author sidler@mulchprod.de
 * @since 4.0
 * @package module_formgenerator
 */
abstract class FormentryBase implements JsonSerializable
{

    /**
     * @var Model
     */
    private $objSourceObject = null;

    /**
     * A string with additional form entry configurations
     */
    private const FORM_FIELD_CONFIG_ANNOTATION = '@fieldConfig';

    /**
     * The name of the property as used in the forms, leading type-prefix is removed
     *
     * @var null
     */
    private $strSourceProperty = null;
    private $strFormName = "";

    /**
     * @var ValidatorInterface
     */
    private $objValidator;

    /**
     * En-/Disables the validation of the field in total - even the "isEmpty" check is being skipped.
     * The property is evaluated outside of the formentry, normally in AdminFormgenerator
     * @var bool
     */
    private $bitSkipValidation = false;

    private $strLabel = "";
    private $strValidationErrorMsg = "";
    private $strEntryName = null;
    private $bitMandatory = false;
    private $strValue = null;
    private $strHint = null;
    private $bitReadonly = false;
    private $bitHideLongHints = false;
    private $dataAttributes = [];
    private $formParams;

    /**
     * Creates a new instance of the current field.
     *
     * @param $strFormName
     * @param $strSourceProperty
     * @param Model $objSourceObject
     * @throws Exception
     */
    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
        $this->strSourceProperty = $strSourceProperty;
        $this->objSourceObject = $objSourceObject;
        $this->strFormName = $strFormName;

        if ($strFormName !== '') {
            $strFormName .= '_';
        }

        $this->strEntryName = StringUtil::toLowerCase($strFormName.$strSourceProperty);

        if ($objSourceObject !== null) {
            $this->updateLabel();
        }

        $this->readValue();
        $this->getFormEntryConfigAnnotationValues();
    }

    /**
     * Uses the current validator and validates the current value.
     *
     * @return bool
     */
    public function validateValue()
    {
        return $this->getObjValidator()->validate($this->getStrValue());
    }

    /**
     * Updates the internal value either based on a request value or the value from
     * the object. This method is only needed in case the request parameters have changed
     * during the request and you need to update the form which may come from a cache
     * @throws Exception
     */
    final public function readValue()
    {
        if ($this->strValue == null) {
            $this->updateValue();
        }
        if ($this instanceof AdminFormgeneratorContainerInterface && !$this instanceof AbstractFormentryI18n) {
            foreach ($this->getFields() as $field) {
                $field->readValue();
            }
        }
    }

    /**
     * Queries the params-array or the source-object for the mapped value.
     * If found in the params-array, the value will be used, otherwise
     * the source-objects' getter is invoked.
     * @throws Exception
     */
    protected function updateValue()
    {
        $arrParams = $this->getFormParams();
        if (isset($arrParams[$this->strEntryName])) {
            $this->setStrValue($arrParams[$this->strEntryName]);
        } else {
            if ($this->getObjSourceObject() !== null) {
                $this->setStrValue($this->getValueFromObject());
            }
        }
    }

    /**
     * Loads the fields label-text, based on a combination of form-name and property-name.
     * The generated label may be overwritten if necessary.
     * @param string $strKey
     * @throws Exception
     */
    public function updateLabel($strKey = "")
    {
        //check if module param is set for @fieldLabel
        $strModule = $this->getAnnotationParamValueForCurrentProperty("module", AdminFormgenerator::STR_LABEL_ANNOTATION);
        if ($strModule === null) {
            $strModule = $this->objSourceObject->getArrModule("modul");
        }

        //check, if label is set as a property
        if ($strKey != "") {
            $this->setStrLabel(Carrier::getInstance()->getObjLang()->getLang($strKey, $strModule));
        } else {
            $this->setStrLabel(Carrier::getInstance()->getObjLang()->getLang("form_".$this->strFormName."_".$this->strSourceProperty, $strModule));
            $strKey = "form_".$this->strFormName."_".$this->strSourceProperty;
        }

        $strHint = $strKey."_hint";
        if (Carrier::getInstance()->getObjLang()->getLang($strHint, $strModule) != "!".$strHint."!") {
            $this->setStrHint(Carrier::getInstance()->getObjLang()->getLang($strHint, $strModule));
        }
    }

    /**
     * Calls the source-objects getter and loads the value.
     * Only used, if the field is not already populated to the
     * global params-array.
     *
     * @throws Exception
     * @return mixed
     * @internal please do not overwrite this method. use an internal state by hooking on getStrValue()/setStrValue()
     */
    protected function getValueFromObject()
    {

        if ($this->objSourceObject == null) {
            return "";
        }

        //try to get the matching getter
        $objReflection = new Reflection($this->objSourceObject);
        $strGetter = $objReflection->getGetter($this->strSourceProperty);
        if ($strGetter === null) {
            throw new Exception("unable to find getter for value-property ".$this->strSourceProperty."@".get_class($this->objSourceObject), Exception::$level_ERROR);
        }

        return $this->objSourceObject->{$strGetter}();
    }

    /**
     * Calls the source-objects setter and stores the value.
     * If you want to skip a single setter, remove the field before.
     *
     * @throws Exception
     * @return mixed
     * @internal please do not overwrite this method. use an internal state by hooking on getStrValue()/setStrValue()
     */
    public function setValueToObject()
    {

        if ($this->objSourceObject == null) {
            return "";
        }
        //$this->readValue();

        $objReflection = new Reflection($this->objSourceObject);
        $strSetter = $objReflection->getSetter($this->strSourceProperty);
        if ($strSetter === null) {
            throw new Exception("unable to find setter for value-property ".$this->strSourceProperty."@".get_class($this->objSourceObject), Exception::$level_ERROR);
        }

        return $this->objSourceObject->{$strSetter}($this->getStrValue());
    }

    /**
     * Checks if the field value is empty
     *
     * @return bool
     */
    public function isFieldEmpty()
    {
        $strValue = $this->getStrValue();
        return (!is_array($strValue) && trim($strValue) === "")
            || is_null($strValue)
            || (is_array($strValue)
                && (count($strValue) == 0 //if it is an array with no entries
                    || (count($strValue) == 1 && isset($strValue[0]) && $strValue[0] === "")) //if it is an array with only one empty value
            );
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            "type" => str_replace("\\", "", get_class($this)),
            "name" => $this->getStrEntryName(),
            "label" => $this->getStrLabel(),
            "readonly" => $this->getBitReadonly(),
            "mandatory" => $this->getBitMandatory(),
            "hint" => $this->getStrHint(),
            "value" => $this->getStrValue(),
        ];
    }

    /**
     * @param bool $bitMandatory
     *
     * @return FormentryBase
     */
    public function setBitMandatory($bitMandatory)
    {
        $this->bitMandatory = $bitMandatory;
        if ($this instanceof AdminFormgeneratorContainerInterface) {
            foreach ($this->getFields() as $field) {
                $field->setBitMandatory($bitMandatory);
            }
        }
        return $this;
    }

    public function getBitMandatory()
    {
        return $this->bitMandatory;
    }

    /**
     * @param string $strLabel
     *
     * @return FormentryBase
     */
    public function setStrLabel($strLabel)
    {
        $this->strLabel = $strLabel;
        return $this;
    }

    public function getStrLabel()
    {
        return $this->strLabel;
    }

    /**
     * @param ValidatorInterface $objValidator
     *
     * @return FormentryBase
     */
    public function setObjValidator(ValidatorInterface $objValidator)
    {
        $this->objValidator = $objValidator;
        return $this;
    }

    /**
     * @return ValidatorInterface
     */
    public function getObjValidator()
    {
        return $this->objValidator;
    }

    public function setStrFormName($strFormName)
    {
        $this->strFormName = $strFormName;
    }

    public function getStrFormName()
    {
        return $this->strFormName;
    }

    /**
     * @param $strEntryName
     *
     * @return FormentryBase
     */
    public function setStrEntryName($strEntryName)
    {
        $this->strEntryName = $strEntryName;
        return $this;
    }

    public function getStrEntryName()
    {
        return $this->strEntryName;
    }

    /**
     * @param $strValue
     *
     * @return FormentryBase
     */
    public function setStrValue($strValue)
    {
        $this->strValue = $strValue;
        return $this;
    }

    public function getStrValue()
    {
        return $this->strValue;
    }

    /**
     * @param $strHint
     *
     * @return FormentryBase
     */
    public function setStrHint($strHint)
    {
        if (trim($strHint) != "") {
            $strHint = nl2br($strHint);
        }
        $this->strHint = $strHint;
        return $this;
    }

    public function getStrHint()
    {
        return $this->strHint;
    }

    /**
     * @param $bitReadonly
     *
     * @return FormentryBase
     */
    public function setBitReadonly($bitReadonly)
    {
        $this->bitReadonly = $bitReadonly;
        return $this;
    }

    public function getBitReadonly()
    {
        return $this->bitReadonly;
    }

    public function getStrSourceProperty()
    {
        return $this->strSourceProperty;
    }

    /**
     * @param string $strSourceProperty
     */
    public function setStrSourceProperty($strSourceProperty)
    {
        $this->strSourceProperty = $strSourceProperty;
    }

    public function getObjSourceObject()
    {
        return $this->objSourceObject;
    }

    /**
     * @param Model $objSourceObject
     */
    public function setObjSourceObject($objSourceObject)
    {
        $this->objSourceObject = $objSourceObject;
    }

    public function setStrValidationErrorMsg($strValidationErrorMsg)
    {
        $this->strValidationErrorMsg = $strValidationErrorMsg;
        return $this;
    }

    /**
     * @return ValidationError[]
     */
    public function getValidationErrorMsg()
    {
        if ($this->strValidationErrorMsg != "") {
            return [new ValidationError($this->strValidationErrorMsg)];
        } else {
            $objValidator = $this->getObjValidator();
            if ($objValidator instanceof ValidatorExtendedInterface) {
                return $objValidator->getValidationMessages();
            } else {
                return [new ValidationError($this->getStrLabel())];
            }
        }
    }

    /**
     * Gets the real property name for the current field, e.g. arrStatus, strTitle etc..
     *
     * @param string $strAnnotation
     *
     * @return int|null|string
     * @throws Exception
     */
    protected function getCurrentProperty($strAnnotation = AdminFormgenerator::STR_TYPE_ANNOTATION)
    {
        $strSourceProperty = null;

        if ($this->getObjSourceObject() != null) {
            $objReflection = new Reflection($this->getObjSourceObject());

            $arrProperties = $objReflection->getPropertiesWithAnnotation($strAnnotation);
            $strSourceProperty = null;
            foreach ($arrProperties as $strPropertyName => $strValue) {
                $strPropertyWithoutPrefix = Lang::getInstance()->propertyWithoutPrefix($strPropertyName);

                if ($strPropertyWithoutPrefix == $this->getStrSourceProperty()) {
                    $strSourceProperty = $strPropertyName;
                    break;
                }
            }
        }

        return $strSourceProperty;
    }

    /**
     * Gets the params for the current property and annotation
     *
     * @param string $strAnnotation
     *
     * @return array|null|string
     * @throws Exception
     */
    protected function getAnnotationParamsForCurrentProperty($strAnnotation = AdminFormgenerator::STR_TYPE_ANNOTATION)
    {
        $strSourceProperty = $this->getCurrentProperty($strAnnotation);
        if ($strSourceProperty !== null) {
            $objReflection = new Reflection($this->getObjSourceObject());
            return $objReflection->getAnnotationValueForProperty($strSourceProperty, $strAnnotation, ReflectionEnum::PARAMS);
        }

        return array();
    }

    /**
     * Gets the param value for the current property ,annotation and param name
     *
     * @param $strParamName
     * @param string $strAnnotation
     *
     * @return mixed|null
     * @throws Exception
     */
    protected function getAnnotationParamValueForCurrentProperty($strParamName, $strAnnotation = AdminFormgenerator::STR_TYPE_ANNOTATION)
    {
        $arrParams = $this->getAnnotationParamsForCurrentProperty($strAnnotation);

        if (is_array($arrParams) && array_key_exists($strParamName, $arrParams)) {
            return $arrParams[$strParamName];
        }

        return null;
    }

    /**
     * @return bool
     */
    public function getBitSkipValidation(): bool
    {
        return $this->bitSkipValidation;
    }

    /**
     * @param bool $bitSkipValidation
     */
    public function setBitSkipValidation(bool $bitSkipValidation)
    {
        $this->bitSkipValidation = $bitSkipValidation;
    }

    /**
     * @return bool
     */
    public function getBitHideLongHints(): bool
    {
        return $this->bitHideLongHints;
    }

    /**
     * @param bool $bitHideLongHints
     */
    public function setBitHideLongHints(bool $bitHideLongHints)
    {
        $this->bitHideLongHints = $bitHideLongHints;

        return $this;
    }

    /**
     * get @FormEntryConfig Annotation (json format like {"configSetting1": true, "configSetting2": false})
     * into usable config settings e.g. $this->configSetting1 = true, $this->configSetting2 = false
     * @throws Exception
     */
    private function getFormEntryConfigAnnotationValues(): void
    {
        if ($this->getObjSourceObject() === null || $this->getStrSourceProperty() === '') {
            return;
        }
        if (!$currentSourceProperty = $this->getCurrentProperty(self::FORM_FIELD_CONFIG_ANNOTATION)) {
            return;
        }

        $reflection = new Reflection($this->getObjSourceObject());
        if (empty($propertyAnnotationValues = $reflection->getAnnotationJsonValueForProperty($currentSourceProperty, self::FORM_FIELD_CONFIG_ANNOTATION))) {
            return;
        }

        foreach ($propertyAnnotationValues as $configName => $configValue) {
            $this->$configName = (bool) $configValue;
        }
    }


    /**
     * @return array
     */
    public function getDataAttributes(): array
    {
        return $this->dataAttributes;
    }

    /**
     * @param array $dataAttributes
     * @return FormentryBase
     */
    public function setDataAttributes(array $dataAttributes): FormentryBase
    {
        $this->dataAttributes = $dataAttributes;
        return $this;
    }

    /**
     * @return array
     */
    public function getFormParams(): array
    {
        return $this->formParams ?? Carrier::getAllParams();
    }

    /**
     * @param array|null $formParams
     */
    public function setFormParams(?array $formParams): void
    {
        $this->formParams = $formParams;
    }
}
