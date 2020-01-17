<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;


/**
 * Model for a user-login-log
 *
 * @package module_user
 * @author sidler@mulchprod.de
 *
 * @module user
 * @moduleId _user_modul_id_
 */
class UserLog
{


    /**
     * Generates a login-log-entry
     *
     * @param int $intStatus
     * @param string $strOtherUsername
     *
     * @return bool
     * @static
     */
    public static function generateLog($intStatus = 1, $strOtherUsername = "")
    {

        $arrParams = array();

        $strQuery = "INSERT INTO agp_user_log
						(user_log_id, user_log_userid, user_log_date, user_log_status, user_log_ip, user_log_sessid) VALUES
						(?, ?, ?, ?, ?, ?)";

        $arrParams[] = generateSystemid();

        if ($strOtherUsername == "") {
            $arrParams[] = (Carrier::getInstance()->getObjSession()->getUserID() == "" ? "0" : Carrier::getInstance()->getObjSession()->getUserID());
        }
        else {
            $arrParams[] = $strOtherUsername;
        }

        $arrParams[] = Date::getCurrentTimestamp();
        $arrParams[] = (int)$intStatus;
        $arrParams[] = getServer("REMOTE_ADDR");
        $arrParams[] = Carrier::getInstance()->getObjSession()->getInternalSessionId();

        return Carrier::getInstance()->getObjDB()->_pQuery($strQuery, $arrParams);
    }

    /**
     * Updates the users' log-entry with the current logout-timestamp
     *
     * @static
     * @return bool
     */
    public static function registerLogout()
    {
        $strQuery = "UPDATE agp_user_log
                        SET user_log_enddate = ?
                      WHERE user_log_sessid = ?";

        return Carrier::getInstance()->getObjDB()->getInstance()->_pQuery(
            $strQuery,
            array(Date::getCurrentTimestamp(), Carrier::getInstance()->getObjSession()->getInternalSessionId())
        );
    }

    /**
     * Returns all login-logs as an array
     *
     * @param null $intStart
     * @param null $intEnd
     *
     * @return mixed
     * @static
     */
    public static function getLoginLogs($intStart = null, $intEnd = null)
    {
        $strQuery = "SELECT *
				       FROM agp_user_log
			      LEFT JOIN agp_user
						ON user_log_userid = user_id
				   ORDER BY user_log_date DESC";
        return Carrier::getInstance()->getObjDB()->getPArray($strQuery, array(), $intStart, $intEnd);
    }

    /**
     * Returns the number of logins written to the log
     *
     * @param Date $objStartDate
     * @param Date $objEndDate
     *
     * @return int
     */
    public function getLoginLogsCount(Date $objStartDate = null, Date $objEndDate = null)
    {
        $strQuery = "SELECT COUNT(*) AS cnt
						FROM agp_user_log as log";

        $arrParams = array();
        if ($objStartDate !== null && $objEndDate !== null) {
            $strQuery .= " WHERE log.user_log_date >= ? AND log.user_log_date <= ?";
            $arrParams[] = $objStartDate->getLongTimestamp();
            $arrParams[] = $objEndDate->getLongTimestamp();
        }

        $arrRow = Carrier::getInstance()->getObjDB()->getPRow($strQuery, $arrParams);

        return $arrRow["cnt"];
    }

    /**
     * Removes entries from the login protocol oder then the passed number of days
     * @param Date $oldestDate
     * @return int the number of deleted entries
     */
    public function cleanLog(Date $oldestDate): int
    {
        $query = 'DELETE FROM agp_user_log WHERE user_log_date < ?';
        $database = Carrier::getInstance()->getObjDB();
        $database->_pQuery($query, [$oldestDate->getLongTimestamp()]);
        return $database->getIntAffectedRows();
    }

}
