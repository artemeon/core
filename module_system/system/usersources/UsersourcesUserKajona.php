<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                           *
********************************************************************************************************/

namespace Kajona\System\System\Usersources;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\Date;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Logger;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\SystemEventidentifier;
use Kajona\System\System\SystemPwHistory;


/**
 * Model representing an user within the classical kajona subsystem.
 * Since kajona-users are NOT reflected in the system-table, the classical systemid is not available.
 * Relevant methods have to be reimplemented to reflect this change.
 *
 * @author sidler@mulchprod.de
 * @since 3.4.1
 * @package module_usersource
 *
 * @module user
 * @moduleId _user_modul_id_
 */
class UsersourcesUserKajona extends Model implements ModelInterface, UsersourcesUserInterface
{

    /**
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryPassword
     */
    private $strPass = "";

    /**
     * For form-faking only!
     *
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryPassword
     */
    private $strPass2 = "";

    /**
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     * @fieldValidator Kajona\System\System\Validators\EmailValidator
     * @fieldMandatory
     */
    private $strEmail = "";

    /**
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     */
    private $strForename = "";

    /**
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     */
    private $strName = "";

    /**
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     */
    private $strStreet = "";

    /**
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     */
    private $strPostal = "";

    /**
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     */
    private $strCity = "";

    /**
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     */
    private $strTel = "";

    /**
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     */
    private $strMobile = "";

    /**
     * @var int
     * @fieldType Kajona\System\Admin\Formentries\FormentryDate
     * @fieldLabel form_user_date
     */
    private $longDate = 0;

    private $strSalt = "";

    /**
     * The immutable password from the database.
     * $strPass is not published with the information from the database, otherwise it would be
     * overwritten.
     *
     * @var string
     */
    private $strFinalPass = "";

    /**
     * @var string
     */
    private $strSpecialConfig = "";


    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     */
    public function getStrDisplayName()
    {
        return $this->getStrName();
    }


    /**
     * Initialises the current object, if a systemid was given
     */
    protected function initObjectInternal()
    {
        $strQuery = "SELECT * FROM ".$this->objDB->dbsafeString("agp_user_kajona")." WHERE user_id=?";
        $arrRow = $this->objDB->getPRow($strQuery, array($this->getSystemid()));

        if (count($arrRow) > 0) {

            $this->setStrEmail($arrRow["user_email"]);
            $this->setStrForename($arrRow["user_forename"]);
            $this->setStrName($arrRow["user_name"]);
            $this->setStrStreet($arrRow["user_street"]);
            $this->setStrPostal($arrRow["user_postal"]);
            $this->setStrCity($arrRow["user_city"]);
            $this->setStrTel($arrRow["user_tel"]);
            $this->setStrMobile($arrRow["user_mobile"]);
            $this->setLongDate($arrRow["user_date"]);
            $this->setSystemid($arrRow["user_id"]);
            $this->setStrSalt($arrRow["user_salt"]);
            $this->setStrSpecialConfig($arrRow["user_specialconfig"]);

            $this->strFinalPass = $arrRow["user_pass"];
        }
    }

    /**
     * Passes a new system-id to the object.
     * This id has to be used for newly created objects,
     * otherwise the mapping of kajona-users to users in the
     * subsystem may fail.
     *
     * @param string $strId
     *
     * @return void
     */
    public function setNewRecordId($strId)
    {
        $strQuery = "UPDATE agp_user_kajona SET user_id = ? WHERE user_id = ?";
        $this->objDB->_pQuery($strQuery, array($strId, $this->getSystemid()));
        $this->setSystemid($strId);
    }

