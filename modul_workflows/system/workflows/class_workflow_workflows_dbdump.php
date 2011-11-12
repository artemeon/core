<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                               *
********************************************************************************************************/

/**
 * Workflow to create a dbdump in a regular interval, by default configured for 24h
 *
 * @package modul_workflows
 */
class class_workflow_workflows_dbdump implements interface_workflows_handler  {

    private $intIntervalHours = 24;
    
    /**
     * @var class_modul_workflows_workflow
     */
    private $objWorkflow = null;

    /**
     * @see interface_workflows_handler::getConfigValueNames()
     */
    public function getConfigValueNames() {
        return array(
            class_carrier::getInstance()->getObjText()->getText("workflow_dbdump_val1", "workflows", "admin")
        );
    }

    /**
     * @see interface_workflows_handler::setConfigValues()
     */
    public function setConfigValues($strVal1, $strVal2, $strVal3) {
        if($strVal1 != "" && is_numeric($strVal1))
            $this->intIntervalHours = $strVal1;

    }

    /**
     * @see interface_workflows_handler::getDefaultValues()
     */
    public function getDefaultValues() {
        return array(24); // by default there are 24h between each dbdump
    }
    
    public function setObjWorkflow($objWorkflow) {
        $this->objWorkflow = $objWorkflow;
    }

    public function getStrName() {
        return class_carrier::getInstance()->getObjText()->getText("workflow_dbdumps_title", "workflows", "admin");
    }
    

    public function execute() {

        $objDB = class_carrier::getInstance()->getObjDB();
        $objDB->dumpDb();

        //trigger again
        return false;

    }

    public function onDelete() {

    }

    

    public function initialize() {
        //nothing to do here
    }

    public function schedule() {
        
        $this->objWorkflow->setObjTriggerdate(new class_date(time() + $this->intIntervalHours * 3600));
        
    }

    public function getUserInterface() {
       
    }

    public function processUserInput($arrParams) {
        return;

    }

    public function providesUserInterface() {
        return false;
    }


    
}
?>