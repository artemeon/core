<?php

namespace Kajona\System\KeyGenerator;


use Slim\Http\Request as SlimRequest;

/**
 * Class to manage all cache operations
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.2
 */
class MessagingKeyGenerator
{
    public function getUnreadMessagesCount(SlimRequest $request): string
    {
        //todo choose a better key pattern
        return $request->getMethod() . '/' . $request->getUri()->getPath();
    }

}
