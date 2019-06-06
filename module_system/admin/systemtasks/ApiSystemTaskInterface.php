<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\Admin\Systemtasks;

use Kajona\System\Admin\AdminFormgenerator;

/**
 * Interface which allows the execution of a system task through the API
 *
 * @since 7.1
 * @author christoph.kappestein@artemeon.de
 */
interface ApiSystemTaskInterface
{
    /**
     * Returns the internal name of the task. This name should correspond with the filename of the task.
     *
     * @return string
     */
    public function getStrInternalTaskName();

    /**
     * Executes the system task. The $body parameter contains the JSON decoded request body of the execute request
     *
     * @param mixed $body
     * @return string
     */
    public function execute($body);

    /**
     * The group identifier is used to group the tasks available in an installation.
     * Don't use too specific identifiers to avoid having a single group for every task,
     * refer to a rather general therm, e.g. "caching" or "database".
     * The identifier is resolved via the systems' language-files internally.
     * Currently, there are: "", "database", "cache", "stats"
     *
     * @return string or "" for default group
     */
    public function getGroupIdentifier();

    /**
     * Returns the form generator for this system task. The execute method expects a JSON object which was described
     * by this form
     *
     * @return AdminFormgenerator|null
     */
    public function getAdminForm();
}

