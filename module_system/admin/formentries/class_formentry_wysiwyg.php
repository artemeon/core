<?php
/*"******************************************************************************************************
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                               *
********************************************************************************************************/

/**
 * @author sidler@mulchprod.de
 * @since 4.3
 * @package module_formgenerator
 */
class class_formentry_wysiwyg extends class_formentry_base implements interface_formentry_printable {


    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null) {
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        //set the default validator
        $this->setObjValidator(new class_text_validator());
    }

    /**
     * Renders the field itself.
     * In most cases, based on the current toolkit.
     *
     * @return string
     */
    public function renderField() {
        $objToolkit = class_carrier::getInstance()->getObjToolkit("admin");
        $strReturn = "";
        if($this->getStrHint() != null)
            $strReturn .= $objToolkit->formTextRow($this->getStrHint());

        $strReturn .= $objToolkit->formWysiwygEditor($this->getStrEntryName(), $this->getStrLabel(), $this->getStrValue());

        return $strReturn;
    }

    public function setValueToObject() {
        $strOldValue = $this->getStrValue();
        $this->setStrValue(processWysiwygHtmlContent($this->getStrValue()));
        $bitReturn = parent::setValueToObject();
        $this->setStrValue($strOldValue);
        return $bitReturn;
    }





    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @return string
     */
    public function getValueAsText() {
        return $this->getStrValue();
    }

}
