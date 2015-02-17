<?php
/*"******************************************************************************************************
*   (c) 2007-2015 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                               *
********************************************************************************************************/

/**
 * @author  sidler@mulchprod.de
 * @since   4.0
 * @package module_formgenerator
 */
class class_formentry_text extends class_formentry_base implements interface_formentry_printable {

    private $strOpener = "";


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

        $strReturn .= $objToolkit->formInputText($this->getStrEntryName(), $this->getStrLabel(), $this->getStrValue(), "inputText", $this->strOpener, $this->getBitReadonly());

        return $strReturn;
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

    /**
     * @param string $strOpener
     * @return class_formentry_text
     */
    public function setStrOpener($strOpener) {
        $this->strOpener = $strOpener;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrOpener() {
        return $this->strOpener;
    }

}
