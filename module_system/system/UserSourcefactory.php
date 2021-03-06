<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;

use Kajona\System\System\Usersources\UsersourcesGroupInterface;
use Kajona\System\System\Usersources\UsersourcesUserInterface;
use Kajona\System\System\Usersources\UsersourcesUsersourceInterface;


/**
 * The sourcefactory holds references to all subsystems and manages the global access.
 * It resolves the leightweight objects into its "real" objects provided by the subsystems
 * and takes care of global functionalities such as authentication of users.
 *
 * @author sidler@mulchprod.de
 * @since 3.4.1
 * @package module_user
 */
class UserSourcefactory
{

    private $arrSubsystemsAvailable = array("kajona");

    /**
     * Default constructor
     */
    public function __construct()
    {

        //try to load the list of subsystems available
        $strConfig = Carrier::getInstance()->getObjConfig()->getConfig("loginproviders");
        if ($strConfig != "") {
            $this->arrSubsystemsAvailable = explode(",", $strConfig);
        }
    }

    /**
     * Tries to find a group identified by its name in the configured subsystems.
     * If given, the first match is returned.
     * Please note that the leightweight object is returned!
     *
     * @param string $strName
     *
     * @return UserGroup or null
     */
    public function getGroupByName($strName)
    {

        //validate if a group with the given name is available
        $strQuery = "SELECT group_id FROM agp_user_group where group_name = ?";
        $arrRow = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strName));

        if (isset($arrRow["group_id"]) && validateSystemid($arrRow["group_id"])) {
            return Objectfactory::getInstance()->getObject($arrRow["group_id"]);
        }

        //nothing found
        return null;
    }

    /**
     * Returns a list of groups matching the passed query-term.
     *
     * @param string $strName
     * @param int|null $intStart
     * @param int|null $intEnd
     * @return UserGroup[]
     */
    public function getGrouplistByQuery($strName, $intStart = null, $intEnd = null)
    {
        //validate if a group with the given name is available
        $strQuery = "SELECT ugroup.group_id, 
                            ugroup.group_subsystem 
                       FROM agp_user_group ugroup
                 INNER JOIN agp_system groupsys
                         ON ugroup.group_id = groupsys.system_id
                      WHERE (groupsys.system_deleted = 0 OR groupsys.system_deleted IS NULL)
                        AND ugroup.group_name LIKE ?";
        $arrRows = Carrier::getInstance()->getObjDB()->getPArray($strQuery, array("%".$strName."%"), $intStart, $intEnd);

        $arrReturn = array();
        foreach ($arrRows as $arrOneRow) {
            if (in_array($arrOneRow["group_subsystem"], $this->arrSubsystemsAvailable)) {
                $arrReturn[] = Objectfactory::getInstance()->getObject($arrOneRow["group_id"]);
            }
        }
        return $arrReturn;
    }

    /**
     * Tries to find an user identified by its name in the configured subsystems.
     * If given, the first match is returned.
     * Please note that the lightweight object is returned!
     *
     * @param string $strName
     *
     * @return UserUser|null
     */
    public function getUserByUsername($strName)
    {

        //validate if a group with the given name is available

        $strQuery = "SELECT user_id FROM agp_user, agp_system where user_id = system_id AND user_username = ? AND (system_deleted = 0 OR system_deleted IS NULL)";
        $arrRow = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strName));

        if (isset($arrRow["user_id"]) && validateSystemid($arrRow["user_id"])) {
            return Objectfactory::getInstance()->getObject($arrRow["user_id"]);
        }

        //since some login-provides may trigger additional searches, query them now
        foreach ($this->arrSubsystemsAvailable as $strOneSubsystem) {
            try {
                $objUser = $this->getUsersource($strOneSubsystem)->getUserByUsername($strName);
                //convert the user to a real one
                if ($objUser != null) {
                    return Objectfactory::getInstance()->getObject($objUser->getSystemid());
                }
            } catch (Exception $e) {
                Logger::getInstance(Logger::USERSOURCES)->critical($e->getMessage());
            }
        }

        //nothing found
        return null;
    }

    /**
     * Creates a list of all users matching the current query.
     * Only active users may be returned!
     *
     * @param string $strParam
     * @param int|null $intStart
     * @param int|null $intEnd
     * @param string|null $strGroupId
     * @return UserUser[]
     * @throws Exception
     */
    public function getUserlistByUserquery($strParam, $intStart = null, $intEnd = null, $strGroupId = null)
    {
        $arrReturn = [];
        foreach ($this->arrSubsystemsAvailable as $subsystem) {
            //append a custom search by each subsystem
            $subsystem = $this->getUsersource($subsystem);
            $arrReturn = array_merge($arrReturn, $subsystem->searchUser($strParam));
        }

        if (validateSystemid($strGroupId)) {
            $arrReturn = array_filter($arrReturn, function (UserUser $user) use ($strGroupId) {
                return in_array($strGroupId, $user->getArrGroupIds());
            });
        }

        uasort($arrReturn, function (UserUser $a, UserUser $b) {
            return strcmp($a->getStrDisplayName(), $b->getStrDisplayName());
        });

        //remove duplicates
        $arrUnified = [];
        foreach ($arrReturn as $user) {
            $arrUnified[$user->getSystemid()] = $user;
        }

        return array_values($arrUnified);
    }


    /**
     * Tries to authenticate a user identified by its username and password.
     * If given the leightweight user-object is returned.
     * Otherwise null is returned AND an authentication-exception is being raised.
     * Only logins with username and password are allowed. This avoids problems with
     * mis-configured systems such as MS AD.
     *
     * @param string $strName
     * @param string $strPassword
     *
     * @throws AuthenticationException
     * @return UsersourcesUserInterface
     */
    public function authenticateUser($strName, $strPassword)
    {
        if (empty($strName) || empty($strPassword)) {
            throw new AuthenticationException("user ".$strName." could not be authenticated", AuthenticationException::$level_ERROR);
        }

        $objUser = $this->getUserByUsername($strName);
        if ($objUser != null) {
            //validate if the user is assigned to at least a single group
            if (empty($objUser->getArrGroupIds())) {
                throw new AuthenticationException("user ".$strName." is not assigned to at least a single group", AuthenticationException::$level_ERROR);
            }

            $objSubsystem = $this->getUsersource($objUser->getStrSubsystem());
            $objPlainUser = $objSubsystem->getUserById($objUser->getSystemid());


            if ($objPlainUser != null && $objSubsystem->authenticateUser($objPlainUser, $strPassword)) {
                return true;
            }
        }

        throw new AuthenticationException("user ".$strName." could not be authenticated", AuthenticationException::$level_ERROR);
    }

    /**
     * Returns the fully featured group-instance created by the matching subsystem.
     *
     * @param UserGroup $objLeightweightGroup
     *
     * @return UsersourcesGroupInterface
     */
    public function getSourceGroup(UserGroup $objLeightweightGroup)
    {
        $objSubsystem = $this->getUsersource($objLeightweightGroup->getStrSubsystem());
        $objPlainGroup = $objSubsystem->getGroupById($objLeightweightGroup->getSystemid());
        return $objPlainGroup;
    }

    /**
     * Returns the fully featured user-instance created by the matching subsystem.
     *
     * @param UserUser $objLeightweightUser
     *
     * @param bool $bitIgnoreDeletedFlag
     * @return UsersourcesUserInterface
     * @throws Exception
     */
    public function getSourceUser(UserUser $objLeightweightUser, $bitIgnoreDeletedFlag = false)
    {
        if (!$bitIgnoreDeletedFlag && $objLeightweightUser->getIntRecordDeleted() == 1) {
            throw new Exception("User was deleted, source user no longer available", Exception::$level_ERROR);
        }

        $objSubsystem = $this->getUsersource($objLeightweightUser->getStrSubsystem());
        $objPlainUser = $objSubsystem->getUserById($objLeightweightUser->getSystemid(), $bitIgnoreDeletedFlag);
        return $objPlainUser;
    }

    /**
     * Tries to resolve the subsystem identified by the passed name.
     * Returns an instance of the usersource identified by its classname.
     * The classname is build by the schema class_usersources_source_$strName
     *
     * @param string $strName
     *
     * @throws Exception
     * @return UsersourcesUsersourceInterface or null if not existing, an exception is raised, too.
     */
    public function getUsersource($strName)
    {
        $strName = trim($strName);
        if ($strName == "") {
            throw new Exception("no login provider given", Exception::$level_ERROR);
        }

        $strFilename = "UsersourcesSource".ucfirst($strName).".php";
        $strFilename = Resourceloader::getInstance()->getPathForFile("/system/usersources/".$strFilename);

        if ($strFilename == null) {
            throw new Exception("login provider ".$strName." not existing", Exception::$level_ERROR);
        }

        return Classloader::getInstance()->getInstanceFromFilename($strFilename, '', 'Kajona\System\System\Usersources\UsersourcesUsersourceInterface');

    }

    /**
     * Returns an array of all user-subsystem-identifiers available.
     *
     * @return string[]
     */
    public function getArrUsersources()
    {
        return $this->arrSubsystemsAvailable;
    }

}

