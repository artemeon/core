<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Messagequeue\Executor;

use Kajona\System\System\Messagequeue\Command\SetRecursiveRightsCommand;
use Kajona\System\System\Messagequeue\CommandInterface;
use Kajona\System\System\Messagequeue\Exception\InvalidCommandException;
use Kajona\System\System\Messagequeue\ExecutorInterface;
use Kajona\System\System\Rights;

/**
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
class SetRecursiveRightsExecutor implements ExecutorInterface
{
    /**
     * @var Rights
     */
    private $rights;

    /**
     * @param Rights $rights
     */
    public function __construct(Rights $rights)
    {
        $this->rights = $rights;
    }

    /**
     * @param CommandInterface $command
     * @throws \Kajona\System\System\Exception
     */
    public function execute(CommandInterface $command): void
    {
        if (!$command instanceof SetRecursiveRightsCommand) {
            throw new InvalidCommandException('Invalid command received');
        }

        $this->rights->setRights(
            $command->getRights(),
            $command->getSystemid()
        );
    }
}
