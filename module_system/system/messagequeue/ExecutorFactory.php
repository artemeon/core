<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Messagequeue;

use Kajona\System\System\Reflection;
use Pimple\Container;

/**
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
class ExecutorFactory
{
    /**
     * @var Container
     */
    protected $objContainer;

    /**
     * @param Container $objContainer
     */
    public function __construct(Container $objContainer)
    {
        $this->objContainer = $objContainer;
    }

    /**
     * @param string $commandClass
     * @return ExecutorInterface
     * @throws \Kajona\System\System\Exception
     */
    public function factory(string $commandClass): ExecutorInterface
    {
        $reflection = new Reflection($commandClass);
        $values = $reflection->getAnnotationValuesFromClass(ExecutorInterface::EXECUTOR_ANNOTATION);

        if (!empty($values)) {
            $serviceName = reset($values);
            $executor = $this->objContainer->offsetGet($serviceName);

            if ($executor instanceof ExecutorInterface) {
                return $executor;
            } else {
                throw new \RuntimeException(sprintf("Provided service %s is not an instance of %s", $serviceName, ExecutorInterface::class));
            }
        } else {
            throw new \RuntimeException("Command has no @executor annotation");
        }
    }
}
