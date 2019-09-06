<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use ArrayObject;
use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Reflection;
use Kajona\System\System\StringUtil;
use Kajona\System\System\Validators\DummyValidator;
use Kajona\System\View\Components\Formentry\Checkboxarray\Checkboxarray;

/**
 * A formelement rendering an array of checkboxes.
 * Requires both, a set of possible options and the set of options currently selected.
 *
 * @author sidler@mulchprod.de
 * @since 4.8
 * @package module_formgenerator
 */
class FormentryCheckboxarray extends FormentryBase implements FormentryPrintableInterface
{

    const TYPE_CHECKBOX = 1;
    const TYPE_RADIO = 2;

    private $intType = 1;
    private $bitInline = false;
    private $arrKeyValues = array();

    /**
     * a list of [key=>value],[key=>value] pairs, resolved from the language-files
     */
    const STR_VALUES_ANNOTATION = "@fieldValues";

    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        //set the default validator
        $this->setObjValidator(new DummyValidator());
    }

    public function setIntType($intType)
    {
        $this->intType = $intType;

        return $this;
    }

    /**
     * @return int
     */
    public function getIntType(): int
    {
        return $this->intType;
    }



    public function setBitInline($bitInline)
    {
        $this->bitInline = $bitInline;

        return $this;
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
            $strReturn .= $objToolkit->formTextHint($this->getStrHint(), $this->getBitHideLongHints());
        }

        $cmp = new Checkboxarray($this->getStrEntryName(), $this->getStrLabel(), $this->arrKeyValues, $this->getStrValue());
        $cmp->setType($this->intType);
        $cmp->setInline($this->bitInline);
        $cmp->setReadOnly($this->getBitReadonly());
        $cmp->setDataArray($this->getDataAttributes());

        $strReturn .= $cmp->renderComponent();
        $strReturn .= $objToolkit->formInputHidden($this->getPresCheckKey(), "1");

        return $strReturn;
    }

    /**
     * Creates a pres-check key to detect de-selected entries
     * @return string
     */
    final protected function getPresCheckKey()
    {
        return StringUtil::replace(["[", "]"], "", $this->getStrEntryName()."_prescheck");
    }

    /**
     * @param $strValue
     *
     * @return FormentryBase
     */
    public function setStrValue($strValue)
    {

        if (is_string($strValue)) {
            return parent::setStrValue(explode(",", $strValue));
        }

        $arrTargetValues = array();

        if ((is_array($strValue) || $strValue instanceof ArrayObject) && count($strValue) > 0) {
            foreach ($strValue as $strKey => $strSingleValue) {
                //DB vals
                if (is_object($strSingleValue)) {
                    $arrTargetValues[] = $strSingleValue->getSystemid();
                } //POST vals
                elseif (!empty($strSingleValue)) {//on = from generic list
                    $arrTargetValues[] = $strKey;
                }
            }
        }

        return parent::setStrValue($arrTargetValues);
    }

    /**
     * @inheritDoc
     */
    protected function updateValue()
    {
        $arrParams = $this->getFormParams();
        if (isset($arrParams[$this->getStrEntryName()])) {
            $this->setStrValue($arrParams[$this->getStrEntryName()]);
        } elseif (isset($arrParams[$this->getPresCheckKey()])) {
            $this->setStrValue(array());
        } else {
            $this->setStrValue($this->getValueFromObject());
        }


        //try to find the matching source property
        $strSourceProperty = $this->getCurrentProperty(self::STR_VALUES_ANNOTATION);
        if ($strSourceProperty == null) {
            return;
        }

        //set dd values
        if ($this->getObjSourceObject() != null && $this->getStrSourceProperty() != "") {
            $objReflection = new Reflection($this->getObjSourceObject());
            $strDDValues = $objReflection->getAnnotationValueForProperty($strSourceProperty, self::STR_VALUES_ANNOTATION);
            $strModule = $this->getAnnotationParamValueForCurrentProperty("module", self::STR_VALUES_ANNOTATION);
            if ($strModule === null) {
                $strModule = $this->getObjSourceObject()->getArrModule("modul");
            }

            $arrDDValues = FormentryDropdown::convertDDValueStringToArray($strDDValues, $strModule);
            if ($arrDDValues !== null) {
                $this->setArrKeyValues($arrDDValues);
            }
        }
    }


    /**
     * @return array
     */
    public function getArrKeyValues()
    {
        return $this->arrKeyValues;
    }

    /**
     * @param array $arrKeyValues
     *
     * @return $this
     */
    public function setArrKeyValues($arrKeyValues)
    {
        $this->arrKeyValues = $arrKeyValues;

        return $this;
    }

    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @return string
     */
    public function getValueAsText()
    {
        $arrNew = array();
        foreach ($this->getStrValue() as $strOneId) {
            if (validateSystemid($strOneId)) {
                $arrNew[] = Objectfactory::getInstance()->getObject($strOneId)->getStrDisplayName();
            } elseif (isset($this->arrKeyValues[$strOneId])) {
                $arrNew[] = $this->arrKeyValues[$strOneId];
            }
        }
        return implode("<br />", $arrNew);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), [
            "values" => (object) $this->arrKeyValues
        ]);
    }
}
