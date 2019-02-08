<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\StringUtil;
use Kajona\System\System\Validators\NumericValidator;
use Kajona\System\View\Components\Formentry\Inputtext\Inputtext;


/**
 * A simple form-element for floats, makes use of localized decimal-separators
 *
 * @author sidler@mulchprod.de
 * @since 4.0
 * @package module_formgenerator
 */
class FormentryFloat extends FormentryBase implements FormentryPrintableInterface
{
    private $intNrOfDecimals = 2;

    /**
     * @var string
     */
    private $strPrepend = "";

    /**
     * @var string
     */
    private $strAppend = "";

    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        //set the default validator
        $this->setObjValidator(new NumericValidator());
    }

    public function setStrValue($strValue)
    {
        parent::setStrValue($strValue);

        //check if value comes from ui by checking if param exist. If param exists try to convert the value to a raw value
        if(Carrier::getInstance()->issetParam($this->getStrEntryName())) {
            parent::setStrValue(self::getRawValue($this->getStrValue()));
        }

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
            $strReturn .= $objToolkit->formTextRow($this->getStrHint());
        }

        $strValue = self::getStrUIValue($this->getStrValue(), $this->intNrOfDecimals);

        $inputText = new Inputtext($this->getStrEntryName(), (string) $this->getStrLabel(), $strValue);
        $inputText->setClass("inputText");
        $inputText->setReadOnly($this->getBitReadonly());
        $inputText->setPrepend($this->getStrPrepend());
        $inputText->setAppend($this->getStrAppend());

        $strReturn .= $inputText->renderComponent();

        return $strReturn;
    }

    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @return string
     */
    public function getValueAsText()
    {
        return self::getStrUIValue($this->getStrValue());
    }

    /**
     * Converts the value of the formentry to a float representation (raw value)
     *
     * @param mixed $strInputValue
     *
     * @return float|null
     */
    public static function getRawValue($strInputValue)
    {
        $strFieldValue = $strInputValue;

        $strSyleThousand = Carrier::getInstance()->getObjLang()->getLang("numberStyleThousands", "system");
        $strStyleDecimal = Carrier::getInstance()->getObjLang()->getLang("numberStyleDecimal", "system");

        $strValue = StringUtil::replace($strSyleThousand, "", $strFieldValue);//remove first thousand separator
        $strValue = StringUtil::replace(array(",", $strStyleDecimal), ".", $strValue);//replace decimal with decimal point for db

        //in case given string is not numeric or an empty string just return that value
        if (!is_numeric($strValue) || $strValue === "") {
            return $strFieldValue;
        }

        return (float)$strValue;

    }

    /**
     * Converts the value of the formentry to UI representation
     *
     * @param mixed $strInputValue
     *
     * @param int $intNrOfDecimals
     * @return mixed
     */
    public static function getStrUIValue($strInputValue, $intNrOfDecimals = 2)
    {
        $strValue = $strInputValue;

        if (!is_numeric($strValue)) {
            return $strValue;
        }

        return numberFormat($strValue, $intNrOfDecimals);
    }

    /**
     * @param int $intNrOfDecimals
     */
    public function setIntNrOfDecimals(int $intNrOfDecimals)
    {
        $this->intNrOfDecimals = $intNrOfDecimals;
    }

    /**
     * @return string
     */
    public function getStrPrepend()
    {
        return $this->strPrepend;
    }

    /**
     * @param string $strPrepend
     */
    public function setStrPrepend(string $strPrepend)
    {
        $this->strPrepend = $strPrepend;
    }

    /**
     * @return string
     */
    public function getStrAppend()
    {
        return $this->strAppend;
    }

    /**
     * @param string $strAppend
     */
    public function setStrAppend(string $strAppend)
    {
        $this->strAppend = $strAppend;
    }
}
