<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *    $Id$                                        *
 ********************************************************************************************************/

namespace Kajona\System\Admin;

use Kajona\Oauth2\System\ProviderManager;
use Kajona\Oauth2\System\ServiceProvider;
use Kajona\System\System\AuthenticationException;
use Kajona\System\System\Carrier;
use Kajona\System\System\Config;
use Kajona\System\System\Cookie;
use Kajona\System\System\HttpStatuscodes;
use Kajona\System\System\Link;
use Kajona\System\System\Logger;
use Kajona\System\System\RequestEntrypointEnum;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\Security\PasswordExpiredException;
use Kajona\System\System\Security\PasswordRotator;
use Kajona\System\System\Security\PasswordValidatorInterface;
use Kajona\System\System\Security\ValidationException;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemModule;
use Kajona\System\System\UserUser;
use Kajona\System\System\Wadlgenerator;

/**
 * This class shows a little LoginScreen if the user is net yet logged in
 *
 * @package module_system
 * @author sidler@mulchprod.de
 *
 * @module login
 * @moduleId _user_modul_id_
 */
class LoginAdmin extends AdminController implements AdminInterface
{
    const SESSION_REFERER = "LOGIN_SESSION_REFERER";
    const SESSION_PARAMS = "LOGIN_SESSION_PARAMS";
    const SESSION_LOAD_FROM_PARAMS = "LOGIN_SESSION_LOAD_FROM_PARAMS";

    /**
     * @inject system_password_validator
     * @var PasswordValidatorInterface
     */
    protected $objPasswordValidator;

    /**
     * @inject system_password_rotator
     * @var PasswordRotator
     */
    protected $objPasswordRotator;

    public function __construct()
    {
        parent::__construct();

        if (!in_array($this->getAction(), ["pwdReset", "login", "adminLogin", "adminLogout"])) {
            $this->setAction("login");
        }
    }

    /**
     * Creates a small login-field
     *
     * @permissions anonymous
     * @return string
     */
    protected function actionLogin()
    {
        if (ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::XML())) {
            if ($this->objSession->login($this->getParam("username"), $this->getParam("password"))) {
                //user allowed to access admin?
                if (!$this->objSession->isAdmin()) {
                    //no, reset session
                    $this->objSession->logout();
                }

                return "<message><success>" . xmlSafeString($this->getLang("login_xml_succeess", "system")) . "</success></message>";
            } else {
                ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
                return "<message><error>" . xmlSafeString($this->getLang("login_xml_error", "system")) . "</error></message>";
            }
        } else {
            if ($this->objSession->isLoggedin() && $this->objSession->isAdmin()) {
                return $this->loadPostLoginSite();
            }

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // on post try to login the user
                $strReturn = $this->actionAdminLogin();
            } else {
                //Store some of the last requests' data
                $this->objSession->setSession(self::SESSION_REFERER, getServer("QUERY_STRING"));
                $this->objSession->setSession(self::SESSION_PARAMS, getArrayPost());

                //Loading a small login-form
                $strReturn = $this->getLoginForm();
            }

