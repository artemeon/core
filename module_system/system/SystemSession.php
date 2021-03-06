<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;


/**
 * Model for a single session. Session are managed by Session, so there should be no need
 * to create instances directly.
 * Session-Entries are not reflected by a systemrecord
 *
 * @package module_system
 * @author sidler@mulchprod.de
 *
 * @module system
 * @moduleId _system_modul_id_
 *
 * @blockFromAutosave
 */
class SystemSession extends Model implements ModelInterface
{

    /**
     * Internal session id. used to validate if the current session was already persisted to the database.
     *
     * @var string
     */
    private $strDbSystemid = "";

    public static $LOGINSTATUS_LOGGEDIN = "loggedin";
    public static $LOGINSTATUS_LOGGEDOUT = "loggedout";

    private $strPHPSessionId = "";
    private $strUserid = "";
    private $intReleasetime = 0;
    private $strLoginprovider = "";
    private $strLasturl = "";
    private $strLoginstatus = "";
    private $bitResetUser = null;

    /**
     * Constructor to create a valid object
     *
     * @param string $strSystemid (use "" on new objects)
     */
    public function __construct($strSystemid = "")
    {
        $this->strLoginstatus = self::$LOGINSTATUS_LOGGEDOUT;

        //base class
        parent::__construct($strSystemid);
    }

    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     */
    public function getStrDisplayName()
    {
        return $this->getSystemid();
    }

    /**
     * Updates the flag to re-init a session for ALL sessions currently opened.
     * @return bool
     */
    public function forceUserReset()
    {
        return $this->objDB->_pQuery("UPDATE agp_session SET session_resetuser = 1", []);
    }

    /**
     * Invalidates the flag for the current user to reset the session, e.g. since the reset was performed
     * @return bool
     */
    public function invalidateUserResetFlag()
    {
        return $this->objDB->_pQuery("UPDATE agp_session SET session_resetuser = null WHERE session_id = ?", [$this->getSystemid()]);
    }

    /**
     * Initalises the current object, if a systemid was given
     *
     * @return void
     */
    protected function initObjectInternal()
    {

        $strQuery = "SELECT * FROM agp_session WHERE session_id = ?";
        $arrRow = $this->objDB->getPRow($strQuery, array($this->getSystemid()));

        //avoid useless query, set internal row
        $this->setArrInitRow(array("system_id" => ""));

        if (count($arrRow) > 1) {
            $this->setStrPHPSessionId($arrRow["session_phpid"]);
            $this->setStrUserid($arrRow["session_userid"]);
            $this->setIntReleasetime($arrRow["session_releasetime"]);
            $this->setStrLoginstatus($arrRow["session_loginstatus"]);
            $this->setStrLoginprovider($arrRow["session_loginprovider"]);
            $this->setStrLasturl($arrRow["session_lasturl"]);
            if (isset($arrRow["session_resetuser"]) && $arrRow["session_resetuser"] == 1) {
                $this->bitResetUser = true;
            }

            $this->strDbSystemid = $this->getSystemid();
        }
    }

    /**
     * saves the current object with all its params back to the database
     *
     * @param bool $strPrevId
     *
     * @return bool
     * @overwrite \Kajona\System\System\Model::updateObjectToDb() due to performance issues
     */
    public function updateObjectToDb($strPrevId = false)
    {
        if ($this->strDbSystemid == "") {
            $this->strDbSystemid = $this->getSystemid();

            //only relevant for special conditions, no usage in real world scenarios since handled by Session
            if (!validateSystemid($this->strDbSystemid)) {
                $this->strDbSystemid = generateSystemid();
                $this->setSystemid($this->strDbSystemid);
            }

            Logger::getInstance()->info("new session ".$this->getSystemid());

            //insert in session table
            $strQuery = "INSERT INTO agp_session
                         (session_id,
                          session_phpid,
                          session_userid,
                          session_releasetime,
                          session_loginstatus,
                          session_loginprovider,
                          session_lasturl
                          ) VALUES ( ?,?,?,?,?,?,? )";

            return $this->objDB->_pQuery(
                $strQuery,
                array(
                    $this->strDbSystemid,
                    $this->getStrPHPSessionId(),
                    $this->getStrUserid(),
                    (int)$this->getIntReleasetime(),
                    $this->getStrLoginstatus(),
                    $this->getStrLoginprovider(),
                    $this->getStrLasturl()
                )
            );

        } else {
            Logger::getInstance()->info("updated session ".$this->getSystemid());
            $strQuery = "UPDATE agp_session SET
                          session_phpid = ?,
                          session_userid = ?,
                          session_releasetime = ?,
                          session_loginstatus = ?,
                          session_loginprovider = ?,
                          session_lasturl = ?
                        WHERE session_id = ? ";

            return $this->objDB->_pQuery(
                $strQuery,
                array(
                    $this->getStrPHPSessionId(),
                    $this->getStrUserid(),
                    (int)$this->getIntReleasetime(),
                    $this->getStrLoginstatus(),
                    $this->getStrLoginprovider(),
                    $this->getStrLasturl(),
                    $this->getSystemid()
                )
            );
        }
    }

