<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Messagequeue\Command;

use Kajona\System\System\Messagequeue\CommandInterface;

/**
 * Command to send a message to several receivers in the background, especially useful if you send a messsage to many
 * recipients
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 * @executor system_message_queue_executor_send_message
 */
class SetRecursiveRightsCommand implements CommandInterface
{
    /**
     * @var string
     */
    private $systemid;

    /**
     * @var array
     */
    private $rights;

    /**
     * @param string $systemid
     * @param array $rights
     */
    public function __construct(string $systemid, array $rights)
    {
        $this->systemid = $systemid;
        $this->rights = $rights;
    }

    /**
     * @return string
     */
    public function getSystemid(): string
    {
        return $this->systemid;
    }

    /**
     * @return array
     */
    public function getRights(): array
    {
        return $this->rights;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'systemid' => $this->systemid,
            'rights' => $this->rights,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data): CommandInterface
    {
        return new self(
            $data['systemid'],
            $data['rights']
        );
    }
}
