<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2012 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id: class_element_formular.php 3530 2011-01-06 12:30:26Z sidler $                                   *
********************************************************************************************************/

/**
 * Portal Element to load the formular specified in the admin
 *
 * @package element_formular
 * @author sidler@mulchprod.de
 */
class class_element_formular_portal extends class_element_portal implements interface_portal_element {

    /**
     * Constructor
     *
     * @param class_module_pages_pageelement|mixed $objElementData
     */
    public function __construct($objElementData) {
        parent::__construct($objElementData);
        $this->setArrModuleEntry("table", _dbprefix_ . "element_formular");
    }

    /**
     * Loads the navigation-class and passes control
     *
     * @throws class_exception
     * @return string
     */
    public function loadData() {


        $strPath = class_resourceloader::getInstance()->getPathForFile("/portal/forms/" . $this->arrElementData["formular_class"]);

        if($strPath === false) {
            throw new class_exception("failed to load form-class " . $this->arrElementData["formular_class"], class_exception::$level_ERROR);
        }

        require_once(_realpath_ . $strPath);
        $strClassname = uniStrReplace(".php", "", $this->arrElementData["formular_class"]);
        $objForm = new $strClassname($this->arrElementData);
        $strReturn = $objForm->action();

        return $strReturn;
    }

}
