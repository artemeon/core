<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System\Usersources;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\System\Carrier;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\Logger;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\SystemChangelog;
use Kajona\System\System\SystemEventidentifier;
use Kajona\System\System\UserGroup;


/**
 * This class represents a group based on the internal authentication system.
 * Since groups are NOT reflected in the system-table, all relevant methods have to be overwritten and
 * reimplemented.
 *
 * @author sidler@mulchprod.de
 * @since 3.4.1
 * @package module_usersource
 *
 * @module user
 * @moduleId _user_modul_id_
 */
class UsersourcesGroupKajona extends Model implements ModelInterface, UsersourcesGroupInterface
{

    /**
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryTextarea
     */
    private $strDesc = "";


    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     */
    public function getStrDisplayName()
    {
        return $this->getStrDesc();
    }


    /**
     * Initialises the current object, if a systemid was given
     *
     * @return void
     */
    protected function initObjectInternal()
    {
        $strQuery = "SELECT * FROM agp_user_group_kajona WHERE group_id=?";
        $arrRow = $this->objDB->getPRow($strQuery, array($this->getSystemid()));

        if (count($arrRow) > 0) {
            $this->setStrDesc($arrRow["group_desc"]);
            $this->setSystemid($arrRow["group_id"]);
        }
    }

    /**
     * Updates the current object to the database.
     * Overwrites Roots' logic since a kajona group is not reflected in the system-table
     *
     * @param bool $strPrevId
     *
     * @return bool
     */
    public function updateObjectToDb($strPrevId = false)
    {
        //mode-splitting
        if ($this->getSystemid() == "") {
            Logger::getInstance(Logger::USERSOURCES)->info("saved new kajona group ".$this->getStrSystemid());
            $strGrId = generateSystemid();
            $this->setSystemid($strGrId);
            $strQuery = "INSERT INTO agp_user_group_kajona
                          (group_id, group_desc) VALUES
                          (?, ?)";
            return $this->objDB->_pQuery($strQuery, array($strGrId, $this->getStrDesc()));
        }
        else {
            Logger::getInstance(Logger::USERSOURCES)->info("updated kajona group ".$this->getSystemid());
            $strQuery = "UPDATE agp_user_group_kajona
                            SET group_desc=?
                          WHERE group_id=?";
            return $this->objDB->_pQuery($strQuery, array($this->getStrDesc(), $this->getSystemid()));
        }
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
        $strQuery = "UPDATE agp_user_group_kajona SET group_id = ? WHERE group_id = ?";
        $this->objDB->_pQuery($strQuery, array($strId, $this->getSystemid()));
        $this->setSystemid($strId);
    }

    /**
     * Returns an array of user-ids associated with the current group.
     * If possible, pageing should be supported
     *
     * @param int $intStart
     * @param int $intEnd
     *
     * @param bool $ignoreCache
     * @return array
     */
    public function getUserIdsForGroup($intStart = null, $intEnd = null, bool $ignoreCache = false)
    {
        $strQuery = "SELECT user2.user_id FROM 
                                         agp_user as user2,
									     agp_user_kajona_members,
									     agp_system 
								   WHERE group_member_group_kajona_id= ?
								  	 AND user2.user_id = group_member_user_kajona_id
                                     AND user2.user_id = system_id
					                 AND system_deleted = 0
                                ORDER BY user2.user_username ASC  ";

        $arrIds = $this->objDB->getPArray($strQuery, array($this->getSystemid()), $intStart, $intEnd, !$ignoreCache);

        $arrReturn = array();
        foreach ($arrIds as $arrOneId) {
            $arrReturn[] = $arrOneId["user_id"];
        }

        return $arrReturn;
    }

    /**
     * Returns the number of members of the current group.
     *
     * @return int
     */
    public function getNumberOfMembers()
    {
        $strQuery = "SELECT COUNT(*) AS cnt
                       FROM agp_user_kajona_members,
                            agp_system 
					   WHERE group_member_group_kajona_id= ?
					     AND group_member_user_kajona_id = system_id
					     AND system_deleted = 0";
        $arrRow = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($this->getSystemid()));
        return $arrRow["cnt"];
    }

    /**
     * Deletes the given group
     *
     * @return bool
     */
    public function deleteGroup()
    {
        Logger::getInstance(Logger::USERSOURCES)->info("deleted kajona group with id ".$this->getSystemid());
        $this->deleteAllUsersFromCurrentGroup();
        $strQuery = "DELETE FROM agp_user_group_kajona WHERE group_id=?";
        CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_RECORDDELETED, array($this->getSystemid(), get_class($this)));
        return $this->objDB->_pQuery($strQuery, array($this->getSystemid()));
    }

    /**
     * Deletes the current object from the system
     *
     * @return bool
     */
    public function deleteObject()
    {
        return $this->deleteGroup();
    }


    /**
     * Deletes all users from the current group
     *
     * @return bool
     */
    private function deleteAllUsersFromCurrentGroup()
    {
        $strQuery = "DELETE FROM agp_user_kajona_members WHERE group_member_group_kajona_id=?";
        return $this->objDB->_pQuery($strQuery, array($this->getSystemid()));
    }

    /**
     * Adds a new member to the group - if possible
     *
     * @param UsersourcesUserInterface $objUser
     *
     * @return bool
     */
    public function addMember(UsersourcesUserInterface $objUser)
    {
        if (!validateSystemid($objUser->getSystemid())) {
            return false;
        }

        $objLog = new SystemChangelog();
        $objLog->processChanges(Objectfactory::getInstance()->getObject($this->getSystemid()), "editMemberships", [["property" => UserGroup::STR_ASSIGNMENT_PROPERTY, "oldvalue" => '', "newvalue" => $objUser->getSystemid()]]);

        return $this->objDB->insertOrUpdate("agp_user_kajona_members", ["group_member_group_kajona_id", "group_member_user_kajona_id"], [$this->getSystemid(), $objUser->getSystemid()], ["group_member_group_kajona_id", "group_member_user_kajona_id"]);
    }


    /**
     * Defines whether the current group-properties (e.g. the name) may be edited or is read-only
     *
     * @return bool
     */
    public function isEditable()
    {
        return true;
    }


    private $bitBlockRemoveCl = false;
    /**
     * Removes a member from the current group - if possible.
     *
     * @param UsersourcesUserInterface $objUser
     *
     * @return bool
     */
    public function removeMember(UsersourcesUserInterface $objUser)
    {
        //add a changelog entry
        if (!$this->bitBlockRemoveCl) {
            $objLog = new SystemChangelog();
            $objLog->processChanges(Objectfactory::getInstance()->getObject($this->getSystemid()), "editMemberships", [["property" => UserGroup::STR_ASSIGNMENT_PROPERTY, "oldvalue" => $objUser->getSystemid(), "newvalue" => '']]);
        }

        $strQuery = "DELETE FROM agp_user_kajona_members
						WHERE group_member_group_kajona_id=?
						  AND group_member_user_kajona_id=?";
        return $this->objDB->_pQuery($strQuery, array($this->getSystemid(), $objUser->getSystemid()));
    }

    /**
     * Hook to update the admin-form when editing / creating a single group
     *
     * @param AdminFormgenerator $objForm
     *
     * @return mixed
     */
    public function updateAdminForm(AdminFormgenerator $objForm)
    {

    }

    /**
     * @return string
     */
    public function getStrDesc()
    {
        return $this->strDesc;
    }

    /**
     * @param string $strDesc
     *
     * @return void
     */
    public function setStrDesc($strDesc)
    {
        $this->strDesc = $strDesc;
    }

}
