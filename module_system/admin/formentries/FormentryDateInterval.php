<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Lang;
use Kajona\System\System\StringUtil;
use Kajona\System\System\Validators\DateIntervalValidator;
use Kajona\System\View\Components\Formentry\InputDateinterval\InputDateInterval;

/**
 * Provides a simple input to enter a date interval i.e. 1 month
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.0
 * @package module_formgenerator
 */
class FormentryDateInterval extends FormentryBase implements FormentryPrintableInterface
{
    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        //set the default validator
        $this->setObjValidator(new DateIntervalValidator());
    }

    /**
     * Renders the field itself.
     * In most cases, based on the current toolkit.
     *
     * @return string
     * @throws \Exception
     */
    public function renderField()
    {
        $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $strReturn = "";
        if ($this->getStrHint() != null) {
            $strReturn .= $objToolkit->formTextRow($this->getStrHint());
        }

        $strValue = $this->getStrValue();
        $objInterval = null;
        if ($strValue instanceof \DateInterval) {
            $objInterval = $strValue;
        } elseif ($strValue != "") {
            $objInterval = new \DateInterval($strValue);
        }

        $dateInterval  = new InputDateInterval($this->getStrEntryName(), $this->getStrLabel(), $objInterval);
        $strReturn .= $dateInterval->renderComponent();

        return $strReturn;
    }

    /**
     * Queries the params-array or the source-object for the mapped value.
     * If found in the params-array, the value will be used, otherwise
     * the source-objects' getter is invoked.
     */
    protected function updateValue()
    {
        $arrParams = $this->getFormParams();
        $strName = $this->getStrEntryName();

        if (isset($arrParams[$strName . "_unit"]) && isset($arrParams[$strName])) {//form sent
            $strUnit = null;
            if (isset($arrParams[$strName . "_unit"])) {
                $strUnit = $arrParams[$strName . "_unit"];
            }
            $strValue = null;
            if (isset($arrParams[$strName])) {
                $strValue = StringUtil::toInt($arrParams[$strName]);
            }

            if (in_array($strUnit, ["D", "W", "M", "Y"]) && $strValue !== null && $strValue > 0) {
                $strDuration = "P" . $strValue . $strUnit;
                $this->setStrValue($strDuration);
            } else {
                $this->setStrValue(null);
            }
        } else {//no form sent
            $this->setStrValue($this->getValueFromObject());
        }
    }

    /**
     * @inheritdoc
     */
    public function getValueAsText()
    {
        $strValue = $this->getStrValue();
        $objInterval = null;
        if (!empty($strValue) && is_string($strValue)) {
            $objInterval = new \DateInterval($strValue);
        } elseif ($strValue instanceof \DateInterval) {
            $objInterval = $strValue;
        }

        if ($objInterval !== null) {
            return self::toString($objInterval);
        }

        return "-";
    }

    /**
     * Transforms a date interval to string
     *
     * @param \DateInterval $objInterval
     * @return string
     */
    public static function toString(\DateInterval $objInterval)
    {
        $objLang = Lang::getInstance();
        $arrFormat = [];
        if ($objInterval->y > 0) {
            $arrFormat[] = "%y " . $objLang->getLang(($objInterval->y > 1 ? "commons_interval_years" : "commons_interval_year"), "system");
        }
        if ($objInterval->m > 0) {
            $arrFormat[] = "%m " . $objLang->getLang(($objInterval->m > 1 ? "commons_interval_months" : "commons_interval_month"), "system");
        }
        if ($objInterval->d > 0) {
            if ($objInterval->d % 7 == 0) {
                $intWeeks = $objInterval->d / 7;
                $arrFormat[] = "{$intWeeks} " . $objLang->getLang(($intWeeks > 1 ? "commons_interval_weeks" : "commons_interval_week"), "system");
            } else {
                $arrFormat[] = "%d " . $objLang->getLang(($objInterval->d > 1 ? "commons_interval_days" : "commons_interval_day"), "system");
            }
        }
        if (count($arrFormat) > 0) {
            return $objInterval->format(implode(", ", $arrFormat));
        }

        return "-";
    }
}
