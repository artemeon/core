<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\Reflection;
use Kajona\System\System\Validators\DummyValidator;

/**
 * An list of tags which can be added or removed.
 *
 * @author christoph.kappestein@gmail.com
 * @since 4.7
 * @package module_formgenerator
 */
class FormentryTageditor extends FormentryMultiselect
{
    /**
     * @var string
     */
    protected $strOnChangeCallback;

    /**
     * @var string
     */
    protected $strDelimiter;

    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        $this->setObjValidator(new DummyValidator());
    }

    /**
     * @param string $strOnChangeCallback
     *
     * @return $this
     */
    public function setOnChangeCallback($strOnChangeCallback)
    {
        $this->strOnChangeCallback = $strOnChangeCallback;

        return $this;
    }

    /**
     * @param string $strDelimiter
     *
     * @return $this
     */
    public function setDelimter($strDelimiter)
    {
        $this->strDelimiter = $strDelimiter;

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

        $data = $this->getStrValue();
        if (!empty($data)) {
            if (strpos($data, '[') === 0) {
                $values = json_decode($data);
            } else {
                $values = explode(',', $data);
            }
        } else {
            $values = [];
        }

        $strReturn .= $objToolkit->formInputTagEditor($this->getStrEntryName(), $this->getStrLabel(), $values, $this->strOnChangeCallback, $this->strDelimiter);
        return $strReturn;
    }

    public function setValueToObject()
    {
        $objSourceObject = $this->getObjSourceObject();
        if ($objSourceObject == null) {
            return "";
        }

        $objReflection = new Reflection($objSourceObject);
        $strSetter = $objReflection->getSetter($this->getStrSourceProperty());
        if ($strSetter === null) {
            throw new Exception("unable to find setter for value-property ".$this->getStrSourceProperty()."@".get_class($objSourceObject), Exception::$level_ERROR);
        }

        return $objSourceObject->{$strSetter}(json_encode(explode(",", $this->getStrValue())));
    }

    public function validateValue()
    {
        if ($this->getObjValidator() != null && !$this->getObjValidator() instanceof DummyValidator) {
            return $this->getObjValidator()->validate(explode(",", $this->getStrValue()));
        }
        $arrValues = explode(",", $this->getStrValue());
        foreach ($arrValues as $strValue) {
            $strValue = trim($strValue);
            if ($strValue === "") {
                return false;
            }
        }

        return true;
    }

    public function getValueAsText()
    {
        return implode(", ", $this->arrKeyValues);
    }

    public function setStrValue($strValue)
    {
        if (is_array($strValue)) {
            $strValue = array_map(function($value){
                return self::encodeValue($value);
            }, $strValue);
            $strValue = implode(",", $strValue);
        }

        return parent::setStrValue($strValue);
    }

    /**
     * Encodes a single tag editor value
     *
     * @param string $value
     * @return string
     */
    public static function encodeValue($value)
    {
        return str_replace(',', '&#44;', $value);
    }

    /**
     * Decodes a single tag editor value
     *
     * @param string $value
     * @return string
     */
    public static function decodeValue($value)
    {
        return str_replace('&#44;', ',', $value);
    }
}
