<?php
/*"******************************************************************************************************
*   (c) 2010-2016 ARTEMEON                                                                              *
********************************************************************************************************/

namespace Kajona\Mediamanager\System\Workflows;

use Kajona\Mediamanager\System\MediamanagerRepo;
use Kajona\Mediamanager\System\MediamanagerRepoFilter;
use Kajona\Mediamanager\System\Search\Indexer;
use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\Workflows\System\WorkflowsHandlerInterface;
use Kajona\Workflows\System\WorkflowsWorkflow;

/**
 * WorkflowFileIndexer
 *
 * @package module_mediamanager
 */
class WorkflowFileIndexer implements WorkflowsHandlerInterface
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
        return array();
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
        return array();
    }

    public function setObjWorkflow($objWorkflow)
    {
        $this->objWorkflow = $objWorkflow;
    }

    public function getStrName()
    {
        return Carrier::getInstance()->getObjLang()->getLang("workflow_file_indexer", "system");
    }

    public function execute()
    {
        $objIndexer = new Indexer();

        $objFilter = new MediamanagerRepoFilter();
        $objFilter->setBitSearchIndex(true);
        $arrRepos = MediamanagerRepo::getObjectListFiltered($objFilter);

        foreach ($arrRepos as $objRepo) {
            $objIndexer->index($objRepo);
        }

        //trigger again
        return false;
    }

    public function onDelete()
    {
    }

    public function schedule()
    {
        $objDate = new Date();
        $objDate->setNextDay();
        $this->objWorkflow->setObjTriggerdate($objDate);
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
