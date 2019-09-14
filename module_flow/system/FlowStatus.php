<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Flow\System;

use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Database;
use Kajona\System\System\IdGenerator;
use Kajona\System\System\Lang;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Permissions\PermissionHandlerFactory;
use Kajona\System\System\Permissions\PermissionHandlerInterface;
use Kajona\System\System\Root;
use Kajona\System\System\UserGroup;

/**
 * FlowStatus
 *
 * @author christoph.kappestein@artemeon.de
 * @targetTable agp_flow_step.step_id
 * @module flow
 * @moduleId _flow_module_id_
 * @formGenerator Kajona\Flow\Admin\FlowStatusFormgenerator
 * @sortManager Kajona\System\System\CommonSortmanager
 */
class FlowStatus extends Model implements ModelInterface, AdminListableInterface
{
    const COLOR_BLACK = "#000000";
    const COLOR_BLUE = "#0040b3";
    const COLOR_BROWN = "#d47a0b";
    const COLOR_GREEN = "#0e8500";
    const COLOR_GREY = "#aeaeae";
    const COLOR_ORANGE = "#ff5600";
    const COLOR_PURPLE = "#e23bff";
    const COLOR_RED = "#d42f00";
    const COLOR_YELLOW = "#ffe211";

    /**
     * @var string
     * @tableColumn agp_flow_step.step_name
     * @tableColumnDatatype char254
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     * @fieldMandatory
     */
    protected $strName;

    /**
     * @var int
     * @tableColumn agp_flow_step.step_index
     * @tableColumnDatatype int
     */
    protected $intIndex;

    /**
     * @var string
     * @tableColumn agp_flow_step.step_icon
     * @tableColumnDatatype char20
     * @fieldType Kajona\System\Admin\Formentries\FormentryColorpicker
     * @fieldMandatory
     */
    protected $strIconColor;

    /**
     * @var UserGroup[]
     * @objectList agp_flow_status2edit (source="status_system_id", target="usergroup_system_id", type={"Kajona\\System\\System\\UserGroup"})
     * @fieldType Kajona\System\Admin\Formentries\FormentryObjecttags
     */
    protected $arrEditGroups;

    /**
     * @var string
     * @tableColumn agp_flow_step.step_roles
     * @tableColumnDatatype text
     * @fieldType Kajona\Flow\Admin\Formentries\FormentryRoles
     * @blockEscaping
     */
    protected $strRoles;

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
        return "icon_flag_hex_".$this->strIconColor;
    }

    /**
     * @return string
     */
    public function getStrIconColor()
    {
        return $this->strIconColor;
    }

    /**
     * @param string $strIconColor
     */
    public function setStrIconColor($strIconColor)
    {
        $this->strIconColor = $strIconColor;
    }

    /**
     * @return string
     */
    public function getStrRoles()
    {
        return $this->strRoles;
    }

    /**
     * @param string $strRoles
     */
    public function setStrRoles($strRoles)
    {
        $this->strRoles = $strRoles;
    }

    /**
     * @return array
     */
    public function getRightsForRole($strRole)
    {
        $arrRoles = json_decode($this->strRoles, true);
        return $arrRoles[$strRole] ?? [];
    }

    /**
     * @param int $intRole
     * @param string $strRight
     * @return bool
     */
    public function roleHasRight(int $intRole, string $strRight)
    {
        $arrPermissions = $this->getRightsForRole($intRole);
        return in_array($strRight, $arrPermissions);
    }

    /**
     * @param array $arrRoles
     */
    public function setRoles(array $arrRoles)
    {
        $this->setStrRoles(json_encode($arrRoles));
    }

    /**
     * @return PermissionHandlerInterface
     */
    public function getPermissionHandler()
    {
        /** @var PermissionHandlerFactory $objHandlerFactory */
        $objHandlerFactory = Carrier::getInstance()->getContainer()->offsetGet(\Kajona\System\System\ServiceProvider::STR_PERMISSION_HANDLER_FACTORY);
        $strTargetClass = $this->getFlowConfig()->getStrTargetClass();
        $objPermissionHandler = $objHandlerFactory->factory($strTargetClass);

        return $objPermissionHandler;
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
     * @throws \Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException
     */
    public function addTransition(FlowTransition $objTransition)
    {
        ServiceLifeCycleFactory::getLifeCycle(get_class($objTransition))->update($objTransition, $this->getSystemid());
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
        /** @var FlowConfig $objFlow */
        $objFlow = Objectfactory::getInstance()->getObject($this->getStrPrevId());
        if ($objFlow instanceof FlowConfig) {
            $strTargetClass = $objFlow->getStrTargetClass();

            /** @var Root $objInstance */
            if (!class_exists($strTargetClass)) {
                return $strTargetClass;
            }
            $objInstance = new $strTargetClass();
            $strName = Lang::getInstance()->getLang($this->strName, $objInstance->getArrModule("module"));

            if ($strName[0] != "!") {
                return $strName;
            }

            $strName = Lang::getInstance()->getLang($this->strName, "flow");
            if ($strName[0] != "!") {
                return $strName;
            }
        }

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

            $arrRow = Database::getInstance()->getPRow("SELECT COUNT(*) AS cnt FROM agp_system WHERE system_class = ? AND system_status = ?", [$strTargetClass, $intStatus]);
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
                    ServiceLifeCycleFactory::getLifeCycle(get_class($objTransition))->update($objTransition, $this->getSystemid());
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
