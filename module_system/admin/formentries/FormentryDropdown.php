<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use Kajona\Packagemanager\System\PackagemanagerManager;
use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\DropdownLoaderInterface;
use Kajona\System\System\Link;
use Kajona\System\System\Reflection;
use Kajona\System\System\ServiceProvider;
use Kajona\System\System\StringUtil;
use Kajona\System\System\Validators\TextValidator;
use Kajona\System\View\Components\Formentry\Dropdown\Dropdown;


/**
 * A yes-no field renders a dropdown containing a list of entries.
 * Make sure to pass the list of possible entries before rendering the form.
 *
 * @author sidler@mulchprod.de
 * @since 4.0
 * @package module_formgenerator
 */
class FormentryDropdown extends FormentryBase implements FormentryPrintableInterface
{
    /**
     * a list of [key=>value],[key=>value] pairs, resolved from the language-files
     */
    const STR_DDVALUES_ANNOTATION = "@fieldDDValues";

    /**
     * A string where to load the fitting dd values
     */
    const STR_DDPROVIDER_ANNOTATION = "@fieldDDProvider";

    private $arrKeyValues = array();
    private $strAddons = "";
    private $strDataPlaceholder = "";
    private $bitRenderReset = false;
    private $iconValues = false;

    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
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
    public function renderField()
    {
        $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $strReturn = "";
        if ($this->getStrHint() != null) {
            $strReturn .= $objToolkit->formTextHint($this->getStrHint(), $this->getBitHideLongHints());
        }

        $strOpener = "";
        if ($this->bitRenderReset) {
            $strOpener = " " . Link::getLinkAdminManual(
                    "href=\"#\" onclick=\"$('#" . $this->getStrEntryName() . "').val('');return false;\"",
                    "",
                    Carrier::getInstance()->getObjLang()->getLang("commons_reset", "prozessverwaltung"),
                    "icon_delete"
                );
        }


        $dropdown = new Dropdown($this->getStrEntryName(), $this->getStrLabel(), $this->arrKeyValues, $this->getStrValue());
        $dropdown->setReadOnly($this->getBitReadonly());
        $dropdown->setOpener($strOpener);
        $dropdown->setAddons($this->getStrAddons());
        $dropdown->setIconValues($this->isIconValues());

        if (!empty($this->getStrDataPlaceholder())) {
            $dropdown->setData('placeholder', $this->getStrDataPlaceholder());
        }
        foreach ($this->getDataAttributes() as $key => $val) {
            $dropdown->setData($key, $val);
        }

        $strReturn .= $dropdown->renderComponent();
        return $strReturn;
    }

    /**
     * Overwritten in order to load key-value pairs declared by annotations
     */
    protected function updateValue()
    {
        parent::updateValue();

        if ($this->getObjSourceObject() != null && $this->getStrSourceProperty() != "") {
            $objReflection = new Reflection($this->getObjSourceObject());

            //try to find the matching source property
            $strSourceProperty = $this->getCurrentProperty(self::STR_DDVALUES_ANNOTATION);
            if ($strSourceProperty == null) {
                $strSourceProperty = $this->getCurrentProperty(self::STR_DDPROVIDER_ANNOTATION);
                if ($strSourceProperty == null) {
                    return;
                }
            }

            // check whether dd provider is available and load values
            $arrDDValues = null;
            $strDDProvider = $objReflection->getAnnotationValueForProperty($strSourceProperty, self::STR_DDPROVIDER_ANNOTATION);

            if (!empty($strDDProvider)) {
                // load values through the dropdown loader
                /** @var DropdownLoaderInterface $loader */
                $loader = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_DROPDOWN_LOADER);

                $arrParams = $this->getAnnotationParamsForCurrentProperty(self::STR_DDPROVIDER_ANNOTATION);
                if (($this->checkIfModuleExists($arrParams))) {
                    $arrDDValues = $loader->fetchValues($strDDProvider, is_array($arrParams) ? $arrParams : []);
                }
            } else {
                // load values from the annotations
                $strDDValues = $objReflection->getAnnotationValueForProperty($strSourceProperty, self::STR_DDVALUES_ANNOTATION);
                $strModule = $this->getAnnotationParamValueForCurrentProperty("module", self::STR_DDVALUES_ANNOTATION);
                if ($strModule === null) {
                    $strModule = $this->getObjSourceObject()->getArrModule("modul");
                }

                $arrDDValues = self::convertDDValueStringToArray($strDDValues, $strModule);
            }

            if ($arrDDValues !== null) {
                $this->setArrKeyValues($arrDDValues);
            }
        }
    }

    /**
     * @param array $arrModulInfo
     * @return bool
     */
    private function checkIfModuleExists(array $arrModuleInfo)
    {
        $strModuleName = StringUtil::substring($arrModuleInfo['module'], strlen("module_"), strlen($arrModuleInfo['module']));
        $objPackageManager = new PackagemanagerManager();
        $objPackage = $objPackageManager->getPackage($strModuleName);

        return !empty($objPackage);
    }

    /**
     * @param $strDDValues
     * @param $strModule
     *
     * @return array|null
     */
    public static function convertDDValueStringToArray($strDDValues, $strModule)
    {
        $arrDDValues = null;
        if ($strDDValues !== null && $strDDValues != "") {
            $arrDDValues = array();
            foreach (explode(",", $strDDValues) as $strOneKeyVal) {
                $strOneKeyVal = StringUtil::substring(trim($strOneKeyVal), 1, -1);
                $arrOneKeyValue = explode("=>", $strOneKeyVal);

                $strKey = trim($arrOneKeyValue[0]) == "" ? " " : trim($arrOneKeyValue[0]);
                if (count($arrOneKeyValue) == 2) {
                    $strValue = Carrier::getInstance()->getObjLang()->getLang(trim($arrOneKeyValue[1]), $strModule);
                    if ($strValue == "!" . trim($arrOneKeyValue[1]) . "!") {
                        $strValue = $arrOneKeyValue[1];
                    }
                    $arrDDValues[$strKey] = $strValue;
                }
            }
        }

        return $arrDDValues;
    }

    public function validateValue()
    {
        return in_array($this->getStrValue(), array_keys($this->arrKeyValues));
    }


    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @return string
     */
    public function getValueAsText()
    {
        $value = isset($this->arrKeyValues[$this->getStrValue()]) ? $this->arrKeyValues[$this->getStrValue()] : "";

        return htmlentities($value, null, null, false);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), [
            "values" => $this->arrKeyValues
        ]);
    }

    /**
     * @param $arrKeyValues
     *
     * @return FormentryDropdown
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
     * @param string $strAddons
     *
     * @return $this
     */
    public function setStrAddons($strAddons)
    {
        $this->strAddons = $strAddons;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrAddons()
    {
        return $this->strAddons;
    }

    /**
     * @return string
     */
    public function getStrDataPlaceholder()
    {
        return $this->strDataPlaceholder;
    }

    /**
     * @param string $strDataPlaceholder
     *
     * @return $this
     */
    public function setStrDataPlaceholder($strDataPlaceholder)
    {
        $this->strDataPlaceholder = $strDataPlaceholder;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getBitRenderReset()
    {
        return $this->bitRenderReset;
    }

    /**
     * @param boolean $bitRenderReset
     */
    public function setBitRenderReset($bitRenderReset)
    {
        $this->bitRenderReset = $bitRenderReset;
    }

    /**
     * @return bool
     */
    public function isIconValues(): bool
    {
        return $this->iconValues;
    }

    /**
     * @param bool $iconValues
     */
    public function setIconValues(bool $iconValues): void
    {
        $this->iconValues = $iconValues;
    }

}
