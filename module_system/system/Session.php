<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                            *
********************************************************************************************************/

namespace Kajona\System\System;

use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Security\PasswordExpiredException;

/**
 * Manages all those session stuff as logins or logouts and access to session vars
 *
 * @package module_system
 * @author sidler@mulchprod.de
 */
final class Session
{

    private $strKey = null;

    private $arrRequestArray;

    public static $intScopeSession = 1;
    /**
     * @var int
     * @deprecated use static fields instead
     */
    public static $intScopeRequest = 2;

    private static $objSession = null;
    private $bitLazyLoaded = false;
    private $bitPhpSessionStarted = false;

    private $bitBlockDbUpdate = false;

    /**
     * Instance of internal kajona-session
     *
     * @var SystemSession
     */
    private $objInternalSession = null;

    /**
     * @var UserUser
     */
    private $objUser = null;

    private $bitClosed = false;

    const STR_SESSION_ADMIN_LANG_KEY = "STR_SESSION_ADMIN_LANG_KEY";

    const STR_SESSION_USERID = "STR_SESSION_USERID";
    const STR_SESSION_GROUPIDS = "STR_SESSION_GROUPIDS";
    const STR_SESSION_GROUPIDS_SHORT = "STR_SESSION_GROUPIDS_SHORT";
    const STR_SESSION_ISADMIN = "STR_SESSION_ISADMIN";


    /**
     * Singleton, use getInstance() instead
     */
    private function __construct()
    {
        //Generating a session-key using a few characteristic values

        $this->arrRequestArray = array();
    }

    /**
     * Builds the internal session key - on first access
     * @return string
     */
    private function getSessionKey()
    {
        if ($this->strKey === null) {
            $strAddon = SystemSetting::getConfigValue("_system_session_ipfixation_") === "false" ? "" : getServer("REMOTE_ADDR");
            $this->strKey = md5(_realpath_.$strAddon);
        }

        return $this->strKey;
    }

    /**
     * Returns one instance of the Session-Object, using a singleton pattern
     *
     * @return Session The Session-Object
     */
    public static function getInstance()
    {
        if (self::$objSession == null) {
            self::$objSession = new Session();
        }

        return self::$objSession;
    }

    /**
     * Starts a session
     */
    private function sessionStart()
    {
        if ($this->bitPhpSessionStarted || $this->bitClosed) {
            return;
        }

        //New session needed or using the already started one?
        if (!session_id()) {
            $strPath = preg_replace('#http(s?)://'.getServer("HTTP_HOST").'#i', '', _webpath_);
            if ($strPath == "" || $strPath[0] != "/") {
                $strPath = "/".$strPath;
            }

            @session_set_cookie_params(0, $strPath, null, SystemSetting::getConfigValue("_cookies_only_https_") == "true", true);
            @session_start();
        }

        $this->bitPhpSessionStarted = true;
    }

    /**
     * Finalizes the current threads session-access.
     * This means that afterwards, all values saved to the session will
     * be lost of throw an error.
     * Make sure you know explicitly what you do before calling
     * this method.
     *
     * @return void
     */
    public function sessionClose()
    {
        if (defined("_autotesting_") && _autotesting_ === true) {
            return;
        }

        $this->bitClosed = true;
        session_write_close();
        if ($this->objInternalSession != null && !$this->bitBlockDbUpdate) {
            ServiceLifeCycleFactory::getLifeCycle(get_class($this->objInternalSession))->update($this->objInternalSession);
        }
    }


