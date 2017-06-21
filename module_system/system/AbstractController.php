<?php
/*"******************************************************************************************************
*   (c) 2013-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                                            *
********************************************************************************************************/

namespace Kajona\System\System;

/**
 * A common base class for AdminController and PortalController.
 * Use one of both to create admin-/portal-views.
 * Do NOT extend this class directly.
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 4.4
 */
abstract class AbstractController
{

    const STR_MODULE_ANNOTATION = "@module";
    const STR_MODULEID_ANNOTATION = "@moduleId";
    const STR_PERMISSION_ANNOTATION = "@permissions";

    /**
     * May be used at an action method to define the return type.
     * Please note, that the return type stated by the action is set up before the action itself is being executed,
     * so you may change the type afterwards again.
     * Possible values are available at @link{HttpResponsetypes}, e.g. xml, json, csv, jpeg
     *
     * @see HttpResponsetypes
     */
    const STR_RESPONSETYPE_ANNOTATION = "@responseType";

    /**
     * Object containing config-data
     *
     * @inject system_config
     * @var Config
     */
    protected $objConfig;

    /**
     * Object containing the session-management
     *
     * @inject system_session
     * @var Session
     */
    protected $objSession;

    /**
     * Object to handle templates
     *
     * @inject system_template
     * @var Template
     */
    protected $objTemplate;

    /**
     * Object managing the lang-files
     *
     * @inject system_lang
     * @var Lang
     */
    protected $objLang;

    /**
     * @inject system_object_factory
     * @var Objectfactory
     */
    protected $objFactory;

    /**
     * @inject system_rights
     * @var Rights
     */
    protected $objRights;

    /**
     * Instance of the current modules' definition
     *
     * @var SystemModule
     */
    private $objModule;

    /**
     * The current module to load lang-files from
     * String containing the current module to be used to load texts
     *
     * @var string
     */
    private $strLangBase = "";

    /**
     * Current action-name, used for the controller
     * current action to perform (GET/POST)
     *
     * @var string
     */
    private $strAction = "";

    /**
     * The current systemid as passed by the constructor / params
     *
     * @var string
     */
    private $strSystemid = "";

    /**
     * Array containing information about the current module
     *
     * @var array
     * @deprecated direct access is no longer allowed
     */
    protected $arrModule = array();

    /**
     * String containing the output generated by an internal action
     *
     * @var string
     */
    protected $strOutput = "";

    /**
     * @param string $strSystemid
     */
    public function __construct($strSystemid = "")
    {
        // compatibility workaround. If the dependencies were not injected yet the class was created without the object
        // builder in this case we manually inject them
        if ($this->objConfig === null) {
            $objBuilder = new ObjectBuilder(Carrier::getInstance()->getContainer());
            $objBuilder->resolveDependencies($this);
        }

        //Setting SystemID
        if ($strSystemid == "") {
            $this->setSystemid(Carrier::getInstance()->getParam("systemid"));
        } else {
            $this->setSystemid($strSystemid);
        }


        //And keep the action
        $this->setAction($this->getParam("action"));
        //in most cases, the list is the default action if no other action was passed
        if ($this->getAction() == "") {
            $this->setAction("list");
        }

        //try to load the current module-name and the moduleId by reflection
        $objReflection = new Reflection($this);
        if (!isset($this->arrModule["modul"])) {
            $arrAnnotationValues = $objReflection->getAnnotationValuesFromClass(self::STR_MODULE_ANNOTATION);
            if (count($arrAnnotationValues) > 0) {
                $this->setArrModuleEntry("modul", trim($arrAnnotationValues[0]));
                $this->setArrModuleEntry("module", trim($arrAnnotationValues[0]));
            } else {
                throw new Exception("controller ".get_called_class()." is missing a ".self::STR_MODULE_ANNOTATION." annotation", Exception::$level_FATALERROR);
            }
        }

        if (!isset($this->arrModule["moduleId"])) {
            $arrAnnotationValues = $objReflection->getAnnotationValuesFromClass(self::STR_MODULEID_ANNOTATION);
            if (count($arrAnnotationValues) > 0) {
                $this->setArrModuleEntry("moduleId", constant(trim($arrAnnotationValues[0])));
            } else {
                throw new Exception("controller ".get_called_class()." is missing a ".self::STR_MODULEID_ANNOTATION." annotation", Exception::$level_FATALERROR);
            }
        }

        $this->strLangBase = $this->getArrModule("modul");
    }


