<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Messagequeue\Executor;

use Kajona\System\System\Messagequeue\CommandInterface;
use Kajona\System\System\Messagequeue\Exception\InvalidCommandException;
use Kajona\System\System\Messagequeue\Command\SendMessageCommand;
use Kajona\System\System\Messagequeue\ExecutorInterface;
use Kajona\System\System\MessagingMessagehandler;

/**
 * SendMessageExecutor
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
class SendMessageExecutor implements ExecutorInterface
{
    public function execute(CommandInterface $command): void
    {
        if (!$command instanceof SendMessageCommand) {
            throw new InvalidCommandException('Invalid command received');
        }

        (new MessagingMessagehandler())->sendMessageObject($command->getMessage(), $command->getReceivers());
    }
}