    /**
     * Writes a value to the session
     *
     * @param string $strKey
     * @param string $strValue
     * @param int $intSessionScope one of Session::$intScopeRequest or Session::$intScopeSession
     *
     * @throws Exception
     * @return bool
     */
    public function setSession($strKey, $strValue, $intSessionScope = 1)
    {

        if ($intSessionScope == Session::$intScopeRequest) {
            $this->arrRequestArray[$strKey] = $strValue;
            return true;
        } else {
            if ($this->bitClosed) {
                throw new Exception("attempt to write to session after calling sessionClose()", Exception::$level_FATALERROR);
            }

            $this->sessionStart();
            //yes, it is wanted to have only one =. The condition checks the assignment.
            if ($_SESSION[$this->getSessionKey()][$strKey] = $strValue) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Setter for captcha-codes. use ONLY this method to set the code.
     *
     * @param string $strCode
     *
     * @return void
     * @throws Exception
     */
    public function setCaptchaCode($strCode)
    {
        $this->setSession("kajonaCaptchaCode", $strCode);
    }

    /**
     * Returns the captcha code generated the last time.
     * the code is being reset, so later requests will return a new systemid
     * forcing the comparison to fail.
     *
     * @return string
     * @throws Exception
     */
    public function getCaptchaCode()
    {
        $strCode = $this->getSession("kajonaCaptchaCode");
        //reset code
        $this->setSession("kajonaCaptchaCode", "");
        if ($strCode == "") {
            $strCode = generateSystemid();
        }

        return $strCode;
    }

    /**
     * Returns a value from the session
     *
     * @param string $strKey
     * @param int $intScope one of Session::$intScopeRequest or Session::$intScopeSession
     *
     * @return string
     */
    public function getSession($strKey, $intScope = 1)
    {
        if ($intScope == Session::$intScopeRequest) {
            if (!isset($this->arrRequestArray[$strKey])) {
                return false;
            } else {
                return $this->arrRequestArray[$strKey];
            }
        } else {
            $this->sessionStart();
            if (!isset($_SESSION[$this->getSessionKey()][$strKey])) {
                return false;
            } else {
                return $_SESSION[$this->getSessionKey()][$strKey];
            }
        }
    }

    /**
     * Checks if a key exists in the current session
     *
     * @param string $strKey
     *
     * @return bool
     */
    public function sessionIsset($strKey)
    {
        $this->sessionStart();
        if (isset($_SESSION[$this->getSessionKey()][$strKey])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes a value from the session
     *
     * @param string $strKey
     *
     * @return void
     */
    public function sessionUnset($strKey)
    {
        $this->sessionStart();
        if ($this->sessionIsset($strKey)) {
            unset($_SESSION[$this->getSessionKey()][$strKey]);
        }
    }

    /**
     * Checks if the current user is logged in
     *
     * @return bool
     * @throws Exception
     */
    public function isLoggedin()
    {
        if ($this->getObjInternalSession() != null) {
            return $this->getObjInternalSession()->isLoggedIn();
        } else {
            return false;
        }
    }

    /**
     * Checks whether a user is an admin or not
     *
     * @return bool
     * @throws Exception
     * @deprecated
     * @todo: ausbauen
     */
    public function isAdmin()
    {
        return true;

        if ($this->isLoggedin()) {
            if ($this->getSession(self::STR_SESSION_ISADMIN) == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    /**
     * Checks whether the current user is member of the global super admin group or not
     *
     * @return bool
     * @throws Exception
     */
    public function isSuperAdmin()
    {
        if ($this->isLoggedin()) {
            if ($this->getUser() != null && $this->getUser()->getIntAdmin() == 1 && in_array(SystemSetting::getConfigValue("_admins_group_id_"), $this->getGroupIdsAsArray())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the language the user set for the administration
     * NOTE: THIS IS FOR THE TEXTS, NOT THE CONTENTS
     *
     * @param bool $bitUseCookie
     * @param bool $bitSkipSessionEntry
     *
     * @return string
     * @throws Exception
     */
    public function getAdminLanguage($bitUseCookie = true, $bitSkipSessionEntry = false)
    {

        if (!$bitSkipSessionEntry && $this->getSession(self::STR_SESSION_ADMIN_LANG_KEY) != "") {
            return $this->getSession(self::STR_SESSION_ADMIN_LANG_KEY);
        }

        //Maybe we can load the language from the cookie
        $objCookie = new Cookie();
        $strLanguage = $objCookie->getCookie("adminlanguage");
        if ($strLanguage != "" && $bitUseCookie) {
            return $strLanguage;
        }

        if ($this->isLoggedin()) {
            if ($this->isAdmin()) {
                if ($this->getUser() != null && $this->getUser()->getStrAdminlanguage() != "") {
                    $strLang = $this->getUser()->getStrAdminlanguage();
                    $this->setSession(self::STR_SESSION_ADMIN_LANG_KEY, $strLang);
                    return $strLang;
                }
            }
        } else {
            //try to load a language the user requested
            $strUserLanguages = str_replace(";", ",", getServer("HTTP_ACCEPT_LANGUAGE"));
            if (StringUtil::length($strUserLanguages) > 0) {
                $arrLanguages = explode(",", $strUserLanguages);
                //check, if one of the requested languages is available on our system
                foreach ($arrLanguages as $strOneLanguage) {
                    if (!preg_match("#q\=[0-9]\.[0-9]#i", $strOneLanguage)) {
                        if (in_array($strOneLanguage, explode(",", Carrier::getInstance()->getObjConfig()->getConfig("adminlangs")))) {
                            return $strOneLanguage;
                        }
                    }
                }
            }
        }

        return Lang::getInstance()->getStrFallbackLanguage();
    }

    /**
     * Checks if a user is set active or not
     *
     * @return bool
     * @throws Exception
     */
    public function isActive()
    {
        if ($this->isLoggedin()) {
            if ($this->getUser() && $this->getUser()->getIntRecordStatus() == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    /**
     * Tries to log a user into the system.
     * In normal cases, you'd rather user the method Session::login($strName, $strPass).
     * This method is only useful if you have a concrete user object and want to make this user the
     * currently active one.
     *
     * @param UserUser $objUser
     *
     * @return bool
     * @throws Exception
     * @see Session::login($strName, $strPass)
     */
    public function loginUser(UserUser $objUser)
    {
        //validate if the user is assigned to at least a single group
        if (empty($objUser->getArrGroupIds())) {
            throw new AuthenticationException("user ".$objUser->getStrDisplayName()." is not assigned to at least a single group", AuthenticationException::$level_ERROR);
        }
        return $this->internalLoginHelper($objUser);
    }


    /**
     * Logs a user into the system if the credentials are correct
     * and the user is active.
     * Only logins with username and password are allowed. This avoids problems with
     * mis-configured systems such as MS AD.
     *
     * @param string $strName
     * @param string $strPassword
     *
     * @return bool
     * @throws AuthenticationException
     * @throws Exception
     */
    public function login($strName, $strPassword)
    {
        $bitReturn = false;
        //How many users are out there with this username and being active?
        $objUsersources = new UserSourcefactory();
        try {
            if ($objUsersources->authenticateUser($strName, $strPassword)) {
                $objUser = $objUsersources->getUserByUsername($strName);
                $bitReturn = $this->internalLoginHelper($objUser);
            }
        } catch (AuthenticationException $objEx) {
            Logger::getInstance()->info("Unsuccessful login attempt by user ".$strName);
            UserLog::generateLog(0, $strName);

            throw $objEx;
        } catch (PasswordExpiredException $objEx) {
            Logger::getInstance()->info("Unsuccessful login attempt by user ".$strName." password is expired");
            UserLog::generateLog(0, $strName);

            throw $objEx;
        }

        return $bitReturn;
    }


    /**
     * Helper to switch the session to a different user. This is may be used to test access-profiles.
     * Due to security concerns, only members of the admin-group are allowed to switch to another user.
     *
     * @param UserUser $objTargetUser
     *
     * @param bool $bitForce
     * @return bool
     * @throws Exception
     */
    public function switchSessionToUser(UserUser $objTargetUser, $bitForce = false)
    {
        if ($this->isLoggedin()) {
            if (Carrier::getInstance()->getObjSession()->isSuperAdmin() || $bitForce) {
                $this->getObjInternalSession()->setStrLoginstatus(SystemSession::$LOGINSTATUS_LOGGEDIN);
                $this->getObjInternalSession()->setStrUserid($objTargetUser->getSystemid());
                $this->setSession(self::STR_SESSION_USERID, $objTargetUser->getSystemid());

                $this->setSession(self::STR_SESSION_GROUPIDS, implode(",", $objTargetUser->getArrGroupIds()));
                $this->setSession(self::STR_SESSION_GROUPIDS_SHORT, implode(",", $objTargetUser->getArrShortGroupIds()));
                ServiceLifeCycleFactory::getLifeCycle(get_class($this->getObjInternalSession()))->update($this->getObjInternalSession());
                $this->objUser = $objTargetUser;

                return true;
            }
        }
        return false;
    }


    /**
     * Does all the internal login-handling
     *
     * @param UserUser $objUser
     *
     * @return bool
     * @throws Exception
     */
    private function internalLoginHelper(UserUser $objUser)
    {

        if ($objUser->getIntRecordStatus() == 1) {
            $this->getObjInternalSession()->setStrLoginstatus(SystemSession::$LOGINSTATUS_LOGGEDIN);
            $this->getObjInternalSession()->setStrUserid($objUser->getSystemid());
            $this->getObjInternalSession()->setStrLoginprovider($objUser->getStrSubsystem());

            //save some metadata to the php-session
            $this->setSession(self::STR_SESSION_USERID, $objUser->getSystemid());
            $this->setSession(self::STR_SESSION_GROUPIDS, implode(",", $objUser->getArrGroupIds()));
            $this->setSession(self::STR_SESSION_GROUPIDS_SHORT, implode(",", $objUser->getArrShortGroupIds()));
            $this->setSession(self::STR_SESSION_ISADMIN, $objUser->getIntAdmin());

            ServiceLifeCycleFactory::getLifeCycle(get_class($this->getObjInternalSession()))->update($this->getObjInternalSession());
            $this->objUser = $objUser;

            //trigger listeners on first login
            if ($objUser->getIntLogins() == 0) {
                CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_USERFIRSTLOGIN, array($objUser->getSystemid()));
            }

            $objUser->setIntLogins($objUser->getIntLogins() + 1);
            $objUser->setIntLastLogin(time());

            if (!$objUser->getLockManager()->isAccessibleForCurrentUser()) {
                //force an unlock, may be locked since the user is being edited in the backend
                $objUser->getLockManager()->unlockRecord(true);
            }
            ServiceLifeCycleFactory::getLifeCycle(get_class($objUser))->update($objUser);

            //Drop a line to the logger
            Logger::getInstance()->info("User: ".$objUser->getStrUsername()." successfully logged in, login provider: ".$objUser->getStrSubsystem());
            UserLog::generateLog();

            //right now we have the time to do a few cleanups...
            SystemSession::deleteInvalidSessions();

            //call listeners
            CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_USERLOGIN, array($objUser->getSystemid()));

            //Login successful, quit
            $bitReturn = true;
        } else {
            //User is inactive
            $bitReturn = false;
        }

        return $bitReturn;
    }

    /**
     * Logs a user off from the system
     *
     * @return void
     * @throws Exception
     */
    public function logout()
    {
        Logger::getInstance()->info("User: ".$this->getUsername()." successfully logged out");
        UserLog::registerLogout();

        CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_USERLOGOUT, array($this->getUserID()));
        $this->getObjInternalSession()->deleteObjectFromDatabase();

        $this->objInternalSession = null;
        $this->objUser = null;
        $this->setSession(self::STR_SESSION_USERID, "");
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000);
        }
        // Finally, destroy the session.
        @session_destroy();
        //start a new one
        $this->bitPhpSessionStarted = false;
        $this->sessionStart();
        //and create a new sessid
        @session_regenerate_id();
        $this->initInternalSession();
        return;
    }

    /**
     * Returns the name of the current user
     *
     * @return string
     * @throws Exception
     */
    public function getUsername()
    {
        if ($this->isLoggedin() && $this->getObjInternalSession() != null && $this->getUser() != null) {
            $strUsername = $this->getUser()->getStrUsername();
        } else {
            $strUsername = "Guest";
        }
        return $strUsername;
    }

    /**
     * Returns the userid or ''
     *
     * @return string
     * @throws Exception
     */
    public function getUserID()
    {
        $strUserid = $this->getSession(self::STR_SESSION_USERID);
        if (validateSystemid($strUserid) && $this->isLoggedin()) {
            return $strUserid;
        }
        return '';
    }

    /**
     * Checks whether is internal session is active or not
     * @return bool
     */
    public function isInitialized()
    {
        return $this->objInternalSession !== null;
    }

    /**
     * Returns an instance of the current user or null of not given
     *
     * @return UserUser
     * @throws Exception
     */
    public function getUser()
    {
        if ($this->objUser != null) {
            return $this->objUser;
        }

        if ($this->getUserID() != "") {
            $this->objUser = Objectfactory::getInstance()->getObject($this->getUserID());
            return $this->objUser;
        }

        return null;
    }

    /**
     * Resets the internal reference to the current user, e.g. to load new values from the database.
     * Reloads the group-assignment of a user
     *
     * @return void
     * @throws Exception
     */
    public function resetUser()
    {
        if ($this->getUserID() != "") {
            $this->objUser = Objectfactory::getInstance()->getObject($this->getUserID(), true);
            //reload group-ids to the session
            if ($this->objUser !== null) {
                $this->setSession(self::STR_SESSION_GROUPIDS, implode(",", $this->objUser->getArrGroupIds()));
                $this->setSession(self::STR_SESSION_GROUPIDS_SHORT, implode(",", $this->objUser->getArrShortGroupIds()));
            }
        }
    }

    /**
     * Updates the flag to re-init a session for ALL sessions currently opened.
     * @throws Exception
     */
    public function resetAllUser()
    {
        if ($this->getObjInternalSession() != null) {
            $this->getObjInternalSession()->forceUserReset();
        }
    }

    /**
     * Returns the systemids of groups the user is member in as a string
     *
     * @return string
     * @throws Exception
     */
    public function getGroupIdsAsString()
    {
        if ($this->getObjInternalSession() != null) {
            return $this->getSession(self::STR_SESSION_GROUPIDS);
        }
        return "";
    }

    /**
     * Returns the systemids of groups the user is member in as an array
     *
     * @return array
     * @throws Exception
     */
    public function getGroupIdsAsArray()
    {
        return explode(",", $this->getGroupIdsAsString());
    }

    /**
     * Returns the short ids of groups the user is member in as an array
     *
     * @return array
     * @throws Exception
     */
    public function getShortGroupIdsAsArray()
    {
        if ($this->getObjInternalSession() != null) {
            $strGroupids = $this->getSession(self::STR_SESSION_GROUPIDS_SHORT);
        } else {
            $strGroupids = "";
        }
        return explode(",", $strGroupids);
    }

    /**
     * Returns the current Session-ID used by php
     *
     * @return string
     */
    public function getSessionId()
    {
        return session_id();
    }

    /**
     * Returns the internal session id used by kajona, so NOT by php
     *
     * @return string
     * @throws Exception
     */
    public function getInternalSessionId()
    {
        if ($this->getObjInternalSession() != null) {
            return $this->getObjInternalSession()->getSystemid();
        } else {
            return $this->getSessionId();
        }
    }

    /**
     * Initializes the internal kajona session
     *
     * @return void
     * @throws Exception
     */
    public function initInternalSession()
    {

        if ($this->getSession("KAJONA_INTERNAL_SESSID") == false) {
            $arrTables = Database::getInstance()->getTables();
            if (!in_array("agp_session", $arrTables)) {
                return;
            }
        }

        $this->bitLazyLoaded = true;

        if ($this->getSession("KAJONA_INTERNAL_SESSID") !== false) {
            $this->objInternalSession = SystemSession::getSessionById($this->getSession("KAJONA_INTERNAL_SESSID"));

            if ($this->objInternalSession != null && $this->objInternalSession->isSessionValid()) {
                if ($this->objInternalSession->getBitResetUser()) {
                    //need to reset the user
                    $this->resetUser();
                    $this->objInternalSession->invalidateUserResetFlag();
                }
                $this->objInternalSession->setIntReleasetime(time() + (int)SystemSetting::getConfigValue("_system_release_time_"));
                $this->objInternalSession->setStrLasturl(getServer("QUERY_STRING"));
            } else {
                $this->objInternalSession = null;
            }

            if ($this->objInternalSession != null) {
                return;
            }

        }

        $objSession = new SystemSession();
        $objSession->setStrPHPSessionId($this->getSessionId());
        $objSession->setStrUserid($this->getUserID());
        $objSession->setIntReleasetime(time() + (int)SystemSetting::getConfigValue("_system_release_time_"));
        $objSession->setStrLasturl(getServer("QUERY_STRING"));
        $objSession->setSystemid(generateSystemid());

        //this update is removed. the internal session validates on destruct, if an update or an insert is required
        //$objSession->updateObjectToDb();

        $this->setSession("KAJONA_INTERNAL_SESSID", $objSession->getSystemid());
        $this->objInternalSession = $objSession;
    }

    /**
     * @return SystemSession
     * @throws Exception
     */
    private function getObjInternalSession()
    {
        //lazy loading
        if ($this->objInternalSession == null && !$this->bitLazyLoaded) {
            $this->initInternalSession();
        }

        return $this->objInternalSession;
    }

    /**
     * @return bool
     */
    public function getBitClosed()
    {
        return $this->bitClosed;
    }

    /**
     * @param bool $bitBlockDbUpdate
     *
     * @return void
     */
    public function setBitBlockDbUpdate($bitBlockDbUpdate)
    {
        $this->bitBlockDbUpdate = $bitBlockDbUpdate;
    }


}


