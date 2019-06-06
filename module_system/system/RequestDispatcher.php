<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *    $Id$                                               *
 ********************************************************************************************************/

namespace Kajona\System\System;

use Kajona\System\Admin\AdminSimple;
use Kajona\System\Admin\LoginAdmin;
use Kajona\System\Xml;
use Kajona\V4skin\Admin\SkinAdminController;

/**
 * The request-dispatcher is called by all external request-entries and acts as a controller.
 * It dispatches the requests to the matching modules and areas, taking care of login-status and more.
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 3.4.1
 */
class RequestDispatcher
{

    private $arrTimestampStart;

    /**
     * @var ResponseObject
     */
    private $objResponse = null;

    /**
     * @var \Kajona\System\System\ObjectBuilder
     */
    private $objBuilder;

    /**
     * @var Session
     */
    private $objSession;

    /**
     * Standard constructor
     *
     * @param ResponseObject $objResponse
     */
    public function __construct(ResponseObject $objResponse, \Kajona\System\System\ObjectBuilder $objBuilder)
    {
        $this->arrTimestampStart = gettimeofday();
        $this->objSession = Carrier::getInstance()->getObjSession();
        $this->objResponse = $objResponse;
        $this->objBuilder = $objBuilder;
    }

    /**
     * Global controller entry, triggers all further actions, splits up admin- and portal loading
     *
     * @param string $strModule
     * @param string $strAction
     */
    public function processRequest($strModule, $strAction)
    {
        CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_STARTPROCESSING, array($strModule, $strAction));

        $strReturn = $this->processAdminRequest($strModule, $strAction);
        $strReturn = $this->callScriptlets($strReturn, ScriptletInterface::BIT_CONTEXT_ADMIN);

        $strReturn = $this->cleanupOutput($strReturn);
        $strReturn = $this->getDebugInfo($strReturn);

        $this->objResponse->setStrContent($strReturn);

        CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_ENDPROCESSING, array($strModule, $strAction));

        $this->objSession->sessionClose();
    }

    /**
     * Processes an admin-request
     *
     * @param string $strModule
     * @param string $strAction
     *
     * @throws Exception
     * @return string
     *
     * @todo refactor
     */
    private function processAdminRequest($strModule, $strAction)
    {
        $strReturn = "";
        $bitLogin = false;

        //validate https status
        if (SystemSetting::getConfigValue("_admin_only_https_") == "true") {
            //check which headers to compare
            $arrHeaderNames = Carrier::getInstance()->getObjConfig()->getConfig("https_header");
            if (!is_array($arrHeaderNames)) {
                $arrHeaderNames = array($arrHeaderNames);
            }
            $strHeaderValue = strtolower(Carrier::getInstance()->getObjConfig()->getConfig("https_header_value"));

            $bitRedirectRequired = true;
            foreach ($arrHeaderNames as $strSingleName) {
                if (issetServer($strSingleName) && $strHeaderValue == strtolower(getServer($strSingleName))) {
                    $bitRedirectRequired = false;
                    break;
                }
            }

            if ($bitRedirectRequired) {
                //reload to https
                ResponseObject::getInstance()->setStrRedirectUrl(
                    StringUtil::replace("http:", "https:", ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::XML()) ? _xmlpath_ : _indexpath_) . "?" . getServer("QUERY_STRING")
                );
                ResponseObject::getInstance()->sendHeaders();
                die("Reloading using https...");
            }

        }

        //set the current backend skin. right here to do it only once.
        AdminskinHelper::defineSkinWebpath();

        //validate login-status / process login-request
        if ($strModule != "login") {
            //try to load the module
            $objModuleRequested = SystemModule::getModuleByName($strModule);
            if (empty($strModule) || $objModuleRequested != null) {
                //see if there is data from a previous, failed request
                if (Carrier::getInstance()->getObjSession()->getSession(LoginAdmin::SESSION_LOAD_FROM_PARAMS) === "true") {
                    foreach (Carrier::getInstance()->getObjSession()->getSession(LoginAdmin::SESSION_PARAMS) as $strOneKey => $strOneVal) {
                        Carrier::getInstance()->setParam($strOneKey, $strOneVal);
                    }

                    Carrier::getInstance()->getObjSession()->sessionUnset(LoginAdmin::SESSION_LOAD_FROM_PARAMS);
                    Carrier::getInstance()->getObjSession()->sessionUnset(LoginAdmin::SESSION_PARAMS);
                }

                //fill the history array to track actions
                if (ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::INDEX()) && empty(Carrier::getInstance()->getParam("folderview"))) {
                    $objHistory = new History();
                    //Writing to the history
                    $objHistory->setAdminHistory();
                }

                $strReturn = "";

                //try to rewrite some redirect urls internally
                if (ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::INDEX()) && $_SERVER['REQUEST_METHOD'] != 'POST' && !empty($strModule) && empty(Carrier::getInstance()->getParam("contentFill"))) {
                    $arrParams = Carrier::getAllParams();
                    unset($arrParams["module"]);
                    unset($arrParams["action"]);
                    unset($arrParams["admin"]);

                    return "<html><head></head><body><script type='text/javascript'>document.location='" . Link::getLinkAdminHref($strModule, $strAction, $arrParams, false, true) . "';</script></body></html>";
                }

                if (Carrier::getInstance()->getParam("blockAction") != "1") {
                    if (!empty($strModule)) {
                        $objConcreteModule = $objModuleRequested->getAdminInstanceOfConcreteModule();
                        try {
                            //process e.g. in case of post requests
                            $strReturn = $objConcreteModule->action();
                            if (ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::INDEX())) {
                                if ($strReturn != "") {
                                    $objHelper = new SkinAdminController();
                                    $strReturn = $objHelper->actionGetPathNavigation($objConcreteModule) . $strReturn;
                                    $strReturn = $objHelper->actionGetQuickHelp($objConcreteModule) . $strReturn;
                                    if ($objConcreteModule instanceof AdminSimple) {
                                        $strReturn = $objConcreteModule->getContentActionToolbar() . $strReturn;
                                    }
                                    $strReturn = "<script type=\"text/javascript\">ContentToolbar.resetBar()</script>" . $strReturn; //TODO: das muss hier raus, falsche stelle?
                                }
                            }
                        } catch (ActionNotFoundException $objEx) {
                            $strReturn = $objEx->getMessage();
                        } catch (RedirectException $objEx) {
                            ResponseObject::getInstance()->setStrRedirectUrl($objEx->getHref());
                            $strReturn = "";
                        } catch (AuthenticationException $objEx) {
                            if (!$this->objSession->isLoggedin()) {
                                //login page required
                                $bitLogin = true;
                            }
                        } catch (\Throwable $objEx) {
                            if (!($objEx instanceof Exception)) {
                                $objNewEx = new Exception($objEx->getMessage(), Exception::$level_ERROR, $objEx);
                                $objEx = $objNewEx;
                            }
                            $objEx->processException();

                            // Execution has to be stopped here!
                            if (ResponseObject::getInstance()->getStrStatusCode() == "" || ResponseObject::getInstance()->getStrStatusCode() == HttpStatuscodes::SC_OK) {
                                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_INTERNAL_SERVER_ERROR);
                            }

                            $strReturn = Exception::renderException($objEx);
                        }

                        //if we resulted in a redirect, rewrite it to a js based on and force the redirect on "root" level
                        if (ResponseObject::getInstance()->getStrRedirectUrl() != "") {
                            //TODO: move following to external helper
                            $strUrl = ResponseObject::getInstance()->getStrRedirectUrl();
                            ResponseObject::getInstance()->setStrRedirectUrl("");

                            $strRoutieRedirect = StringUtil::replace(_webpath_ . "/index.php?", "", $strUrl);
                            //and strip everything until the last #sign
                            $strRoutieRedirect = StringUtil::substring($strRoutieRedirect, StringUtil::lastIndexOf($strRoutieRedirect, "#"));

                            $strJs = "";
                            if (ResponseObject::getInstance()->getBitForceMessagePollOnRedirect()) {
                                $strJs = "messaging.pollMessages();";
                            }

                            $strReturn = "<script type='text/javascript'>
                            Router.loadUrl('{$strRoutieRedirect}');
                            {$strJs}
                                </script>";

                        }
                    }

                    if (ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::INDEX())
                        && (empty(Carrier::getInstance()->getParam("contentFill"))
                            || !empty(Carrier::getInstance()->getParam("combinedLoad")))
                    ) {
                        if ($this->objSession->isLoggedin()) {
                            $objHelper = new SkinAdminController();
                            $strReturn = $objHelper->actionGenerateMainTemplate($strReturn);
                        } else {
                            $bitLogin = true;
                        }
                    }

                }

                //React, if admin was opened by the portaleditor
                if (Carrier::getInstance()->getParam("peClose") == "1") {
                    if (getGet("peRefreshPage") != "") {
                        $strReloadUrl = xssSafeString(getGet("peRefreshPage"));
                        $strReturn = "<html><head></head><body><script type='text/javascript'>if(window.opener) { window.opener.location = '" . $strReloadUrl . "'; window.close(); } else { parent.location = '" . $strReloadUrl . "'; }</script></body></html>";
                    } else {
                        $strReturn = "<html><head></head><body><script type='text/javascript'>if(window.opener) { window.opener.location.reload(); window.close(); } else { parent.location.reload(); }</script></body></html>";
                    }
                }

            } else {
                throw new Exception("Requested module " . $strModule . " not existing");
            }

        } else {
            $bitLogin = true;
        }

        if ($bitLogin) {
            if ($strModule != "login") {
                $strAction = "";
            }

            //skip in case of xml requests
            if (ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::XML())) {
                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
                ResponseObject::getInstance()->setStrResponseType(HttpResponsetypes::STR_TYPE_XML);
                Xml::setBitSuppressXmlHeader(true);
                return Exception::renderException(new ActionNotFoundException("you are not authorized/authenticated to call this action", Exception::$level_FATALERROR));
            }

            $objHelper = new SkinAdminController();
            $objLogin = $this->objBuilder->factory(LoginAdmin::class);
            $strReturn = $objLogin->action($strAction);

            if (Carrier::getInstance()->getParam("contentFill") != "1") {
                if (!empty(Carrier::getInstance()->getParam("anonymous"))) {
                    $strReturn = $objHelper->actionGenerateAnonymousTemplate("<div class='loadingContainer'></div>");
                } else {
                    $strReturn = $objHelper->actionGenerateLoginTemplate("<div class='loadingContainer'></div>");
                }
            }
        }

        return $strReturn;
    }

    /**
     * Strips unused contents from the generated output, e.g. placeholders
     *
     * @param string $strContent
     *
     * @return string
     */
    private function cleanupOutput($strContent)
    {
        $objTemplate = Carrier::getInstance()->getObjTemplate();
        $objTemplate->setTemplate($strContent);
        $objTemplate->deletePlaceholder();
        $strContent = $objTemplate->getTemplate();
        $strContent = str_replace("\%\%", "%%", $strContent);

        return $strContent;
    }

    /**
     * Calls the scriptlets in order to process additional tags and in order to enrich the content.
     *
     * @param string $strContent
     * @param int $intContext
     *
     * @return string
     */
    private function callScriptlets($strContent, $intContext)
    {
        $objScriptlet = new ScriptletHelper();
        return $objScriptlet->processString($strContent, $intContext);
    }

    /**
     * Generates debugging-infos, but only in non-xml mode
     *
     * @param string $strReturn
     *
     * @return string
     */
    private function getDebugInfo($strReturn)
    {
        $strDebug = "";
        if (_timedebug_ || _dbnumber_ || _templatenr_ || _memory_) {
            //Maybe we need the time used to generate this page
            if (_timedebug_ === true) {
                $arrTimestampEnde = gettimeofday();
                $intTimeUsed = (($arrTimestampEnde['sec'] * 1000000 + $arrTimestampEnde['usec'])
                     - ($this->arrTimestampStart['sec'] * 1000000 + $this->arrTimestampStart['usec'])) / 1000000;

                $strDebug .= "PHP-Time: " . number_format($intTimeUsed, 6) . " sec ";
            }

            //Hows about the queries?
            if (_dbnumber_ === true) {
                $strDebug .= "Queries db/cachesize/cached/fired: " . Carrier::getInstance()->getObjDB()->getNumber() . "/" .
                Carrier::getInstance()->getObjDB()->getCacheSize() . "/" .
                Carrier::getInstance()->getObjDB()->getNumberCache() . "/" .
                    (Carrier::getInstance()->getObjDB()->getNumber() - Carrier::getInstance()->getObjDB()->getNumberCache()) . " ";
            }

            //memory
            if (_memory_ === true) {
                $strDebug .= "Memory/Max Memory: " . bytesToString(memory_get_usage()) . "/" . bytesToString(memory_get_peak_usage()) . " ";
                $strDebug .= "Classes Loaded: " . Classloader::getInstance()->getIntNumberOfClassesLoaded() . " ";
            }

            ResponseObject::getInstance()->addHeader("Kajona-Debug: " . $strDebug);
        }

        return $strReturn;
    }

}
