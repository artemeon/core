<?php
/*"******************************************************************************************************
*   (c) 2015-2016 by Kajona, www.kajona.de                                                         *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Workflows\System;

use Kajona\Dashboard\System\ObjectTodoEntry;
use Kajona\Dashboard\System\TodoProviderInterface;
use Kajona\System\System\Lang;
use Kajona\System\System\Session;


/**
 * @package module_workflows
 */
abstract class WorkflowsTodoProviderBase implements TodoProviderInterface
{
    public static function getExtensionName()
    {
        return self::EXTENSION_POINT;
    }

    public function getCurrentTodosByCategory($strCategory, $bitLimited = true)
    {
        if (in_array($strCategory, array_keys($this->getWorkflowClasses()))) {
            return $this->getPendingWorkflows($strCategory, $bitLimited);
        } else {
            return array();
        }
    }

    public function getCategories()
    {
        return $this->getWorkflowClasses();
    }

    protected function getPendingWorkflows($strWorkflowClass, $bitLimited)
    {
        $objLang = Lang::getInstance();
        $arrUsers = array_merge(array(Session::getInstance()->getUserID()), Session::getInstance()->getGroupIdsAsArray());
        $arrResult = array();

        if ($bitLimited) {
            $arrWorkflows = WorkflowsWorkflow::getPendingWorkflowsForUser($arrUsers, 0, self::LIMITED_COUNT, array($strWorkflowClass));
        } else {
            $arrWorkflows = WorkflowsWorkflow::getPendingWorkflowsForUser($arrUsers, false, false, array($strWorkflowClass));
        }

        foreach ($arrWorkflows as $objWorkflow) {
            if ($objWorkflow->getObjWorkflowHandler()->providesUserInterface()) {
                /** @var WorkflowsWorkflow $objWorkflow */
                $objTodo = new ObjectTodoEntry($objWorkflow);
                $objTodo->setStrCategory($strWorkflowClass);
                $arrResult[] = $objTodo;
            }
        }

        return $arrResult;
    }

    /**
     * Returns an array containing all classes
     *
     * @return array<workflow_class => "category label">
     */
    abstract protected function getWorkflowClasses();
}

