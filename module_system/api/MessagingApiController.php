<?php


namespace Kajona\System\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\Api\System\Http\JsonResponse;
use Kajona\System\Admin\MemoryCacheManager;
use Kajona\System\System\Exception;
use Kajona\System\System\MessagingAlert;
use Kajona\System\System\MessagingMessage;
use Kajona\System\System\Session;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Environment\HttpResponse;

/**
 * Api controller to manage users messages
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.2
 * @keyGenerator Kajona\System\KeyGenerator\MessagingKeyGenerator
 */
class MessagingApiController implements ApiControllerInterface
{
    /**
     * Object containing the session-management
     *
     * @inject system_session
     * @var Session
     */
    protected $objSession;

    /**
     * returns number of unread messages
     *
     * @param HttpContext $context
     * @return HttpResponse
     * @throws Exception
     * @api
     * @cacheable
     * @method GET
     * @path /v1/messages/count
     * @authorization usertoken
     */
    public function getUnreadMessagesCount(HttpContext $context): HttpResponse
    {
        $userId = $this->objSession->getUserID();
        $count = MessagingMessage::getNumberOfMessagesForUser($userId, true);
        $alert = MessagingAlert::getNextAlertForUser($userId);
        return new JsonResponse([
            "count" => $count,
            "alert" => $alert
        ]);
    }
}