    /**
     * Updates the current object to the database
     * <b>ATTENTION</b> If you don't want to update the password, set it to "" before!
     *
     * @param bool $strPrevId
     *
     * @return bool
     */
    public function updateObjectToDb($strPrevId = false)
    {

        if ($this->getSystemid() == "") {
            $strUserid = generateSystemid();
            $this->setSystemid($strUserid);
            $strQuery = "INSERT INTO agp_user_kajona (
                        user_specialconfig, user_id,
                        user_pass, user_email, user_forename,
                        user_name, 	user_street,
                        user_postal, user_city,
                        user_tel, user_mobile,
                        user_date, user_salt

                        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";

            Logger::getInstance(Logger::USERSOURCES)->info("new kajona user: ".$this->getStrEmail());

            $bitReturn = $this->objDB->_pQuery($strQuery, array(
                $this->getStrSpecialConfig(),
                $strUserid,
                $this->getStrPass(),
                $this->getStrEmail(),
                $this->getStrForename(),
                $this->getStrName(),
                $this->getStrStreet(),
                $this->getStrPostal(),
                $this->getStrCity(),
                $this->getStrTel(),
                $this->getStrMobile(),
                $this->getLongDate(),
                $this->getStrSalt()
            ), array(false));
        }
        else {

            $strQuery = "";
            $arrParams = array();

            if ($this->getStrPass() != "") {
                $strQuery = "UPDATE agp_user_kajona SET user_specialconfig=?, 
                        user_pass=?, user_email=?, user_forename=?, user_name=?, user_street=?, user_postal=?, user_city=?, user_tel=?, user_mobile=?,
                        user_date=?, user_salt=?  WHERE user_id = ?";
                $arrParams = array(
                    $this->getStrSpecialConfig(), $this->getStrPass(),
                    $this->getStrEmail(), $this->getStrForename(), $this->getStrName(), $this->getStrStreet(), $this->getStrPostal(),
                    $this->getStrCity(), $this->getStrTel(), $this->getStrMobile(), $this->getLongDate(), $this->getStrSalt(),  $this->getSystemid()
                );

            } else {
                $strQuery = "UPDATE agp_user_kajona SET user_specialconfig=?,
                        user_email=?, user_forename=?, user_name=?, user_street=?, user_postal=?, user_city=?, user_tel=?, user_mobile=?,
                        user_date=?, user_salt=? WHERE user_id = ?";

                $arrParams = array(
                    $this->getStrSpecialConfig(), $this->getStrEmail(), $this->getStrForename(), $this->getStrName(), $this->getStrStreet(), $this->getStrPostal(),
                    $this->getStrCity(), $this->getStrTel(), $this->getStrMobile(), $this->getLongDate(), $this->getStrSalt(), $this->getSystemid()
                );

            }

            Logger::getInstance(Logger::USERSOURCES)->info("updated user ".$this->getStrEmail());

            $bitReturn = $this->objDB->_pQuery($strQuery, $arrParams, array(false));
        }

        $strPass = $this->getStrPass();
        if ($bitReturn && !empty($strPass)) {
            $objHistory = new SystemPwHistory();
            $objHistory->setStrTargetUser($this->getSystemid());
            $objHistory->setStrPass($strPass);
            $objHistory->setStrChangeDate(new Date());
            ServiceLifeCycleFactory::getLifeCycle(get_class($objHistory))->update($objHistory);
        }

        return $bitReturn;
    }