    /**
     * This method triggers the internal processing.
     * It may be overridden if required, e.g. to implement your own action-handling.
     * By default, the method to be called is set up out of the action-param passed.
     * Example: The action requested is named "newPage". Therefore, the framework tries to
     * call actionNewPage(). If now method matching the schema is found, nothing is done.
     * <b> Please note that this is different from the admin-handling! </b> In the case of admin-classes,
     * an exception is thrown. But since there could be many modules on a single page, not each module
     * may be triggered.
     * Since Kajona 4.0, the check on declarative permissions via annotations is supported.
     * Therefore the list of permissions, named after the "permissions" annotation are validated against
     * the module currently loaded.
     *
     *
     * @param string $strAction
     *
     * @see Rights::validatePermissionString
     * @throws Exception
     * @return string
     * @since 3.4
     */
    public function action($strAction = "")
    {

        if ($strAction != "") {
            $this->setAction($strAction);
        }

        $strAction = $this->getAction();

        //search for the matching method - build method name
        $strMethodName = "action".StringUtil::toUpperCase($strAction[0]).StringUtil::substring($strAction, 1);
        $objReflection = new Reflection(get_class($this));

        if (!method_exists($this, $strMethodName)) {
            //and quit. nothing to do here, method not existing
            $this->strOutput = Carrier::getInstance()->getObjToolkit("admin")->warningBox("called method ".$strMethodName." not existing for class ".get_called_class());
            $objException = new ActionNotFoundException("called method ".$strMethodName." not existing for class ".get_called_class(), Exception::$level_ERROR);
            $this->strOutput = Exception::renderException($objException);
            throw $objException;
        }



        $strPermissions = $objReflection->getMethodAnnotationValue($strMethodName, self::STR_PERMISSION_ANNOTATION);
        if ($strPermissions !== false) {
            //fetch the object to validate, either the module or a directly referenced object
            if (validateSystemid($this->getSystemid()) && $this->objFactory->getObject($this->getSystemid()) != null) {
                $objObjectToCheck = $this->objFactory->getObject($this->getSystemid());
            } else {
                $objObjectToCheck = $this->getObjModule();
            }

            if (!$this->objRights->validatePermissionString($strPermissions, $objObjectToCheck)) {
                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
                $this->strOutput = Carrier::getInstance()->getObjToolkit("admin")->warningBox($this->getLang("commons_error_permissions"));
                $objException = new AuthenticationException("you are not authorized/authenticated to call this action", Exception::$level_ERROR);

                if (ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::XML())) {
                    throw $objException;
                } else {
                    //todo: throw exception, too?
                    $objException->setIntDebuglevel(0);
                    $objException->processException();
                    return $this->strOutput;
                }
            }
        }

        $strReturnType = $objReflection->getMethodAnnotationValue($strMethodName, self::STR_RESPONSETYPE_ANNOTATION);
        if ($strReturnType !== false) {
            ResponseObject::getInstance()->setStrResponseType(HttpResponsetypes::getTypeForString(StringUtil::toLowerCase($strReturnType)));
        }

        $this->strOutput = $this->$strMethodName();


