<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Workflows;

use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\LoginProtocolCleanerInterface;
use Kajona\Workflows\System\WorkflowsHandlerInterface;
use Kajona\Workflows\System\WorkflowsWorkflow;

/**
 * Workflow triggering the cleanup of the user login protocol
 *
 * @author stefan.idler@artemeon.de
 * @since 7.1
 */
class LoginProtocolCleanupWorkflow implements WorkflowsHandlerInterface
{
    private $execHour = 4;

    /**
     * @var WorkflowsWorkflow
     */
    private $workflowInstance;

    /**
     * @var LoginProtocolCleanerInterface
     * @inject Kajona\System\System\LoginProtocolCleanerInterface
     */
    private $cleanService;

    /**
     * @see WorkflowsHandlerInterface::getConfigValueNames()
     */
    public function getConfigValueNames()
    {
        return [
            Carrier::getInstance()->getObjLang()->getLang('workflow_user_loginprotocol_cleaner_val1', 'user'),
        ];
    }

    public function getStrName()
    {
        return Carrier::getInstance()->getObjLang()->getLang('workflow_user_loginprotocol_cleaner_title', 'user');
    }


    /**
     * @param string $strVal1
     * @param string $strVal2
     * @param string $strVal3
     * @see WorkflowsHandlerInterface::setConfigValues()
     *
     */
    public function setConfigValues($strVal1, $strVal2, $strVal3)
    {
        if ($strVal1 !== '' && is_numeric($strVal1)) {
            $this->execHour = $strVal1;
        }
    }

    /**
     * @see WorkflowsHandlerInterface::getDefaultValues()
     */
    public function getDefaultValues()
    {
        return array(6);
    }

    public function setObjWorkflow($objWorkflow)
    {
        $this->workflowInstance = $objWorkflow;
    }


    public function execute()
    {
        $this->cleanService->cleanUserlog();
        return false;
    }

    public function onDelete()
    {
    }

    public function schedule()
    {
        $date = new Date();
        $date->setNextDay();
        $date->setIntHour($this->execHour);
        $date->setIntMin(15);
        $date->setIntSec(0);
        $this->workflowInstance->setTriggerdate($date);
    }

    public function getUserInterface()
    {
    }

    public function processUserInput($arrParams)
    {
    }

    public function providesUserInterface()
    {
        return false;
    }
}
