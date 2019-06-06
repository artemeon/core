<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Workflows;

use Kajona\System\System\Carrier;
use Kajona\System\System\Config;
use Kajona\System\System\Database;
use Kajona\System\System\Date;
use Kajona\System\System\ServiceProvider;
use Kajona\Workflows\System\WorkflowsHandlerInterface;
use Kajona\Workflows\System\WorkflowsWorkflow;

/**
 * Forces an update of oracles' internal stats
 *
 * @author stefan.idler@artemeon.de
 * @since 7.1
 */
class UpdateOracleStats implements WorkflowsHandlerInterface
{
    private $execHour = 6;

    /**
     * @var WorkflowsWorkflow
     */
    private $workflowInstance;

    /**
     * @see WorkflowsHandlerInterface::getConfigValueNames()
     */
    public function getConfigValueNames()
    {
        return array(
            Carrier::getInstance()->getObjLang()->getLang("workflow_oracle_stats_val1", "system"),
        );
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
        if ($strVal1 != "" && is_numeric($strVal1)) {
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

    public function getStrName()
    {
        return Carrier::getInstance()->getObjLang()->getLang("workflow_oracle_stats_title", "system");
    }

    public function execute()
    {
        $cfg = Config::getInstance("module_system", "config.php");

        if ($cfg->getConfig('dbdriver') !== 'oci8') {
            //do n.th.
            return true;
        }

        /** @var Database $db */
        $db = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_DB);
        $db->_pQuery("BEGIN dbms_stats.gather_schema_stats('".$cfg->getConfig("dbusername")."'); END;", []);

        //trigger again
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
        $date->setIntMin(0);
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
