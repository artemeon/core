<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                            *
********************************************************************************************************/

namespace Kajona\Ldap\System\Usersources;

use Kajona\Ldap\System\Ldap;
use Kajona\Ldap\System\LdapAuthenticatorInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Config;
use Kajona\System\System\Database;
use Kajona\System\System\Exception;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Logger;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Usersources\UsersourcesGroupInterface;
use Kajona\System\System\Usersources\UsersourcesUserInterface;
use Kajona\System\System\Usersources\UsersourcesUsersourceInterface;
use Kajona\System\System\UserUser;


/**
 * Global entry into the ldap-subsystem.
 * Mapps all calls and redirects them to the directory-services.
 * Since 4.8, the class is able to handle various ldap connections
 *
 * @author sidler@mulchprod.de
 * @since 3.4.1
 * @package module_ldap
 */
class UsersourcesSourceLdap implements UsersourcesUsersourceInterface
{

    private $objDB;


    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->objDB = Carrier::getInstance()->getObjDB();
    }

    /**
     * Returns a readable name of the source, e.g. "Kajona" or "LDAP Company 1"
     *
     * @return mixed
     */
    public function getStrReadableName()
    {
        return Carrier::getInstance()->getObjLang()->getLang("usersource_ldap_name", "ldap");
    }

    /**
     * Tries to authenticate a user with the given credentials.
     * The password is unencrypted, each source should take care of its own encryption.
     *
     * @param UsersourcesUserInterface|UsersourcesUserLdap $objUser
     * @param string $strPassword
     *
     * @return bool
     */
    public function authenticateUser(UsersourcesUserInterface $objUser, $strPassword)
    {
        /** @var LdapAuthenticatorInterface $userNameGenerator */
        $ldapAuthenticate = Carrier::getInstance()->getContainer()->offsetGet(\Kajona\Ldap\System\ServiceProvider::STR_LDAP_AUTHENTICATOR);
        return $ldapAuthenticate->authenticateUser($objUser, $strPassword);
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
        return false;
    }

    /**
     * @return bool
     */
    public function getMembersEditable()
    {
        return false;
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
        $strQuery = "SELECT group_id FROM agp_user_group WHERE group_id = ? AND group_subsystem = 'ldap'";

        $arrIds = $this->objDB->getPRow($strQuery, array($strId));
        if (isset($arrIds["group_id"]) && validateSystemid($arrIds["group_id"])) {
            return new UsersourcesGroupLdap($arrIds["group_id"]);
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
        return new UsersourcesGroupLdap();
    }

    /**
     * Returns an empty user, e.g. to fetch the fields available and
     * in order to fill a new one.
     *
     * @return UsersourcesUserInterface
     */
    public function getNewUser()
    {
        return new UsersourcesUserLdap();
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
        if ($bitIgnoreDeletedFlag) {
            $strQuery = "SELECT user_id FROM agp_user, agp_system WHERE user_id = system_id AND user_id = ? AND user_subsystem = 'ldap'";
        } else {
            $strQuery = "SELECT user_id FROM agp_user, agp_system WHERE user_id = system_id AND user_id = ? AND user_subsystem = 'ldap' AND (system_deleted = 0 OR system_deleted IS NULL)";
        }

        $arrIds = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strId));
        if (isset($arrIds["user_id"]) && validateSystemid($arrIds["user_id"])) {
            return new UsersourcesUserLdap($arrIds["user_id"]);
        }

        return null;
    }

    /**
     * Loads the user identified by the passed dn
     *
     * @param string $strUserDn
     *
     * @return UsersourcesUserInterface or null
     * @deprecated
     */
    public function getUserByDn($strUserDn)
    {
        $strQuery = "SELECT user_ldap_id FROM agp_user_ldap WHERE user_ldap_dn = ?";

        $arrIds = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strUserDn));
        if (isset($arrIds["user_ldap_id"]) && validateSystemid($arrIds["user_ldap_id"])) {
            return new UsersourcesUserLdap($arrIds["user_ldap_id"]);
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
     * @throws \Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException
     */
    public function getUserByUsername($strUsername)
    {
        $strQuery = "SELECT user_id FROM agp_user, agp_system WHERE user_id = system_id AND user_username = ? AND user_subsystem = 'ldap' AND (system_deleted = 0 OR system_deleted IS NULL)";

        $arrIds = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strUsername));
        if (isset($arrIds["user_id"]) && validateSystemid($arrIds["user_id"])) {
            return new UsersourcesUserLdap($arrIds["user_id"]);
        }

        //user not found. search for a matching user in the ldap and add a possible match to the system
        foreach (Ldap::getAllInstances() as $objSingleLdap) {
            $arrDetails = $objSingleLdap->getUserdetailsByName($strUsername);

            if ($arrDetails !== false && count($arrDetails) == 1) {
                $arrSingleUser = $arrDetails[0];

                //validate if relevant
                if (!$this->validateUserHasGroupAssignments($arrSingleUser["identifier"], $objSingleLdap->getIntCfgNr())) {
                    continue;
                }

                $objUser = new UserUser();
                $objUser->setStrUsername($strUsername);
                $objUser->setStrSubsystem("ldap");
                $objUser->setIntAdmin(1);
                ServiceLifeCycleFactory::getLifeCycle(get_class($objUser))->update($objUser);

                /** @var $objSourceUser UsersourcesUserLdap */
                $objSourceUser = $objUser->getObjSourceUser();
                if ($objSourceUser instanceof UsersourcesUserLdap) {
                    $objSourceUser->setStrDN($arrSingleUser["identifier"]);
                    $objSourceUser->setStrFamilyname($arrSingleUser["familyname"]);
                    $objSourceUser->setStrGivenname($arrSingleUser["givenname"]);
                    $objSourceUser->setStrEmail($arrSingleUser["mail"]);
                    $objSourceUser->setIntCfg($objSingleLdap->getIntCfgNr());
                    ServiceLifeCycleFactory::getLifeCycle(get_class($objSourceUser))->update($objSourceUser);

                    $this->objDB->flushQueryCache();
                    return $objSourceUser;
                }

            }
        }

        return null;
    }

    /**
     * If required by the current connection config, the assignment of groups to the current user is being validated.
     * True if either not required or if the number of assignments is > 0
     * @param String $dn
     * @param int $ldapCfg
     * @return bool
     * @throws Exception
     */
    private function validateUserHasGroupAssignments(String $dn, int $ldapCfg)
    {
        $cfg = Config::getInstance('module_ldap', 'config.php')->getConfig($ldapCfg);
        $required = $cfg['ldap_group_assignment_required'] ?? false;

        if (!$required) {
            return true;
        }

        $user = new UsersourcesUserLdap();
        $user->setStrDN($dn);
        $user->setIntCfg($ldapCfg);

        $groupIds = $user->getGroupIdsForUser();
        return count($groupIds) > 0;
    }


    /**
     * @inheritdoc
     * @param $strUsername
     * @param int $intMax
     * @return UserUser[]|void
     * @throws Exception
     * @throws \Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException
     */
    public function searchUser($strUsername, $intMax = 10)
    {
        $connection = Database::getInstance();

        $strQuery = "SELECT user_tbl.user_id
                      FROM agp_system, agp_user AS user_tbl
                      JOIN agp_user_ldap AS user_ldap ON user_tbl.user_id = user_ldap.user_ldap_id
                      WHERE
                          (
                          user_tbl.user_username LIKE ? 
                          OR user_ldap.user_ldap_givenname LIKE ? 
                          OR user_ldap.user_ldap_familyname LIKE ? 
                          OR ".$connection->getConcatExpression(['user_ldap.user_ldap_givenname', '\' \'', 'user_ldap.user_ldap_familyname'])." LIKE ?
                          OR ".$connection->getConcatExpression(['user_ldap.user_ldap_familyname', '\' \'', 'user_ldap.user_ldap_givenname'])." LIKE ?
                          OR ".$connection->getConcatExpression(['user_ldap.user_ldap_familyname', '\', \'', 'user_ldap.user_ldap_givenname'])." LIKE ?                  
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

        if (count($arrReturn) < $intMax) {
            foreach (Ldap::getAllInstances() as $objSingleLdap) {
                $arrDetails = $objSingleLdap->searchUserByWildcard($strUsername);
                if ($arrDetails === false) {
                    continue;
                }

                foreach ($arrDetails as $arrSingleUser) {
                    //transparent user creation if not already existing
                    $user = $this->getUserByUsername($arrSingleUser["username"]);
                    if ($user instanceof UsersourcesUserLdap) {
                        $existingUser = Objectfactory::getInstance()->getObject($user->getSystemid());
                        $arrReturn[] = $existingUser;
                    }
                }
            }
        }

        return $arrReturn;
    }


    /**
     * @inheritdoc
     */
    public function getAllGroupIds($bitIgnoreSystemGroups = false)
    {
        $strQuery = "SELECT group_id
                       FROM agp_user_group_ldap,
                            agp_user_group
                      WHERE group_id = group_ldap_id
                      ".($bitIgnoreSystemGroups ? " AND group_system_group != 1 " : "")."
                      ORDER BY group_name";
        $arrRows = Carrier::getInstance()->getObjDB()->getPArray($strQuery, array());
        $arrReturn = array();
        foreach ($arrRows as $arrOneRow) {
            $arrReturn[] = $arrOneRow["group_id"];
        }

        return $arrReturn;
    }

    /**
     * Returns an array of user-ids provided by the current source.
     *
     * @return string[]
     */
    public function getAllUserIds()
    {
        $strQuery = "SELECT user_id
                       FROM agp_user_ldap,
                            agp_user,
                            agp_system
                      WHERE user_id = user_ldap_id
                        AND user_id = system_id
                        AND (system_deleted = 0 OR system_deleted IS NULL)
                      ORDER BY user_username";
        $arrRows = Carrier::getInstance()->getObjDB()->getPArray($strQuery, array());
        $arrReturn = array();
        foreach ($arrRows as $arrOneRow) {
            $arrReturn[] = $arrOneRow["user_id"];
        }

        return $arrReturn;
    }


    /**
     * Updates all user-data stored in the system.
     * This may be a long-running task, so execute this only explicitly
     * and not during common requests!
     *
     * @return bool
     * @throws \Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException
     * @throws \Kajona\System\System\Lifecycle\ServiceLifeCycleLogicDeleteException
     */
    public function updateUserData()
    {
        //sync may take time -> increase the time available
        if (@ini_get("max_execution_time") < 500 && @ini_get("max_execution_time") > 0) {
            @ini_set("max_execution_time", "500");
        }

        //fill all groups - loads new members from the ldap system
        $arrGroups = $this->getAllGroupIds();

        $arrUserIds = array();

        foreach ($arrGroups as $strSingleGroupId) {
            $objGroup = new UsersourcesGroupLdap($strSingleGroupId);
            $arrUserIds = array_merge($arrUserIds, $objGroup->getUserIdsForGroup());
        }

        $arrUserIds = array_unique($arrUserIds);

        //parse all users
        $arrUsers = $this->getAllUserIds();
        foreach ($arrUsers as $strOneUserId) {
            $objUser = new UserUser($strOneUserId);
            /** @var $objSourceUser UsersourcesUserLdap */
            $objSourceUser = $objUser->getObjSourceUser();
            $arrUserDetails = false;
            try {
                $arrUserDetails = Ldap::getInstance($objSourceUser->getIntCfg())->getUserdetailsByName($objUser->getStrUsername());
                if ($arrUserDetails != false && isset($arrUserDetails[0])) {
                    $arrUserDetails = $arrUserDetails[0];
                }
            } catch (Exception $objEx) {
            }
            if ($arrUserDetails !== false && in_array($strOneUserId, $arrUserIds)) {
                $objSourceUser->setStrDN($arrUserDetails["identifier"]);
                $objSourceUser->setStrFamilyname($arrUserDetails["familyname"]);
                $objSourceUser->setStrGivenname($arrUserDetails["givenname"]);
                $objSourceUser->setStrEmail($arrUserDetails["mail"]);
                ServiceLifeCycleFactory::getLifeCycle(get_class($objSourceUser))->update($objSourceUser);

                $this->objDB->flushQueryCache();
            } else {
                //user seems to be deleted, remove from system, if not in any kajona group too (mixed groups allowed from 7.0)
                $userGroupsIds = $objUser->getArrGroupIds();
                if (count(array_diff($userGroupsIds, $arrGroups)) == 0) {
                    ServiceLifeCycleFactory::getLifeCycle(get_class($objUser))->delete($objUser);
                    Logger::getInstance("ldapsync.log")->warning("Deleting user ".$strOneUserId." / ".$objUser->getStrUsername()." @ ".$objSourceUser->getStrDN());
                }
            }
        }

        return true;
    }

}
