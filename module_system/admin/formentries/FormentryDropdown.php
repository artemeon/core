<?php
/*"******************************************************************************************************
*   (c) 2007-2015 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Link;
use Kajona\System\System\Reflection;
use Kajona\System\System\Validators\TextValidator;


/**
 * A yes-no field renders a dropdown containing a list of entries.
 * Make sure to pass the list of possible entries before rendering the form.
 *
 * @author sidler@mulchprod.de
 * @since 4.0
 * @package module_formgenerator
 */
class FormentryDropdown extends FormentryBase implements FormentryPrintableInterface {

    /**
     * a list of [key=>value],[key=>value] pairs, resolved from the language-files
     */
    const STR_DDVALUES_ANNOTATION = "@fieldDDValues";


    private $arrKeyValues = array();
    private $strAddons = "";
    private $strDataPlaceholder = "";
    private $bitRenderReset = false;

    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null) {
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        //set the default validator
        $this->setObjValidator(new TextValidator());
    }

    /**
     * Renders the field itself.
     * In most cases, based on the current toolkit.
     *
     * @return string
     */
    public function renderField() {
        $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $strReturn = "";
        if($this->getStrHint() != null)
            $strReturn .= $objToolkit->formTextRow($this->getStrHint());

        $strOpener = "";
        if($this->bitRenderReset) {
            $strOpener = " ".Link::getLinkAdminManual(
                "href=\"#\" onclick=\"$('#".$this->getStrEntryName()."').val('');return false;\"",
                "",
                    Carrier::getInstance()->getObjLang()->getLang("commons_reset", "prozessverwaltung"),
                "icon_delete"
            );
        }

        $strReturn.=$objToolkit->formInputDropdown($this->getStrEntryName(), $this->arrKeyValues, $this->getStrLabel(), $this->getStrValue(), "", !$this->getBitReadonly(), $this->getStrAddons(), $this->getStrDataPlaceholder(), $strOpener);
        return $strReturn;
    }

    /**
     * Overwritten in order to load key-value pairs declared by annotations
     */
    protected function updateValue() {
        parent::updateValue();

        if($this->getObjSourceObject() != null && $this->getStrSourceProperty() != "") {
            $objReflection = new Reflection($this->getObjSourceObject());

            //try to find the matching source property
            $arrProperties = $objReflection->getPropertiesWithAnnotation(self::STR_DDVALUES_ANNOTATION);
            $strSourceProperty = null;
            foreach($arrProperties as $strPropertyName => $strValue) {
                if(uniSubstr(uniStrtolower($strPropertyName), (uniStrlen($this->getStrSourceProperty()))*-1) == $this->getStrSourceProperty())
                    $strSourceProperty = $strPropertyName;
            }

            if($strSourceProperty == null)
                return;

            $strDDValues = $objReflection->getAnnotationValueForProperty($strSourceProperty, self::STR_DDVALUES_ANNOTATION);
            if($strDDValues !== null && $strDDValues != "") {
                $arrDDValues = array();
                foreach(explode(",", $strDDValues) as $strOneKeyVal) {
                    $strOneKeyVal = uniSubstr(trim($strOneKeyVal), 1, -1);
                    $arrOneKeyValue = explode("=>", $strOneKeyVal);

                    $strKey = trim($arrOneKeyValue[0]) == "" ? " " : trim($arrOneKeyValue[0]);
                    if(count($arrOneKeyValue) == 2) {
                        $strValue = Carrier::getInstance()->getObjLang()->getLang(trim($arrOneKeyValue[1]), $this->getObjSourceObject()->getArrModule("modul"));
                        if($strValue == "!".trim($arrOneKeyValue[1])."!")
                            $strValue = $arrOneKeyValue[1];
                        $arrDDValues[$strKey] = $strValue;
                    }
                }
                $this->setArrKeyValues($arrDDValues);
            }
        }
    }

    public function validateValue() {
        return in_array($this->getStrValue(), array_keys($this->arrKeyValues));
    }


    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @return string
     */
    public function getValueAsText() {
        return isset($this->arrKeyValues[$this->getStrValue()]) ? $this->arrKeyValues[$this->getStrValue()] : "";
    }

    /**
     * @param $arrKeyValues
     * @return FormentryDropdown
     */
    public function setArrKeyValues($arrKeyValues) {
        $this->arrKeyValues = $arrKeyValues;
        return $this;
    }

    public function getArrKeyValues() {
        return $this->arrKeyValues;
    }

    /**
     * @param string $strAddons
     * @return $this
     */
    public function setStrAddons($strAddons) {
        $this->strAddons = $strAddons;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrAddons() {
        return $this->strAddons;
    }

    /**
     * @return string
     */
    public function getStrDataPlaceholder() {
        return $this->strDataPlaceholder;
    }

    /**
     * @param string $strDataPlaceholder
     * @return $this
     */
    public function setStrDataPlaceholder($strDataPlaceholder) {
        $this->strDataPlaceholder = $strDataPlaceholder;
            return $this;
    }

    /**
     * @return boolean
     */
    public function getBitRenderReset() {
        return $this->bitRenderReset;
    }

    /**
     * @param boolean $bitRenderReset
     */
    public function setBitRenderReset($bitRenderReset) {
        $this->bitRenderReset = $bitRenderReset;
    }





}
