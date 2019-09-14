<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System\Messagequeue\Command;

use Kajona\System\System\Messagequeue\CommandInterface;
use Kajona\System\System\MessagingMessage;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;

/**
 * Command to send a message to several receivers in the background, especially useful if you send a messsage to many
 * recipients
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 * @executor system_message_queue_executor_send_message
 */
class SendMessageCommand implements CommandInterface
{
    /**
     * @var MessagingMessage
     */
    private $message;

    /**
     * @var array
     */
    private $receivers;

    /**
     * @param MessagingMessage $message
     * @param array $receivers
     */
    public function __construct(MessagingMessage $message, array $receivers)
    {
        $this->message = $message;
        $this->receivers = $receivers;
    }

    /**
     * @return MessagingMessage
     */
    public function getMessage(): MessagingMessage
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getReceivers(): array
    {
        return $this->receivers;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $receiverIds = array_map(function($receiver){
            if ($receiver instanceof UserGroup || $receiver instanceof UserUser) {
                return $receiver->getSystemid();
            } elseif (is_string($receiver) && validateSystemid($receiver)) {
                return $receiver;
            }

            return null;
        }, $this->receivers);

        return [
            'message' => $this->message,
            'receivers' => array_filter($receiverIds),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data): CommandInterface
    {
        $receivers = $data['receivers'] ?? [];
        $receivers = array_map(function($receiverId){
            return Objectfactory::getInstance()->getObject($receiverId);
        }, $receivers);

        return new self(
            MessagingMessage::fromArray($data['message'] ?? []),
            $receivers
        );
    }
}
