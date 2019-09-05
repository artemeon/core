<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace AGP\Console\Command\System;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @TODO move command to system module
 */
final class LockCommand extends Command
{
    private const LOCK_FILE = _realpath_ . '/kajona.lock';

    protected function configure(): void
    {
        $this->setName('system:lock');
        $this->setDescription('Locks a previously unlocked AGP system.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        \touch(self::LOCK_FILE);
    }
}
