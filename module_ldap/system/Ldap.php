<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                               *
********************************************************************************************************/

namespace Kajona\Ldap\System;

use Kajona\System\System\Config;
use Kajona\System\System\Exception;
use Kajona\System\System\Logger;
use Kajona\System\System\StringUtil;


/**
 * The Ldap acts as a ldap-connector and is used by the usersources-subsystem as a login-provider.
 * It is configured by the config.php file located at /system/config.
 * Please refer to this file in order to see how source-systes may be connected.
 *
 * @package module_ldap
 * @author sidler@mulchprod.de
 * @since 3.4.1
 * @see /system/config/config.php
 */
class Ldap
{

    /**
     * @var array
     */
    private $arrConfig;

    /**
     * @var Resource
     */
    private $objCx = null;

    /**
     * @var Ldap[]
     */
    private static $arrInstances = null;

    /**
     * Constructor
     *
     * @param int $intConfigNumber
     */
    private function __construct($intConfigNumber = 0)
    {
        $this->arrConfig = Config::getInstance("module_ldap")->getConfig($intConfigNumber);
        $this->arrConfig["nr"] = $intConfigNumber;

        $this->connect();
    }

    public function __destruct()
    {
        ldap_close($this->objCx);
    }


    /**
     * Connects to the ldap-server.
     * If no connection is possible, an exception is thrown.
     */
    private function connect()
    {
        if ($this->objCx == null) {
            $this->objCx = ldap_connect($this->arrConfig["ldap_server"], $this->arrConfig["ldap_port"]);

            //set options to avoid errors with references on top-level
            ldap_set_option($this->objCx, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($this->objCx, LDAP_OPT_PROTOCOL_VERSION, 3);

            Logger::getInstance(Logger::USERSOURCES)->info("new ldap-connection to " . $this->arrConfig["ldap_server"] . ":" . $this->arrConfig["ldap_port"]);

            $this->internalBind();
        }
    }

    /**
     * Returns an instance of Ldap, the connection is setup on first call.
     *
     * @param int $intConfigNumber
     *
     * @return Ldap
     */
    public static function getInstance($intConfigNumber = 0)
    {
        self::getAllInstances();
        return self::$arrInstances[$intConfigNumber];
    }

    /**
     * Returns instances for each configured ldap source
     *
     * @return Ldap[]
     */
    public static function getAllInstances()
    {
        if (self::$arrInstances == null) {
            $intI = 0;
            while (is_array(Config::getInstance("module_ldap")->getConfig($intI))) {
                self::$arrInstances[$intI] = new Ldap($intI);
                $intI++;
            }
        }

        return self::$arrInstances;
    }

    /**
     * Authenticates an user against the current ldap-connection.
     * Please be aware that this method only tries to authenticate the user,
     * the binding is released immediately. Afterwards the credentials
     * given in the config-file are used again.
     *
     * @param string $strUsername the dn
     * @param string $strPassword
     * @param string $strContext
     *
     * @return bool
     */
    public function authenticateUser($strUsername, $strPassword)
    {
        Logger::getInstance(Logger::USERSOURCES)->info("ldap authenticate user " . $strUsername);
        $bitBind = @ldap_bind($this->objCx, $strUsername, $strPassword);
        $this->internalBind();

        return $bitBind;
    }

    /**
     * Tries to bind to the ldap-server.
     * If no binding is possible, an exception is thrown.
     *
     * @throws Exception
     */
    private function internalBind()
    {
        $bitBind = false;
        if ($this->arrConfig["ldap_bind_anonymous"] === true) {
            $bitBind = @ldap_bind($this->objCx);
        } else {
            $bitBind = @ldap_bind(
                $this->objCx,
                $this->arrConfig["ldap_bind_username"],
                $this->arrConfig["ldap_bind_userpwd"]
            );
        }

        if ($bitBind === false) {
            throw new Exception("ldap bind failed: " . ldap_errno($this->objCx) . " # " . ldap_error($this->objCx), Exception::$level_FATALERROR);
        } else {
            Logger::getInstance(Logger::USERSOURCES)->info("ldap bind succeeded: " . $this->arrConfig["ldap_server"] . ":" . $this->arrConfig["ldap_port"]);
        }
    }

    /**
     * Loads all members of the passed group-identifier.
     * This list may not be limited to users, all members are returned.
     *
     * @param string $strGroupDN
     *
     * @throws Exception
     * @return string[] array of distinguished names
     */
    public function getMembersOfGroup($strGroupDN)
    {
        $arrReturn = array();

        //search the group itself
        $objResult = @ldap_search($this->objCx, $strGroupDN, $this->arrConfig["ldap_group_filter"]);

        if ($objResult !== false) {
            Logger::getInstance(Logger::USERSOURCES)->info("ldap-search found " . ldap_count_entries($this->objCx, $objResult) . " entries");

            $arrResult = @ldap_first_entry($this->objCx, $objResult);
            while ($arrResult !== false) {
                $arrValues = @ldap_get_values($this->objCx, $arrResult, $this->arrConfig["ldap_group_attribute_member"]);
                if (!empty($arrValues)) {
                    foreach ($arrValues as $strKey => $strSingleValue) {
                        if ($strKey !== "count") {
                            $arrReturn[] = $strSingleValue;
                        }
                    }
                }

                $arrResult = @ldap_next_entry($this->objCx, $arrResult);
            }
        } else {
            throw new Exception("loading of group members failed: " . ldap_errno($this->objCx) . " # " . ldap_error($this->objCx));
        }

        return $arrReturn;
    }

    /**
     * Counts the number of group-members
     * This list may not be limited to users, all members are returned as defined by the filter
     *
     * @param string $strGroupDN
     *
     * @throws Exception
     * @return int
     */
    public function getNumberOfGroupMembers($strGroupDN)
    {
        //search the group itself
        $objResult = @ldap_search($this->objCx, $strGroupDN, $this->arrConfig["ldap_group_filter"]);

        if ($objResult !== false) {
            Logger::getInstance(Logger::USERSOURCES)->info("ldap-search found " . ldap_count_entries($this->objCx, $objResult) . " entries");
            $arrResult = @ldap_first_entry($this->objCx, $objResult);
            if ($arrResult !== false) {
                $arrValues = @ldap_get_values($this->objCx, $arrResult, $this->arrConfig["ldap_group_attribute_member"]);
                return $arrValues["count"];
            }
        } else {
            throw new Exception("loading of number of group failed: " . ldap_errno($this->objCx) . " # " . ldap_error($this->objCx));
        }
        return -1;
    }

    /**
     * Validates if a single user is member of a given group
     *
     * @param string $strUserDN
     * @param string $strGroupDN
     *
     * @throws Exception
     * @return boolean
     */
    public function isUserMemberOfGroup($strUserDN, $strGroupDN)
    {
        if (empty($strUserDN) || empty($strGroupDN)) {
            return false;
        }

        //search the group itself
        $strQuery = $this->arrConfig["ldap_group_isUserMemberOf"];
        //double encode backslashes
        $strUserDN = StringUtil::replace("\\,", "\\\\,", $strUserDN);
        $strQuery = StringUtil::replace("?", $strUserDN, $strQuery);
        $objResult = @ldap_search($this->objCx, $strGroupDN, $strQuery);

        if ($objResult !== false) {
            $intCount = ldap_count_entries($this->objCx, $objResult);
            if ($intCount == 1) {
                $bitReturn = true;
            } else {
                $bitReturn = false;
            }

        } else {
            throw new Exception("loading of group-memberships failed: " . ldap_errno($this->objCx) . " # " . ldap_error($this->objCx));
        }

        return $bitReturn;
    }

    /**
     * Useful to trigger a manual search query
     *
     * @param $strBaseDn
     * @param $strQuery
     *
     * @param $arrReturnValues
     *
     * @return array
     * @throws Exception
     */
    public function customSearch($strBaseDn, $strQuery, $arrReturnValues)
    {
        $objResult = @ldap_search($this->objCx, $strBaseDn, $strQuery);
        if ($objResult !== false) {
            $arrResult = @ldap_first_entry($this->objCx, $objResult);

            $arrReturn = array();
            while ($arrResult !== false) {
                $arrRow = array();
                foreach ($arrReturnValues as $strOneAttribute) {
                    $arrRow[$strOneAttribute] = $this->getStrAttribute($arrResult, $strOneAttribute);
                }
                $arrReturn[] = $arrRow;
                $arrResult = @ldap_next_entry($this->objCx, $arrResult);
            }

            return $arrReturn;
        } else {
            throw new Exception("loading of custom search failed: " . ldap_errno($this->objCx) . " # " . ldap_error($this->objCx));
        }
    }


    /**
     * Returns an array of user-details for the user identified by the passed username.
     * Since there could be multiple hits, an array of arrays is returned
     *
     * @param string $strUsername
     *
     * @throws Exception
     * @return string array of hits, each hit an array details, false in case of errors
     */
    public function getUserDetailsByDN($strUsername)
    {
        $arrReturn = false;

        //search the group itself
        $objResult = @ldap_search($this->objCx, $strUsername, $this->arrConfig["ldap_user_filter"]);

        if ($objResult !== false) {
            $arrReturn = array();
            Logger::getInstance(Logger::USERSOURCES)->info("ldap-search found " . ldap_count_entries($this->objCx, $objResult) . " entries");

            $arrResult = @ldap_first_entry($this->objCx, $objResult);
            while ($arrResult !== false) {
                $arrReturn = array();
                $arrReturn["username"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_user_attribute_username"]);
                $arrReturn["mail"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_user_attribute_mail"]);
                if ($arrReturn["mail"] == "") {
                    $arrReturn["mail"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_user_attribute_mail_fallback"]);
                }
                $arrReturn["familyname"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_user_attribute_familyname"]);
                $arrReturn["givenname"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_user_attribute_givenname"]);
                $arrReturn["identifier"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_common_identifier"]);


                $arrResult = ldap_next_entry($this->objCx, $arrResult);
            }
        } else {
            Logger::getInstance(Logger::USERSOURCES)->error("loading of user failed: " . ldap_errno($this->objCx) . " # " . ldap_error($this->objCx) . " \n Username: " . $strUsername . " Userfilter: " . $this->arrConfig["ldap_user_filter"]);
            throw new Exception("loading of user failed: " . ldap_errno($this->objCx) . " # " . ldap_error($this->objCx));
        }

        return $arrReturn;
    }

    /**
     * Strips critical characters from user-provided values
     * @param $query
     * @return mixed
     */
    private function sanitizeUserInput($query)
    {
        return StringUtil::replace(["\\", "*", "(", ")"], ["\\\\", "", "", ""], $query);
    }

    /**
     * Searches for an user identified by the passed username.
     * The result is limited to the path set up via the config-file.
     *
     * @param string $strUsername
     *
     * @throws Exception
     * @return string array of userdetails, false in case of errors
     */
    public function getUserdetailsByName($strUsername)
    {
        //escape domain names
        $strUsername = $this->sanitizeUserInput($strUsername);
        $strUserFilter = $this->arrConfig["ldap_user_search_filter"];
        $strUserFilter = StringUtil::replace("?", $strUsername, $strUserFilter);

        return $this->triggerLdapSearch($this->arrConfig["ldap_user_base_dn"], $strUserFilter);
    }

    /**
     * @param $strPortion
     * @return array|bool
     * @throws Exception
     */
    public function searchUserByWildcard($strPortion, $intMax = 10)
    {
        //escape domain names
        $strPortion = $this->sanitizeUserInput($strPortion);
        $strUserFilter = $this->arrConfig["ldap_user_search_wildcard"];
        $strUserFilter = StringUtil::replace("?", $strPortion."*", $strUserFilter);

        return $this->triggerLdapSearch($this->arrConfig["ldap_user_base_dn"], $strUserFilter, $intMax);
    }


    /**
     * Internal helper to trigger a user-search against the ldap
     * @param $baseDN
     * @param $filter
     * @param int $maxHits
     * @return array|bool
     * @throws Exception
     */
    private function triggerLdapSearch($baseDN, $filter, $maxHits = 10)
    {
        $arrReturn = false;
        //search the group itself
        $objResult = @ldap_search($this->objCx, $baseDN, $filter);

        if ($objResult !== false) {
            Logger::getInstance(Logger::USERSOURCES)->info("ldap-search found " . ldap_count_entries($this->objCx, $objResult) . " entries");

            $arrResult = @ldap_first_entry($this->objCx, $objResult);
            while ($arrResult !== false) {
                $arrTemp = array();
                $arrTemp["username"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_user_attribute_username"]);
                $arrTemp["mail"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_user_attribute_mail"]);
                if ($arrTemp["mail"] == "") {
                    $arrTemp["mail"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_user_attribute_mail_fallback"]);
                }
                $arrTemp["familyname"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_user_attribute_familyname"]);
                $arrTemp["givenname"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_user_attribute_givenname"]);
                $arrTemp["identifier"] = $this->getStrAttribute($arrResult, $this->arrConfig["ldap_common_identifier"]);

                $arrReturn[] = $arrTemp;

                if (count($arrReturn) >= $maxHits) {
                    break;
                }

                $arrResult = ldap_next_entry($this->objCx, $arrResult);
            }
        } else {
            Logger::getInstance(Logger::USERSOURCES)->error("loading of user failed: " . ldap_errno($this->objCx) . " # " . ldap_error($this->objCx) . " \n Userfilter: " . $filter . " Base DN: " . $baseDN);
            throw new Exception("loading of user failed: " . ldap_errno($this->objCx) . " # " . ldap_error($this->objCx));
        }

        return $arrReturn;
    }

    /**
     * Loads a single attribute from a given resultset
     *
     * @param Resource $arrResult
     * @param string $strKey
     *
     * @return string
     */
    private function getStrAttribute($arrResult, $strKey)
    {
        $strReturn = "";

        $arrValues = @ldap_get_values($this->objCx, $arrResult, $strKey);
        if ($arrValues["count"] > 0) {
            $strReturn = $arrValues[0]; //no more utf-encode required since ldap v3 is utf-8 ready :)
        }

        return $strReturn;
    }

    public function getIntCfgNr()
    {
        return $this->arrConfig["nr"];
    }

    public function getStrCfgName()
    {
        return $this->arrConfig["ldap_alias"];
    }
}
