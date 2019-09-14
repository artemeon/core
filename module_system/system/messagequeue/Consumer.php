<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Messagequeue;

use Kajona\System\System\Database;
use Psr\Log\LoggerInterface;

/**
 * Consumer
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
class Consumer
{
    /**
     * Breaks in case the workflow takes more then this seconds
     */
    private const MAX_EXECUTION = 600;

    /**
     * Count of events which are max processed
     */
    private const MAX_PREFETCH = 512;

    /**
     * @var Database
     */
    private $connection;

    /**
     * @var ExecutorFactory
     */
    private $executorFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Database $connection
     * @param ExecutorFactory $executorFactory
     * @param LoggerInterface $logger
     */
    public function __construct(Database $connection, ExecutorFactory $executorFactory, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->executorFactory = $executorFactory;
        $this->logger = $logger;
    }

    public function consumeAll(): void
    {
        $startTime = time();
        $result = $this->connection->getPArray('SELECT command_id, command_class, command_payload FROM agp_system_commands', [], 0, self::MAX_PREFETCH, false);

        foreach ($result as $row) {
            // directly delete the event since otherwise this would block the queue in case the event throws an
            // unrecoverable error
            $this->connection->delete('agp_system_commands', ['command_id' => $row['command_id']]);

            $class = $row['command_class'];
            $payload = \json_decode($row['command_payload'], true);

            $this->consume($class, $payload);

            // in case this workflow runs too long break and wait for the next execution
            if (time() - $startTime > self::MAX_EXECUTION) {
                break;
            }
        }
    }

    /**
     * @param string $class
     * @param array $payload
     */
    private function consume(string $class, array $payload): void
    {
        // check whether the event class exists
        if (!class_exists($class)) {
            return;
        }

        try {
            if (!is_callable($class . '::fromArray', false, $callable)) {
                throw new \RuntimeException('Command has no fromArray method');
            }

            $command = call_user_func_array($callable, [$payload]);

            if ($command instanceof CommandInterface) {
                $this->executorFactory->factory(get_class($command))->execute($command);
            } else {
                throw new \RuntimeException('Could not create command');
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
