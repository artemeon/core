<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Flow\System;

use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\Database;
use Kajona\System\System\IdGenerator;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\UserGroup;

/**
 * FlowStatus
 *
 * @author christoph.kappestein@artemeon.de
 * @targetTable flow_step.step_id
 * @module flow
 * @moduleId _flow_module_id_
 * @formGenerator Kajona\Flow\Admin\FlowStatusFormgenerator
 * @sortManager Kajona\System\System\CommonSortmanager
 */
class FlowStatus extends Model implements ModelInterface, AdminListableInterface
{
    /**
     * @var string
     * @tableColumn flow_step.step_name
     * @tableColumnDatatype char254
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     * @fieldMandatory
     */
    protected $strName;

    /**
     * @var integer
     * @tableColumn flow_step.step_index
     * @tableColumnDatatype int
     */
    protected $intIndex;

    /**
     * @var string
     * @tableColumn flow_step.step_icon
     * @tableColumnDatatype char20
     * @fieldType Kajona\System\Admin\Formentries\FormentryDropdown
     * @fieldDDValues [icon_flag_black => flow_step_icon_0],[icon_flag_blue => flow_step_icon_1],[icon_flag_brown => flow_step_icon_2],[icon_flag_green => flow_step_icon_3],[icon_flag_grey => flow_step_icon_4],[icon_flag_orange => flow_step_icon_5],[icon_flag_purple => flow_step_icon_6],[icon_flag_red => flow_step_icon_7],[icon_flag_yellow => flow_step_icon_8]
     * @fieldMandatory
     */
    protected $strIcon;

    /**
     * @var UserGroup[]
     * @objectList flow_status2edit (source="status_system_id", target="usergroup_system_id", type={"Kajona\\System\\System\\UserGroup"})
     * @fieldType Kajona\System\Admin\Formentries\FormentryObjecttags
     */
    protected $arrEditGroups;

    /**
     * @return string
     */
    public function getStrName()
    {
        return $this->strName;
    }

    /**
     * @param string $strName
     */
    public function setStrName($strName)
    {
        $this->strName = $strName;
    }

    /**
     * @return int
     */
    public function getIntIndex()
    {
        return $this->intIndex;
    }

    /**
     * @param int $intIndex
     */
    public function setIntIndex($intIndex)
    {
        $this->intIndex = $intIndex;
    }

    /**
     * @return string
     */
    public function getStrIcon()
    {
        return $this->strIcon;
    }

    /**
     * @param string $strIcon
     */
    public function setStrIcon($strIcon)
    {
        $this->strIcon = $strIcon;
    }

    /**
     * @return string
     */
    public function getStrColor()
    {
        switch ($this->strIcon) {
            case 'icon_flag_black':
                return '#000000';
            case 'icon_flag_blue':
                return '#0040b3';
            case 'icon_flag_brown':
                return '#d47a0b';
            case 'icon_flag_green':
                return '#0e8500';
            case 'icon_flag_grey':
                return '#aeaeae';
            case 'icon_flag_orange':
                return '#ff5600';
            case 'icon_flag_purple':
                return '#e23bff';
            case 'icon_flag_red':
                return '#d42f00';
            case 'icon_flag_yellow':
                return '#ffe211';
        }

        return '#eee';
    }

    /**
     * Return the status int for this step
     *
     * @return int
     */
    public function getIntStatus()
    {
        return $this->getIntIndex();
    }

    /**
     * Returns all available transitions
     *
     * @return FlowTransition[]
     */
    public function getArrTransitions()
    {
        return FlowTransition::getObjectListFiltered(null, $this->getSystemid());
    }

    /**
     * @param FlowTransition $objTransition
     */
    public function addTransition(FlowTransition $objTransition)
    {
        $objTransition->updateObjectToDb($this->getSystemid());
    }

    /**
     * @param int $intTargetIndex
     * @return FlowTransition|null
     */
    public function getTransitionByTargetIndex($intTargetIndex)
    {
        $arrTransitions = $this->getArrTransitions();
        foreach ($arrTransitions as $objTransition) {
            if ($objTransition->getTargetStatus()->getIntIndex() == $intTargetIndex) {
                return $objTransition;
            }
        }
        return null;
    }

    /**
     * @return UserGroup[]
     */
    public function getArrEditGroups()
    {
        return $this->arrEditGroups;
    }

    /**
     * @param UserGroup[] $arrEditGroups
     */
    public function setArrEditGroups($arrEditGroups)
    {
        $this->arrEditGroups = $arrEditGroups;
    }

    /**
     * @return string
     */
    public function getStrDisplayName()
    {
        return $this->strName;
    }

    /**
     * @return FlowConfig
     */
    public function getFlowConfig()
    {
        return Objectfactory::getInstance()->getObject($this->getPrevId());
    }

    public function getStrAdditionalInfo()
    {
        return "";
    }

    public function getStrLongDescription()
    {
        return "";
    }

    public function updateObjectToDb($strPrevId = false)
    {
        // set index if we create a new record
        if (!validateSystemid($this->getSystemid()) && $this->intIndex !== 0 && empty($this->intIndex)) {
            // we add 1 because the first index must be 2 since 0/1 is reserved
            $this->intIndex = IdGenerator::generateNextId(_flow_module_id_) + 1;
        }

        return parent::updateObjectToDb($strPrevId);
    }

    public function deleteObject()
    {
        if ($this->getFlowConfig()->getIntRecordStatus() === 1) {
            $this->assertNoRecordsAreAssignedToThisStatus();
        }

        return parent::deleteObject();
    }

    public function deleteObjectFromDatabase()
    {
        if ($this->getFlowConfig()->getIntRecordStatus() === 1) {
            $this->assertNoRecordsAreAssignedToThisStatus();
        }

        return parent::deleteObjectFromDatabase();
    }

    public function assertNoRecordsAreAssignedToThisStatus()
    {
        $objFlow = $this->getFlowConfig();
        if ($objFlow instanceof FlowConfig) {
            $strTargetClass = $objFlow->getStrTargetClass();
            $intStatus = $this->getIntStatus();

            $dbPrefix = _dbprefix_;
            $arrRow = Database::getInstance()->getPRow("SELECT COUNT(*) AS cnt FROM {$dbPrefix}system WHERE system_class = ? AND system_status = ?", [$strTargetClass, $intStatus]);
            $intCount = isset($arrRow["cnt"]) ? (int) $arrRow["cnt"] : 0;

            if ($intCount > 0) {
                throw new \RuntimeException("There are already " . $intCount . " records assigned to the status " . $intStatus);
            }
        }
    }

    /**
     * Removes all transitions of this status and sets the new transitions according to the provided status array
     *
     * @param FlowStatus[]
     */
    public function setTargets(array $arrStatus)
    {
        try {
            Database::getInstance()->transactionBegin();

            // remove all existing transitions
            $arrTransition = $this->getArrTransitions();
            foreach ($arrTransition as $objTransition) {
                $objTransition->deleteObject();
            }

            // set new transitions
            foreach ($arrStatus as $objStatus) {
                if ($objStatus instanceof FlowStatus) {
                    $objTransition = new FlowTransition();
                    $objTransition->setStrTargetStatus($objStatus->getSystemid());
                    $objTransition->updateObjectToDb($this->getSystemid());
                } else {
                    throw new \InvalidArgumentException("Provided value is no FlowStatus object");
                }
            }

            Database::getInstance()->transactionCommit();
            return true;
        } catch (\Exception $e) {
            Database::getInstance()->transactionRollback();
            return false;
        }
    }
}