    /**
     * Called whenever a update-request was fired.
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
     * Deletes a user from the systems
     *
     * @return bool
     */
    public function deleteUser()
    {
        Logger::getInstance(Logger::USERSOURCES)->info("deleted user with id ".$this->getSystemid());
        $this->deleteAllUserMemberships();
        $strQuery = "DELETE FROM agp_user_kajona WHERE user_id=?";
        //call other models that may be interested
        $bitDelete = $this->objDB->_pQuery($strQuery, array($this->getSystemid()));
        CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_RECORDDELETED, array($this->getSystemid(), get_class($this)));
        return $bitDelete;
    }

    /**
     * Deletes the current object from the system
     *
     * @return bool
     */
    public function deleteObjectFromDatabase()
    {
        return $this->deleteUser();
    }

    /**
     * Deletes all memberships of the given USER from ALL groups
     *
     * @return bool
     * @static
     */
    private function deleteAllUserMemberships()
    {
        $strQuery = "DELETE FROM agp_user_kajona_members WHERE group_member_user_kajona_id=?";
        return $this->objDB->_pQuery($strQuery, array($this->getSystemid()));
    }

    /**
     * Indicates if the current users' password may be reset, e.g. via a password-forgotten mail
     *
     * @return bool
     */
    public function isPasswordResettable()
    {
        return true;
    }

    /**
     * Returns the list of group-ids the current user is assigned to
     *
     * @return array
     */
    public function getGroupIdsForUser()
    {
        $strQuery = "SELECT group_id, group_short_id
                       FROM agp_user_group,
                            agp_user_kajona_members,
                            agp_system
                      WHERE group_member_user_kajona_id= ?
                        AND group_id = group_member_group_kajona_id
                        AND group_id = system_id
                        AND system_deleted != 1
                   ORDER BY group_name ASC  ";

        $arrIds = $this->objDB->getPArray($strQuery, array($this->getSystemid()));

        $arrReturn = array();
        foreach ($arrIds as $arrOneId) {
            $arrReturn[] = $arrOneId["group_id"];
        }

        return $arrReturn;
    }

    /**
     * @inheritdoc
     */
    public function getShortGroupIdsForUser()
    {
        $strQuery = "SELECT group_id, group_short_id
                       FROM agp_user_group,
                            agp_user_kajona_members,
                            agp_system
                      WHERE group_member_user_kajona_id= ?
                        AND group_id = group_member_group_kajona_id
                        AND group_id = system_id
                        AND system_deleted != 1
                   ORDER BY group_name ASC  ";

        $arrIds = $this->objDB->getPArray($strQuery, array($this->getSystemid()));

        $arrReturn = array();
        foreach ($arrIds as $arrOneId) {
            $arrReturn[] = $arrOneId["group_short_id"];
        }

        return $arrReturn;
    }


    /**
     * Indicates if the current user is editable or read-only
     *
     * @return bool
     */
    public function isEditable()
    {
        return true;
    }

    /**
     * Hook to update the admin-form when editing / creating a single user
     *
     * @param AdminFormgenerator $objForm
     *
     * @return mixed
     */
    public function updateAdminForm(AdminFormgenerator $objForm)
    {

    }

    // --- GETTERS / SETTERS --------------------------------------------------------------------------------

    public function getStrPass()
    {
        return $this->strPass;
    }

    public function getStrPass2()
    {
        return "";
    }

    public function setStrPass2($strValue)
    {
    }

    public function getStrEmail()
    {
        return $this->strEmail;
    }

    public function getStrForename()
    {
        return $this->strForename;
    }

    public function getStrName()
    {
        return $this->strName;
    }

    /**
     * The immutable password from the database.
     *
     * @return string
     */
    public function getStrFinalPass()
    {
        return $this->strFinalPass;
    }

    public function setStrPass($strPass)
    {
        if (trim($strPass) != "") {
            $this->setStrSalt(generateSystemid());
            $this->strPass = UsersourcesSourceKajona::encryptPassword($strPass, $this->getStrSalt());
        }
    }

    public function setStrEmail($strEmail)
    {
        $this->strEmail = $strEmail;
    }

    public function setStrForename($strForename)
    {
        $this->strForename = $strForename;
    }

    public function setStrName($strName)
    {
        $this->strName = $strName;
    }

    public function getStrStreet()
    {
        return $this->strStreet;
    }

    public function setStrStreet($strStreet)
    {
        $this->strStreet = $strStreet;
    }

    public function getStrPostal()
    {
        return $this->strPostal;
    }

    public function setStrPostal($strPostal)
    {
        $this->strPostal = $strPostal;
    }

    public function getStrCity()
    {
        return $this->strCity;
    }

    public function setStrCity($strCity)
    {
        $this->strCity = $strCity;
    }

    public function getStrTel()
    {
        return $this->strTel;
    }

    public function setStrTel($strTel)
    {
        $this->strTel = $strTel;
    }

    public function getStrMobile()
    {
        return $this->strMobile;
    }

    public function setStrMobile($strMobile)
    {
        $this->strMobile = $strMobile;
    }

    public function getLongDate()
    {
        return $this->longDate;
    }

    public function setLongDate($longDate)
    {
        $this->longDate = $longDate;
    }

    public function setStrSalt($strSalt)
    {
        $this->strSalt = $strSalt;
    }

    public function getStrSalt()
    {
        return $this->strSalt;
    }

    /**
     * @return string
     */
    public function getStrSpecialConfig()
    {
        return $this->strSpecialConfig;
    }

    /**
     * @param string $strSpecialConfig
     */
    public function setStrSpecialConfig($strSpecialConfig)
    {
        $this->strSpecialConfig = $strSpecialConfig;
    }


}
