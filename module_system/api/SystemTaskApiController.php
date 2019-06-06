<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Systemtasks\ApiSystemTaskInterface;
use Kajona\System\Admin\Systemtasks\SystemtaskBase;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Exception\NotFoundException;

/**
 * SystemTaskApiController
 *
 * @author christoph.kappestein@gmail.com
 * @since 7.1
 */
class SystemTaskApiController implements ApiControllerInterface
{
    /**
     * Endpoint which returns all available system tasks
     *
     * @api
     * @method GET
     * @path /systemtask
     * @authorization filetoken
     */
    public function listSystemTasks()
    {
        $systemTasks = SystemtaskBase::getAllSystemtasks();
        $return = [];

        foreach ($systemTasks as $systemTask) {
            if ($systemTask instanceof ApiSystemTaskInterface) {
                $group = $systemTask->getGroupIdentifier() ?: "default";

                if (!isset($return[$group])) {
                    $return[$group] = [];
                }

                $return[$group][$systemTask->getStrInternalTaskName()] = $systemTask->getStrTaskName();
            }
        }

        ksort($return);

        return [
            "systemtasks" => $return
        ];
    }

    /**
     * Shows the input form for a specific systemtask
     *
     * @api
     * @method GET
     * @path /systemtask/{systemtask}
     * @authorization filetoken
     */
    public function showSystemTasksForm(HttpContext $context)
    {
        $systemTask = $this->getSystemTaskByName($context->getUriFragment("systemtask"));
        if ($systemTask instanceof ApiSystemTaskInterface) {
            $form = $systemTask->getAdminForm();
            if ($form instanceof AdminFormgenerator) {
                return $form;
            } else {
                throw new NotFoundException("No system task form available");
            }
        } else {
            throw new NotFoundException("System task not found");
        }
    }

    /**
     * Executes a specific systemtask
     *
     * @api
     * @method POST
     * @path /systemtask/{systemtask}
     * @authorization filetoken
     */
    public function executeSystemTask($body, HttpContext $context)
    {
        $systemTask = $this->getSystemTaskByName($context->getUriFragment("systemtask"));
        if ($systemTask instanceof ApiSystemTaskInterface) {
            // execute the system task
            $return = $systemTask->execute((array) $body);

            return [
                "success" => true,
                "return" => $return
            ];
        } else {
            throw new NotFoundException("System task not found");
        }
    }

    /**
     * @param string $name
     * @return ApiSystemTaskInterface|null
     */
    private function getSystemTaskByName(string $name)
    {
        $systemTasks = SystemtaskBase::getAllSystemtasks();
        foreach ($systemTasks as $systemTask) {
            if ($systemTask instanceof ApiSystemTaskInterface && $name == $systemTask->getStrInternalTaskName()) {
                return $systemTask;
            }
        }

        return null;
    }
}

