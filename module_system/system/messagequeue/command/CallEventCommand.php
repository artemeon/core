<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Messagequeue\Command;

use Kajona\System\System\Messagequeue\CommandInterface;

/**
 * Command which dispatches a core event
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 * @executor system_message_queue_executor_call_event
 */
class CallEventCommand implements CommandInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $arguments;

    /**
     * Note arguments can only contain values which can be json_encoded
     *
     * @param string $name
     * @param array $arguments
     */
    public function __construct(string $name, array $arguments)
    {
        $this->name = $name;
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->arguments,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data): CommandInterface
    {
        return new self(
            $data['name'] ?? null,
            $data['arguments'] ?? null
        );
    }
}