        return $this->strOutput;
    }


    // --- Common Methods -----------------------------------------------------------------------------------


    /**
     * Writes a value to the params-array
     *
     * @param string $strKey Key
     * @param mixed $mixedValue Value
     *
     * @return void
     */
    public function setParam($strKey, $mixedValue)
    {
        Carrier::getInstance()->setParam($strKey, $mixedValue);
    }

    /**
     * Returns a value from the params-Array
     *
     * @param string $strKey
     *
     * @return string|string[] else ""
     */
    public function getParam($strKey)
    {
        return Carrier::getInstance()->getParam($strKey);
    }

    /**
     * Returns the complete Params-Array
     *
     * @return mixed
     * @final
     */
    final public function getAllParams()
    {
        return Carrier::getAllParams();
    }

    /**
     * returns the action used for the current request
     *
     * @return string
     * @final
     */
    final public function getAction()
    {
        return (string)$this->strAction;
    }

    /**
     * Overwrites the current action
     *
     * @param string $strAction
     *
     * @return void
     */
    final public function setAction($strAction)
    {
        $this->strAction = htmlspecialchars(trim($strAction), ENT_QUOTES, "UTF-8", false);
    }



    // --- SystemID & System-Table Methods ------------------------------------------------------------------

    /**
     * Sets the current SystemID
     *
     * @param string $strID
     *
     * @return bool
     * @final
     */
    final public function setSystemid($strID)
    {
        if (validateSystemid($strID)) {
            $this->strSystemid = $strID;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the current SystemID
     *
     * @return string
     * @final
     */
    final public function getSystemid()
    {
        return $this->strSystemid;
    }

    /**
     * Resets the internal system id
     *
     * @final
     */
    final public function unsetSystemid()
    {
        $this->strSystemid = "";
    }


    /**
     * Returns the current Text-Object Instance
     *
     * @return Lang
     */
    protected function getObjLang()
    {
        return $this->objLang;
    }


    /**
     * Returns the current instance of SystemModule, based on the current subclass.
     * Lazy-loading, so loaded on first access.
     *
     * @return SystemModule|null
     */
    protected function getObjModule()
    {
        if ($this->objModule == null) {
            $this->objModule = SystemModule::getModuleByName($this->getArrModule("modul"));
        }

        return $this->objModule;
    }


    /**
     * Generates a sorted array of systemids, reaching from the passed systemid up
     * until the assigned module-id
     *
     * @param string $strSystemid
     * @param string $strStopSystemid
     *
     * @return mixed
     * @deprecated should be handled by the model-classes instead
     */
    public function getPathArray($strSystemid = "", $strStopSystemid = "")
    {
        if ($strSystemid == "") {
            $strSystemid = $this->getSystemid();
        }
        if ($strStopSystemid == "") {
            $strStopSystemid = $this->getObjModule()->getSystemid();
        }

        $objSystemCommon = new SystemCommon();
        return $objSystemCommon->getPathArray($strSystemid, $strStopSystemid);
    }

    /**
     * Returns a value from the $arrModule array.
     * If the requested key not exists, returns ""
     *
     * @param string $strKey
     *
     * @return string
     */
    public function getArrModule($strKey)
    {
        if (isset($this->arrModule[$strKey])) {
            return $this->arrModule[$strKey];
        } else {
            return "";
        }
    }

    /**
     * Writes a key-value-pair to the arrModule
     *
     * @param string $strKey
     * @param mixed $strValue
     *
     * @return void
     */
    public function setArrModuleEntry($strKey, $strValue)
    {
        $this->arrModule[$strKey] = $strValue;
    }


    // --- TextMethods --------------------------------------------------------------------------------------

    /**
     * Used to load a property.
     * If you want to provide a list of parameters but no module (automatic loading), pass
     * the parameters array as the second argument (an array). In this case the module is resolved
     * internally.
     *
     * @param string $strName
     * @param string|array $strModule Either the module name (if required) or an array of parameters
     * @param array $arrParameters
     *
     * @return string
     */
    public function getLang($strName, $strModule = "", $arrParameters = array())
    {
        if (is_array($strModule)) {
            $arrParameters = $strModule;
        }

        if ($strModule == "" || is_array($strModule)) {
            $strModule = $this->strLangBase;
        }

        //Now we have to ask the Text-Object to return the text
        return $this->getObjLang()->getLang($strName, $strModule, $arrParameters);
    }

    /**
     * Sets the textbase, so the module used to load texts
     *
     * @param string $strLangbase
     *
     * @return void
     */
    final protected function setStrLangBase($strLangbase)
    {
        $this->strLangBase = $strLangbase;
    }



    // --- PageCache Features -------------------------------------------------------------------------------

    /**
     * Deletes the complete Pages-Cache
     */
    public function flushCompletePagesCache()
    {
        /** @var CacheManager $objCache */
        $objCache = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_CACHE_MANAGER);
        $objCache->flushCache();
    }
}
