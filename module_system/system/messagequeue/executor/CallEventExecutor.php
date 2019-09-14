<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Messagequeue\Executor;

use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\Messagequeue\CommandInterface;
use Kajona\System\System\Messagequeue\Command\CallEventCommand;
use Kajona\System\System\Messagequeue\Exception\InvalidCommandException;
use Kajona\System\System\Messagequeue\ExecutorInterface;

/**
 * CallEventExecutor
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
class CallEventExecutor implements ExecutorInterface
{
    /**
     * @var CoreEventdispatcher
     */
    private $eventDispatcher;

    public function __construct(CoreEventdispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function execute(CommandInterface $command): void
    {
        if (!$command instanceof CallEventCommand) {
            throw new InvalidCommandException('Invalid command received');
        }

        $this->eventDispatcher->notifyGenericListeners($command->getName(), $command->getArguments());
    }
}
