<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Workflows;

use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\Messagequeue\Consumer;
use Kajona\System\System\ServiceProvider;
use Kajona\Workflows\System\WorkflowsHandlerInterface;
use Kajona\Workflows\System\WorkflowsWorkflow;

/**
 * WorkflowExecuteAsyncEvents
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
class WorkflowCommandConsumer implements WorkflowsHandlerInterface
{
    /**
     * @var WorkflowsWorkflow
     */
    private $objWorkflow = null;

    /**
     * @see WorkflowsHandlerInterface::getConfigValueNames()
     */
    public function getConfigValueNames()
    {
        return [];
    }

    /**
     * @see WorkflowsHandlerInterface::setConfigValues()
     *
     * @param string $strVal1
     * @param string $strVal2
     * @param string $strVal3
     */
    public function setConfigValues($strVal1, $strVal2, $strVal3)
    {
    }

    /**
     * @see WorkflowsHandlerInterface::getDefaultValues()
     */
    public function getDefaultValues()
    {
        return [];
    }

    public function setObjWorkflow($objWorkflow)
    {
        $this->objWorkflow = $objWorkflow;
    }

    public function getStrName()
    {
        return Carrier::getInstance()->getObjLang()->getLang("workflow_consumer_title", "system");
    }

    public function execute()
    {
        /** @var Consumer $consumer */
        $consumer = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::MESSAGE_QUEUE_CONSUMER);
        $consumer->consumeCommands();

        // trigger again
        return false;
    }

    public function onDelete()
    {
    }

    public function schedule()
    {
        // call the workflow every minute
        $this->objWorkflow->setTriggerdate(new Date());
    }

    public function getUserInterface()
    {
    }

    public function processUserInput($arrParams)
    {
        return;
    }

    public function providesUserInterface()
    {
        return false;
    }
}
