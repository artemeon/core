<?php


namespace Kajona\System\Admin;

use Slim\Http\Request as SlimRequest;

/**
 * Each key-invalidator class must implement this interface in order to keep the same structure
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.2
 */
interface KeyInvalidatorInterface
{
    public function invalidate(CacheStore $store, SlimRequest $request): void;
}