            return $strReturn;
        }
    }

    /**
     * Creates a form in order to change the password - if the authcode is valid
     *
     * @permissions anonymous
     * @return string
     */
    protected function actionPwdReset()
    {
        $strReturn = "";

        if (!validateSystemid($this->getParam("systemid"))) {
            return $this->getLang("login_change_error", "user");
        }

        $objUser = new UserUser($this->getParam("systemid"));

        if ($objUser->getStrAuthcode() != "" && $this->getParam("authcode") == $objUser->getStrAuthcode() && $objUser->getStrUsername() != "") {
            if ($this->getParam("reset") != "") {
                //check the submitted passwords.
                $strPass1 = trim($this->getParam("password1"));
                $strPass2 = trim($this->getParam("password2"));

                if ($strPass1 == $strPass2 && $objUser->getStrUsername() == $this->getParam("username")) {
                    try {
                        $bitReturn = $this->objPasswordValidator->validate($strPass1, $objUser);
                        if ($bitReturn) {
                            if ($objUser->getObjSourceUser()->isPasswordResettable() && method_exists($objUser->getObjSourceUser(), "setStrPass")) {
                                $objUser->getObjSourceUser()->setStrPass($strPass1);
                                $this->objLifeCycleFactory->factory(get_class($objUser->getObjSourceUser()))->update($objUser->getObjSourceUser());
                            }
                            $objUser->setStrAuthcode("");
                            $this->objLifeCycleFactory->factory(get_class($objUser))->update($objUser);

                            Logger::getInstance()->info("changed password of user " . $objUser->getStrUsername());

                            return $this->objToolkit->warningBox($this->getLang("login_change_success", "user"));
                        } else {
                            $strReturn .= $this->objToolkit->warningBox($this->getLang("login_change_error", "user"));
                        }
                    } catch (ValidationException $objE) {
                        $strReturn .= $this->objToolkit->warningBox($objE->getMessage());
                    }
                } else {
                    $strReturn .= $this->objToolkit->warningBox($this->getLang("login_change_error", "user"));
                }
            } else {
                if ($this->getParam("reason") == "expired") {
                    $strReturn .= $this->objToolkit->warningBox($this->getLang("reset_reason_expired", "user"), "alert-info");
                } else {
                    $strReturn .= $this->objToolkit->warningBox($this->getLang("login_password_form_intro", "user"), "alert-info");
                }
            }

            $strReturn .= $this->getPwdResetForm();
        } else {
            $strReturn .= $this->objToolkit->warningBox($this->getLang("login_change_error", "user"));
        }

        return $strReturn;
    }

    private function getLoginForm($bitError = false)
    {
        $strForm = "";
        $strForm .= $this->objToolkit->formHeader(Link::getLinkAdminHref($this->getArrModule("modul"), "login"), generateSystemid(), "", "Forms.defaultOnSubmit(this);return false;");
        $strForm .= $this->objToolkit->formInputText("name", $this->getLang("login_loginUser", "user"), "", "input-large");
        $strForm .= $this->objToolkit->formInputPassword("passwort", $this->getLang("login_loginPass", "user"), "", "input-large");
        $strForm .= $this->objToolkit->formInputSubmit($this->getLang("login_loginButton", "user"));
        $strForm .= $this->objToolkit->formClose();
        $strForm .= "<script type='text/javascript'>
    Util.setBrowserFocus('name');
</script>";

        if (SystemModule::getModuleByName("oauth2") !== null) {
            /** @var ProviderManager $providerManager */
            $providerManager = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_PROVIDER_MANAGER);
            $providers = $providerManager->getAvailableProviders();

            if (count($providers) > 0) {
                $strForm .= $this->objToolkit->divider();
                foreach ($providers as $provider) {
                    if ($provider->getRedirectDetector()->forceRedirect()) {
                        $redirectUrl = $providerManager->buildAuthorizationUrl($provider);
                        ResponseObject::getInstance()->setStrRedirectUrl($redirectUrl);
                    }

                    $strForm .= $this->objToolkit->formHeader(Link::getLinkAdminHref("oauth2", "redirect", ["provider_id" => $provider->getId()]), generateSystemid());
                    $strForm .= $this->objToolkit->formInputSubmit($this->getLang("login_with", "oauth2", [$provider->getName()]));
                    $strForm .= $this->objToolkit->formClose();
                }
            }
        }

        if (SystemModule::getModuleByName("ntlmauth") !== null) {
            $strForm .= $this->objToolkit->divider();
                if (Config::getInstance("module_ntlmauth")->getConfig('forceRedirect')) {
                    ResponseObject::getInstance()->setStrRedirectUrl(Config::getInstance("module_ntlmauth")->getConfig('authUrl'));
                }

                $strForm .= $this->objToolkit->formHeader(Config::getInstance("module_ntlmauth")->getConfig('authUrl'));
                $strForm .= $this->objToolkit->formInputSubmit($this->getLang("login_with", "ntlmauth"));
                $strForm .= $this->objToolkit->formClose();


        }

        $arrTemplate = array();
        $arrTemplate["form"] = $strForm;
        $arrTemplate["loginTitle"] = $this->getLang("login_loginTitle", "user");
        $arrTemplate["loginJsInfo"] = $this->getLang("login_loginJsInfo", "user");
        $arrTemplate["loginCookiesInfo"] = $this->getLang("login_loginCookiesInfo", "user");
        //An error occurred?
        if ($bitError) {
            $arrTemplate["error"] = $this->getLang("login_loginError", "user");
        }

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/elements.tpl", "login_form");
    }

    private function getPwdResetForm()
    {
        //Loading a small form to change the password
        $strForm = "";
        $strForm .= $this->objToolkit->formHeader(Link::getLinkAdminHref($this->getArrModule("modul"), "pwdReset"), generateSystemid(), "", "Forms.defaultOnSubmit(this);return false;");
        $strForm .= $this->objToolkit->formInputText("username", $this->getLang("login_loginUser", "user"), "", "inputTextShort");
        $strForm .= $this->objToolkit->formInputPassword("password1", $this->getLang("login_loginPass", "user"), "", "inputTextShort");
        $strForm .= $this->objToolkit->formInputPassword("password2", $this->getLang("login_loginPass2", "user"), "", "inputTextShort");
        $strForm .= $this->objToolkit->formInputSubmit($this->getLang("login_changeButton", "user"), "", "", "inputSubmitShort");
        $strForm .= $this->objToolkit->formInputHidden("reset", "reset");
        $strForm .= $this->objToolkit->formInputHidden("authcode", $this->getParam("authcode"));
        $strForm .= $this->objToolkit->formInputHidden("systemid", $this->getParam("systemid"));
        $strForm .= $this->objToolkit->formClose();
        $strForm .= "<script type='text/javascript'>
        Util.setBrowserFocus('username');
        </script>";

        $arrTemplate = array();
        $arrTemplate["form"] = $strForm;
        $arrTemplate["loginTitle"] = $this->getLang("login_loginTitle", "user");
        $arrTemplate["loginJsInfo"] = $this->getLang("login_loginJsInfo", "user");
        $arrTemplate["loginCookiesInfo"] = $this->getLang("login_loginCookiesInfo", "user");

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/elements.tpl", "login_form");
    }

    /**
     * Returns a skin based info-box about the current users' login-status.
     *
     * @return string
     */
    public function getLoginStatus()
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $this->objSession->getUsername();
        $arrTemplate["profile"] = Link::getLinkAdminHref("user", "edit", "userid=" . $this->objSession->getUserID(), false);
        $arrTemplate["logout"] = Link::getLinkAdminHref($this->getArrModule("modul"), "adminLogout", "", true, false);
        $arrTemplate["dashboard"] = Link::getLinkAdminHref("dashboard", "", "", false);
        $arrTemplate["statusTitle"] = $this->getLang("login_statusTitle", "user");
        $arrTemplate["profileTitle"] = $this->getLang("login_profileTitle", "user");
        $arrTemplate["logoutTitle"] = $this->getLang("login_logoutTitle", "user");
        $arrTemplate["dashboardTitle"] = $this->getLang("login_dashboard", "user");
        $arrTemplate["sitemapTitle"] = $this->getLang("login_sitemap", "user");
        $arrTemplate["printLink"] = Link::getLinkAdminManual("href=\"#\" onclick=\"window.print();\"", $this->getLang("login_printview", "user"));
        $arrTemplate["printTitle"] = $this->getLang("login_print", "user");

        return $this->objToolkit->getLoginStatus($arrTemplate);
    }

    /**
     * Generates the form to fetch the credentials required to authenticate a user
     *
     * @permissions anonymous
     * @return string
     */
    protected function actionAdminLogin()
    {
        try {
            if ($this->objSession->login($this->getParam("name"), $this->getParam("passwort"))) {
                //user allowed to access admin?
                if (!$this->objSession->isAdmin()) {
                    //no, reset session
                    $this->objSession->logout();}

                //save the current skin as a cookie
                $objCookie = new Cookie();
                $objCookie->setCookie("adminlanguage", $this->objSession->getAdminLanguage(false, true));

                return $this->loadPostLoginSite();
            } else {
                return $this->getLoginForm(true);
            }
        } catch (AuthenticationException $objEx) {
            return $this->getLoginForm(true);
        } catch (PasswordExpiredException $objEx) {
            // if expired redirect to reset password form
            $strToken = generateSystemid();

            $objUser = $this->objFactory->getObject($objEx->getStrUserId());
            $objUser->setStrAuthcode($strToken);
            $this->objLifeCycleFactory->factory(get_class($objUser))->update($objUser);

            return Link::clientRedirectHref("login", "pwdReset", ["systemid" => $objUser->getSystemid(), "authcode" => $strToken, "reason" => "expired"]);
        }
    }

    /**
     * Ends the session of the current user
     *
     * @permissions anonymous
     * @return string
     */
    protected function actionLogout()
    {
        $this->objSession->logout();
        return "<message><success>" . xmlSafeString($this->getLang("logout_xml", "system")) . "</success></message>";
    }

    /**
     * Ends the session of the current user and
     * redirects back to the login-screen
     *
     * @permissions anonymous
     */
    protected function actionAdminlogout()
    {
        $this->objSession->logout();
        ResponseObject::getInstance()->setStrRedirectUrl(Link::getLinkAdminHref("login", "", "", true, false));
    }

    private function loadPostLoginSite()
    {
        // any url to redirect? Only in case its available and we dont come from the login module
        $strRefer = $this->objSession->getSession(self::SESSION_REFERER);
        if ($strRefer != "" && strpos($strRefer, "module=login") === false) {
            $strUrl = StringUtil::replace("&contentFill=1", "", $strRefer);
            $this->objSession->sessionUnset(self::SESSION_REFERER);
            $this->objSession->setSession(self::SESSION_LOAD_FROM_PARAMS, "true");

            return Link::clientRedirectManual(_indexpath_ . "?" . $strUrl);
        } else {
            //route to the default module
            $strModule = "dashboard";
            if (Session::getInstance()->isLoggedin()) {
                $objUser = Session::getInstance()->getUser();
                if ($objUser->getStrAdminModule() != "") {
                    $strModule = $objUser->getStrAdminModule();
                }
            }

            // at the moment it is required to use the "old" url style since otherwise it could happen that the
            // location.href call does not trigger a redirect (in case only the url hash has changed) and thus we would
            // not load a different template and see the main content inside the login template
            return Link::clientRedirectManual(_indexpath_ . "?admin=1&module=" . $strModule);
        }
    }

    /**
     * This method is just a placeholder to avoid error-flooding of the admins.
     * If the session expires, the browser tries one last time to
     * fetch the number of messages for the user. Since the user is "logged out" by the server,
     * an "not authorized" exception is called - what is correct, but not really required right here.
     *
     * @permissions anonymous
     * @return string
     */
    protected function actionGetRecentMessages()
    {
        ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_UNAUTHORIZED);
        return "<error>" . $this->getLang("commons_error_permissions") . "</error>";
    }

    /**
     * Generates the wadl file for the current module
     *
     * @permissions anonymous
     * @return string
     */
    protected function actionWADL()
    {
        $objWadl = new Wadlgenerator("admin", "login");
        $objWadl->addIncludeGrammars("http://apidocs.kajona.de/xsd/message.xsd");

        $objWadl->addMethod(
            true,
            "login",
            array(
                array("username", "xsd:string", true),
                array("password", "xsd:string", true),
            ),
            array(),
            array(
                array("application/xml", "message"),
            )
        );

        $objWadl->addMethod(true, "logout", array());
        return $objWadl->getDocument();
    }
}
