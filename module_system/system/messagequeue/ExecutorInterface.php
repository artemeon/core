<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Messagequeue;

/**
 * Executor which gets called by the consumer to execute the logic for a specific command
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
interface ExecutorInterface
{
    const EXECUTOR_ANNOTATION = '@executor';

    /**
     * Executes a specific command
     *
     * @param CommandInterface $command
     */
    public function execute(CommandInterface $command): void;
}
