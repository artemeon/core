<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                            *
********************************************************************************************************/

namespace Kajona\System\System\Usersources;

use Kajona\System\System\Carrier;
use Kajona\System\System\Database;
use Kajona\System\System\Logger;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Security\PasswordExpiredException;
use Kajona\System\System\Security\PasswordRotator;
use Kajona\System\System\ServiceProvider;
use Kajona\System\System\StringUtil;
use Kajona\System\System\UserUser;


/**
 * The kajona usersource is the global entry and factory / facade for the classical kajona usersystem
 *
 * @author sidler@mulchprod.de
 * @since 3.4.1
 * @package module_usersource
 */
class UsersourcesSourceKajona implements UsersourcesUsersourceInterface
{
    private static $arrUserCache = array();

    /**
     * @var \Kajona\System\System\Database
     */
    private $objDB;

    /**
     * @var PasswordRotator
     */
    private $objPasswordRotator;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->objDB = Carrier::getInstance()->getObjDB();
        $this->objPasswordRotator = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_PASSWORD_ROTATOR);
    }

    /**
     * Returns a readable name of the source, e.g. "Kajona" or "LDAP Company 1"
     *
     * @return mixed
     */
    public function getStrReadableName()
    {
        return Carrier::getInstance()->getObjLang()->getLang("usersource_kajona_name", "user");
    }


    /**
     * Tries to authenticate a user with the given credentials.
     * The password is unencrypted, each source should take care of its own encryption.
     *
     * @param UsersourcesUserInterface|UsersourcesUserKajona $objUser
     * @param string $strPassword
     *
     * @return bool
     */
    public function authenticateUser(UsersourcesUserInterface $objUser, $strPassword)
    {
        if ($objUser instanceof UsersourcesUserKajona) {
            $bitMD5Encryption = false;
            if (StringUtil::length($objUser->getStrFinalPass()) == 32) {
                $bitMD5Encryption = true;
            }
            if ($objUser->getStrFinalPass() == self::encryptPassword($strPassword, $objUser->getStrSalt(), $bitMD5Encryption)) {
                // check whether password is expired
                if ($this->objPasswordRotator->isPasswordExpired($objUser)) {
                    throw new PasswordExpiredException($objUser->getSystemid(), "Password is expired");
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getCreationOfGroupsAllowed()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function getCreationOfUsersAllowed()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function getMembersEditable()
    {
        return true;
    }

    /**
     * Loads the group identified by the passed id
     *
     * @param string $strId
     *
     * @return UsersourcesGroupInterface or null
     */
    public function getGroupById($strId)
    {
        $strQuery = "SELECT group_id FROM agp_user_group_kajona WHERE group_id = ?";

        $arrIds = $this->objDB->getPRow($strQuery, array($strId));
        if (isset($arrIds["group_id"]) && validateSystemid($arrIds["group_id"])) {
            return new UsersourcesGroupKajona($arrIds["group_id"]);
        }

        return null;
    }

    /**
     * Returns an empty group, e.g. to fetch the fields available and
     * in order to fill a new one.
     *
     * @return UsersourcesGroupInterface
     */
    public function getNewGroup()
    {
        return new UsersourcesGroupKajona();
    }

    /**
     * Returns an empty user, e.g. to fetch the fields available and
     * in order to fill a new one.
     *
     * @return UsersourcesUserInterface
     */
    public function getNewUser()
    {
        return new UsersourcesUserKajona();
    }

    /**
     * Loads the user identified by the passed id
     *
     * @param string $strId
     *
     * @param bool $bitIgnoreDeletedFlag
     * @return UsersourcesUserInterface or null
     */
    public function getUserById($strId, $bitIgnoreDeletedFlag = false)
    {

        if (isset(self::$arrUserCache[$strId])) {
            return self::$arrUserCache[$strId];
        }

        $strQuery = "SELECT user_id FROM agp_user_kajona  WHERE user_id = ? ";

        $arrIds = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strId));
        if (isset($arrIds["user_id"]) && validateSystemid($arrIds["user_id"])) {
            self::$arrUserCache[$strId] = new UsersourcesUserKajona($arrIds["user_id"]);
            return self::$arrUserCache[$strId];
        }

        return null;
    }

    /**
     * Loads the user identified by the passed name.
     * This method may be called during the authentication of users and may be used as a hook
     * in order to create new users in the central database not yet existing.
     *
     * @param string $strUsername
     *
     * @return UsersourcesUserInterface or null
     */
    public function getUserByUsername($strUsername)
    {
        $strQuery = "SELECT user_id FROM agp_user, agp_system WHERE user_id = system_id AND user_username = ? AND user_subsystem = 'kajona' AND (system_deleted = 0 OR system_deleted IS NULL)";

        $arrIds = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strUsername));
        if (isset($arrIds["user_id"]) && validateSystemid($arrIds["user_id"])) {
            if (!isset(self::$arrUserCache[$arrIds["user_id"]])) {
                self::$arrUserCache[$arrIds["user_id"]] = new UsersourcesUserKajona($arrIds["user_id"]);
            }

            return self::$arrUserCache[$arrIds["user_id"]];
        }

        return null;
    }

    /**
     * @inheritdoc
     * @param $strUsername
     * @return array|UserUser[]
     */
    public function searchUser($strUsername, $intMax = 10)
    {
        $connection = Database::getInstance();

        $strQuery = "SELECT user_tbl.user_id
                      FROM agp_system, agp_user AS user_tbl
                      LEFT JOIN agp_user_kajona AS user_kajona ON user_tbl.user_id = user_kajona.user_id
                      WHERE
                          (
                          user_tbl.user_username LIKE ? 
                          OR user_kajona.user_forename LIKE ? 
                          OR user_kajona.user_name LIKE ? 
                          OR ".$connection->getConcatExpression(['user_kajona.user_forename', '\' \'', 'user_kajona.user_name'])." LIKE ?
                          OR ".$connection->getConcatExpression(['user_kajona.user_name', '\' \'', 'user_kajona.user_forename'])." LIKE ?
                          OR ".$connection->getConcatExpression(['user_kajona.user_name', '\', \'', 'user_kajona.user_forename'])." LIKE ?                  
                          )
                          AND user_tbl.user_id = system_id
                          AND (system_deleted = 0 OR system_deleted IS NULL)
                      ORDER BY user_tbl.user_username, user_tbl.user_subsystem ASC";

        $arrParams = array("%".$strUsername."%", "%".$strUsername."%", "%".$strUsername."%", "%".$strUsername."%", "%".$strUsername."%", "%".$strUsername."%");

        $arrIds = Carrier::getInstance()->getObjDB()->getPArray($strQuery, $arrParams, 0, $intMax);

        $arrReturn = array();
        foreach ($arrIds as $arrOneId) {
            $arrReturn[] = Objectfactory::getInstance()->getObject($arrOneId["user_id"]);
        }

        return $arrReturn;
    }


    /**
     * Fetches a user by mail. This way of fetching users is not officially supported since not covered by all login-providers.
     *
     * @param string $strEmail
     *
     * @return UsersourcesUserInterface or null
     */
    public function getUserByEmail($strEmail)
    {
        $strQuery = "SELECT sysuser.user_id 
                       FROM agp_user as sysuser, 
                            agp_user_kajona as kjuser, 
                            agp_system 
                      WHERE sysuser.user_id = system_id 
                        AND sysuser.user_id = kjuser.user_id 
                        AND user_email = ? 
                        AND user_subsystem = 'kajona' 
                        AND (system_deleted = 0 OR system_deleted IS NULL)";

        $arrIds = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strEmail));
        if (isset($arrIds["user_id"]) && validateSystemid($arrIds["user_id"])) {
            if (!isset(self::$arrUserCache[$arrIds["user_id"]])) {
                self::$arrUserCache[$arrIds["user_id"]] = new UsersourcesUserKajona($arrIds["user_id"]);
            }

            return self::$arrUserCache[$arrIds["user_id"]];
        }

        return null;
    }


    /**
     * Encrypts the password, e.g. in order to be validated during logins
     *
     * @param string $strPassword
     * @param string $strSalt
     * @param bool $bitMD5Encryption
     *
     * @return string
     */
    public static function encryptPassword($strPassword, $strSalt = "", $bitMD5Encryption = false)
    {
        if ($bitMD5Encryption) {
            Logger::getInstance(Logger::USERSOURCES)->warning("usage of old md5-encrypted password!");
            return md5($strPassword);
        }

        if ($strSalt == "") {
            return sha1($strPassword);
        } else {
            return sha1(md5($strSalt).$strPassword);
        }
    }


    /**
     * @inheritdoc
     */
    public function getAllGroupIds($bitIgnoreSystemGroups = false)
    {
        $strQuery = "SELECT gk.group_id as group_id
                       FROM agp_user_group_kajona AS gk,
                            agp_user_group AS g
                      WHERE g.group_id = gk.group_id
                           ".($bitIgnoreSystemGroups ? "AND (g.group_system_group != 1 OR g.group_system_group IS NULL) " : "")."
                      ORDER BY g.group_name ASC";
        $arrRows = Carrier::getInstance()->getObjDB()->getPArray($strQuery, array());
        $arrReturn = array();
        foreach ($arrRows as $arrOneRow) {
            $arrReturn[] = $arrOneRow["group_id"];
        }

        return $arrReturn;
    }

}
