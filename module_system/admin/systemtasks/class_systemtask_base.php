<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2015 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                               *
********************************************************************************************************/


/**
 * Base class for all systemtasks. Provides a few methods to be used by the concrete tasks.
 *
 * @package module_system
 * @autor sidler@mulchprod.de
 */
abstract class class_systemtask_base {

    private $strTextbase = "system";

    /**
     * Instance of class_db
     *
     * @var class_db
     */
    private $objDB;

    /**
     * Instance of class_text
     *
     * @var class_lang
     */
    private $objLang;

    /**
     * Instance of class_toolkit
     *
     * @var class_toolkit_admin
     */
    protected $objToolkit;

    /**
     * URL used to trigger a reload, e.g. during long tasks
     *
     * @var string
     */
    private $strReloadParam = "";

    /**
     * Infos regarding the current process
     *
     * @var string
     */
    private $strProgressInformation = "";

    /**
     * @var class_module_system_common
     */
    private $objSystemCommon;

    /**
     * Indicates, wether the form to set up the task is a multipart-form or not (e.g.
     * for fileuploads)
     *
     * @var bool
     */
    private $bitMultipartform = false;

    /**
     * Default constructor
     */
    public function __construct() {

        //load the external objects
        $this->objDB = class_carrier::getInstance()->getObjDB();
        $this->objLang = class_carrier::getInstance()->getObjLang();
        $this->objToolkit = class_carrier::getInstance()->getObjToolkit("admin");
        $this->objSystemCommon = new class_module_system_common();

    }

    /**
     * Delegate requests for strings to the text-subsystem
     *
     * @param string $strLangKey
     * @param array $arrParameters
     *
     * @return string
     */
    protected function getLang($strLangKey, $arrParameters = array()) {
        return $this->objLang->getLang($strLangKey, $this->strTextbase, $arrParameters);
    }

    /**
     * Method invoking the hook-methods to generate a form.
     *
     * @param string $strTargetModule
     * @param string $strTargetAction
     * @param string|class_admin_formgenerator $objAdminForm
     *
     * @return string
     */
    public final function generateAdminForm($strTargetModule = "system", $strTargetAction = "systemTasks", $objAdminForm = null) {
        $strReturn = "";
        $objAdminForm = $objAdminForm == null ? $this->getAdminForm() : $objAdminForm;

        if($objAdminForm instanceof class_admin_formgenerator) {
            $objAdminForm->addField(new class_formentry_hidden("", "execute"))->setStrValue("true");
            $objAdminForm->addField(new class_formentry_button("", "systemtask_run"))->setStrLabel($this->objLang->getLang("systemtask_run", "system"))->setStrValue("submit");

            if($this->bitMultipartform) {
                $objAdminForm->setStrFormEncoding(class_admin_formgenerator::FORM_ENCTYPE_MULTIPART);
            }

            $strLink = class_link::getLinkAdminHref($strTargetModule, $strTargetAction, "task=" . $this->getStrInternalTaskName());
            $strReturn = $objAdminForm->renderForm($strLink, 0);
        }
        else if($objAdminForm != "") {
            if($this->bitMultipartform) {
                $strReturn .= $this->objToolkit->formHeader(class_link::getLinkAdminHref($strTargetModule, $strTargetAction, "task=" . $this->getStrInternalTaskName()), "taskParamForm", class_admin_formgenerator::FORM_ENCTYPE_MULTIPART);
            }
            else {
                $strReturn .= $this->objToolkit->formHeader(class_link::getLinkAdminHref($strTargetModule, $strTargetAction, "task=" . $this->getStrInternalTaskName()), "taskParamForm");
            }
            $strReturn .= $objAdminForm;
            $strReturn .= $this->objToolkit->formInputHidden("execute", "true");
            $strReturn .= $this->objToolkit->formInputSubmit($this->objLang->getLang("systemtask_run", "system"));
            $strReturn .= $this->objToolkit->formClose();

        }

        return $strReturn;
    }

    /**
     * Sets the current textbase, so a module.
     * If your textfiles are coming along with a module different than module system, pass the name here
     * to enable a proper text-loading.
     *
     * @param string $strModulename
     * @return void
     */
    protected function setStrTextBase($strModulename) {
        $this->strTextbase = $strModulename;
    }

    /**
     * Empty implementation, override in subclass!
     * @return class_admin_formgenerator
     */
    public function getAdminForm() {
    }

    /**
     * Empty implementation, override in subclass!
     * @return string[]
     */
    public function getSubmitParams() {
        return "";
    }

    /**
     * Empty implementation, oveerride in subclass!
     * @return string
     */
    public function getStrInternalTaskName() {
    }

    /**
     * @param string $strReloadParam
     */
    public function setStrReloadParam($strReloadParam) {
        $this->strReloadParam = $strReloadParam;
    }

    /**
     * @return string
     */
    public function getStrReloadParam() {
        return $this->strReloadParam;
    }

    /**
     * @return string
     */
    public function getStrReloadUrl() {
        if($this->strReloadParam != "") {
            return getLinkAdminHref("system", "systemTasks", "&task=" . $this->getStrInternalTaskName() . $this->strReloadParam);
        }
        else {
            return "";
        }
    }

    /**
     * @param string $strProgressInformation
     * @return void
     */
    public function setStrProgressInformation($strProgressInformation) {
        $this->strProgressInformation = $strProgressInformation;
    }

    /**
     * @return string
     */
    public function getStrProgressInformation() {
        return $this->strProgressInformation;
    }

    /**
     * Delegate to system-kernel, used to read from params.
     * Provides acces to the GET and POST params
     *
     * @param string $strKey
     *
     * @return mixed
     */
    public function getParam($strKey) {
        return $this->objSystemCommon->getParam($strKey);
    }

    /**
     * Delegate to system-kernel, used to write to params
     *
     * @param string $strKey
     * @param mixed $strValue
     *
     * @return void
     */
    public function setParam($strKey, $strValue) {
        $this->objSystemCommon->setParam($strKey, $strValue);
    }

    /**
     * Indicates, wether the form to set up the task is a multipart-form or not (e.g.
     * for fileuploads)
     *
     * @param bool $bitMultipartform
     */
    public function setBitMultipartform($bitMultipartform) {
        $this->bitMultipartform = $bitMultipartform;
    }

}
