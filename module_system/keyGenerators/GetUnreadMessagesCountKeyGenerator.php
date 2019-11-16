<?php


namespace Kajona\System\KeyGenerator;


use Kajona\System\Admin\KeyGeneratorInterface;
use Slim\Http\Request as SlimRequest;

/**
 * Key generator for /v1/messages/count end-point
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.2
 */
class GetUnreadMessagesCountKeyGenerator implements KeyGeneratorInterface
{

    public function getKey(SlimRequest $request): string
    {
        //todo choose a better key pattern
        return '/' . $request->getUri()->getPath();
    }
}
