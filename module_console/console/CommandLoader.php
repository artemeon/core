<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace AGP\Console\Console;

use AGP\Console\Container\CommandBag;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

class CommandLoader implements CommandLoaderInterface
{
    /**
     * @var CommandBag
     */
    private $commandBag;

    public function __construct(CommandBag $commandBag)
    {
        $this->commandBag = $commandBag;
    }

    public function has($name): bool
    {
        return $this->commandBag->has($name);
    }

    public function get($name): Command
    {
        return $this->commandBag->get($name);
    }

    public function getNames(): array
    {
        return $this->commandBag->getNames();
    }
}