    /**
     * Called whenever an update-request was fired.
     * Use this method to synchronize yourselves with the database.
     * Use only updates, inserts are not required to be implemented.
     *
     * @return bool
     */
    protected function updateStateToDb()
    {
        return true;
    }


    /**
     * Deletes the current object from the database
     *
     * @return bool
     */
    public function deleteObjectFromDatabase()
    {
        Logger::getInstance()->info("deleted session ".$this->getSystemid());
        //start with the module-table
        $strQuery = "DELETE FROM agp_session WHERE session_id = ?";
        return $this->objDB->_pQuery($strQuery, array($this->getSystemid()));
    }

    /**
     * Overwritten, no real delete required
     *
     * @return bool
     */
    public function deleteObject()
    {
        return $this->deleteObjectFromDatabase();
    }


    /**
     * Returns, if available, the internal session-object for the passed internal session-id
     *
     * @param string $strSessionid
     *
     * @return SystemSession
     */
    public static function getSessionById($strSessionid)
    {
        $objSession = new SystemSession($strSessionid);
        if ($objSession->isSessionValid()) {
            return $objSession;
        } else {
            return null;
        }
    }


    /**
     * Returns, if available, the internal session-object for the passed internal session-id
     *
     * @param int $intStart
     * @param int $intEnd
     *
     * @return SystemSession[]
     */
    public static function getAllActiveSessions($intStart = null, $intEnd = null)
    {

        $strQuery = "SELECT session_id FROM agp_session WHERE session_releasetime > ? ORDER BY session_releasetime DESC, session_id ASC";
        $arrIds = Carrier::getInstance()->getObjDB()->getPArray($strQuery, array(time()), $intStart, $intEnd);

        $arrReturn = array();
        foreach ($arrIds as $arrOneId) {
            $arrReturn[] = new SystemSession($arrOneId["session_id"]);
        }

        return $arrReturn;
    }

    /**
     * Returns the number of session currently being active
     *
     * @return int
     */
    public static function getNumberOfActiveSessions()
    {
        $strQuery = "SELECT COUNT(*) AS cnt FROM agp_session WHERE session_releasetime > ?";

        $arrRow = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array(time()));
        return $arrRow["cnt"];
    }


    /**
     * Returns if the current user has logged in or not
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        if ($this->isSessionValid() && $this->getStrLoginstatus() == self::$LOGINSTATUS_LOGGEDIN) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes all invalid session-entries from the database
     *
     * @return bool
     */
    public static function deleteInvalidSessions()
    {
        $strSql = "DELETE FROM agp_session WHERE session_releasetime < ?";
        return Carrier::getInstance()->getObjDB()->_pQuery($strSql, array(time()));
    }

    /**
     * @return bool
     */
    public function isSessionValid()
    {
        return $this->getIntReleasetime() > time();
    }


    /**
     * @param string $strPHPSessId
     *
     * @return void
     */
    public function setStrPHPSessionId($strPHPSessId)
    {
        $this->strPHPSessionId = $strPHPSessId;
    }

    /**
     * @param string $strUserid
     *
     * @return void
     */
    public function setStrUserid($strUserid)
    {
        $this->strUserid = $strUserid;
    }

    /**
     * @param int $intReleasetime
     *
     * @return void
     */
    public function setIntReleasetime($intReleasetime)
    {
        $this->intReleasetime = $intReleasetime;
    }

    /**
     * @param string $strLoginprovider
     *
     * @return void
     */
    public function setStrLoginprovider($strLoginprovider)
    {
        $this->strLoginprovider = $strLoginprovider;
    }

    /**
     * @param string $strLasturl
     *
     * @return void
     */
    public function setStrLasturl($strLasturl)
    {
        //limit to 255 chars
        $this->strLasturl = StringUtil::truncate($strLasturl, 450, "");
    }

    /**
     * @param string $strLoginstatus
     *
     * @return void
     */
    public function setStrLoginstatus($strLoginstatus)
    {
        $this->strLoginstatus = $strLoginstatus;
    }

    /**
     * @return string
     */
    public function getStrPHPSessionId()
    {
        return $this->strPHPSessionId;
    }

    /**
     * @return string
     */
    public function getStrUserid()
    {
        return $this->strUserid;
    }

    /**
     * @return int
     */
    public function getIntReleasetime()
    {
        return $this->intReleasetime;
    }

    /**
     * @return string
     */
    public function getStrLoginprovider()
    {
        return $this->strLoginprovider;
    }

    /**
     * @return string
     */
    public function getStrLasturl()
    {
        return $this->strLasturl;
    }

    /**
     * @return string
     */
    public function getStrLoginstatus()
    {
        return $this->strLoginstatus;
    }

    /**
     * @return bool
     */
    public function getBitResetUser()
    {
        return $this->bitResetUser;
    }
}
