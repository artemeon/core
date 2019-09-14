<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace AGP\Console\Container;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class CommandBag
{
    /**
     * @var Command[]
     */
    private $commands = [];

    public function add(Command ...$commands): void
    {
        foreach ($commands as $command) {
            $this->commands[$command->getName()] = $command;
        }
    }

    public function has(string $commandName): bool
    {
        return isset($this->commands[$commandName]);
    }

    /**
     * @param string $commandName
     * @return Command
     * @throws CommandNotFoundException
     */
    public function get(string $commandName): Command
    {
        if (!isset($this->commands[$commandName])) {
            throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $commandName));
        }

        return $this->commands[$commandName];
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return \array_keys($this->commands);
    }
}
