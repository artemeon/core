<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Messagequeue;

use Kajona\System\System\Database;

/**
 * Producer
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
class Producer
{
    /**
     * @var Database
     */
    private $connection;

    /**
     * @param Database $connection
     */
    public function __construct(Database $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Dispatches the command which will be executed in the future
     *
     * @param CommandInterface $command
     */
    public function dispatch(CommandInterface $command): void
    {
        $this->connection->insert('agp_system_commands', [
            'command_id' => generateSystemid(),
            'command_class' => get_class($command),
            'command_payload' => \json_encode($command->toArray()),
        ], [false, false, false]);
    }
}
