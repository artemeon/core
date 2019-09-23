<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace AGP\Console;

use AGP\Console\Command\System\LockCommand;
use AGP\Console\Command\System\UnlockCommand;
use AGP\Console\Console\CommandLoader;
use AGP\Console\Container\CommandBag;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

final class ServiceProvider implements ServiceProviderInterface
{
    private function registerServices(Container $container): void
    {
        $container[CommandBag::class] = static function (): CommandBag {
            return new CommandBag();
        };
        $container[CommandLoader::class] = static function (Container $container): CommandLoader {
            return new CommandLoader(
                $container[CommandBag::class]
            );
        };

        $container[LockCommand::class] = static function (): LockCommand {
            return new LockCommand();
        };
        $container[UnlockCommand::class] = static function (): UnlockCommand {
            return new UnlockCommand();
        };
    }

    private function registerCommands(Container $container): void
    {
        $container->extend(
            CommandBag::class,
            static function (CommandBag $commandBag, Container $container): CommandBag {
                $commandBag->add(...[
                    $container[LockCommand::class],
                    $container[UnlockCommand::class],
                ]);

                return $commandBag;
            }
        );
    }

    public function register(Container $container): void
    {
        $this->registerServices($container);
        $this->registerCommands($container);
    }
}
