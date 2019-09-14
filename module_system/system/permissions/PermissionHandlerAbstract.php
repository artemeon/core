<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                                        *
********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Permissions;

use Kajona\Flow\System\FlowManager;
use Kajona\Flow\System\FlowStatus;
use Kajona\System\System\Exception;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Rights;
use Kajona\System\System\Root;

/**
 * PermissionHandlerAbstract
 *
 * @package module_system
 * @author christoph.kappestein@artemeon.de
 * @since 7.0
 */
abstract class PermissionHandlerAbstract implements PermissionHandlerInterface
{
    /**
     * @var Objectfactory
     */
    protected $objFactory;

    /**
     * @var Rights
     */
    protected $objRights;

    /**
     * @var FlowManager
     */
    protected $objFlowManager;

    /**
     * @param Objectfactory $objFactory
     * @param Rights $objRights
     * @param FlowManager $objFlowManager
     */
    public function __construct(Objectfactory $objFactory, Rights $objRights, FlowManager $objFlowManager)
    {
        $this->objFactory = $objFactory;
        $this->objRights = $objRights;
        $this->objFlowManager = $objFlowManager;
    }

    /**
     * @inheritdoc
     */
    public function onCreate(Root $record)
    {
        if (!$this->isValid($record)) {
            return;
        }

        $this->calculatePermissions($record);
    }

    /**
     * @inheritdoc
     */
    public function onUpdate(Root $oldRecord, Root $newRecord)
    {
        if (!$this->isValid($newRecord)) {
            return;
        }

        // if nothing has changed we also dont need to set the rights
        if (!$this->hasChanged($oldRecord, $newRecord)) {
            return;
        }

        $this->calculatePermissions($newRecord);
    }

    /**
     * @inheritdoc
     */
    public function calculatePermissions(Root $objRecord)
    {
        $objStatus = $this->objFlowManager->getCurrentStepForModel($objRecord);

        if ($objStatus instanceof FlowStatus) {
            // in case the status has no roles configured we dont set any rights
            if (!$this->hasRolesConfigured($objStatus)) {
                return;
            }

            $objProcessor = new PermissionActionProcessor($this->objRights);

            $this->setRights($objStatus, $objRecord, $objProcessor);

            $objProcessor->applyActions();
        }
    }

    /**
     * This method must return whether a property has changed so that we need to set the rights i.e. in case the status
     * or a OE has changed
     *
     * @param Root $objOldRecord
     * @param Root $objNewRecord
     * @return bool
     */
    protected function hasChanged(Root $objOldRecord, Root $objNewRecord)
    {
        return $objOldRecord->getIntRecordStatus() != $objNewRecord->getIntRecordStatus();
    }

    /**
     * Returns whether the record is valid to be processed by the permission handler. IF you want to exclude specific
     * records you need to overwrite this method
     *
     * @param Root $record
     * @return bool
     */
    protected function isValid(Root $record): bool
    {
        return true;
    }

    /**
     * Method which adds specific right actions to the provided permission action processor
     *
     * @param FlowStatus $objStatus
     * @param Root $objRecord
     * @param PermissionActionProcessor $objProcessor
     * @throws Exception
     */
    protected function setRights(FlowStatus $objStatus, Root $objRecord, PermissionActionProcessor $objProcessor)
    {
        $arrRoles = $this->getRoles();
        $objProcessor->addAction(new RemoveAllGroups($objRecord->getSystemid()));

        foreach ($arrRoles as $strRole) {
            $arrGroups = $this->getGroupsByRole($objRecord, $strRole);
            $arrRights = $objStatus->getRightsForRole($strRole);

            foreach ($arrGroups as $objGroup) {
                foreach ($arrRights as $strRight) {
                    $objProcessor->addAction(new AddPermissionToGroup($objRecord->getSystemid(), $objGroup->getSystemid(), $strRight));
                }
            }
        }
    }

    /**
     * Checks whether the provided status has roles configured
     *
     * @param FlowStatus $objStatus
     * @return bool
     */
    private function hasRolesConfigured(FlowStatus $objStatus)
    {
        $strRoles = $objStatus->getStrRoles();
        if (empty($strRoles)) {
            return false;
        }

        $arrRoles = json_decode($strRoles, true);
        if (empty($arrRoles)) {
            return false;
        }

        return is_array($arrRoles);
    }

    /**
     * @inheritdoc
     */
    public function getRoleLabel($strRole)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getAvailableRolesWithLabel(): array {
        $availableRoles = [];
        foreach($this->getRoles() as $role) {
            $availableRoles[$role] = $this->getRoleLabel($role);
        }
    }


}
