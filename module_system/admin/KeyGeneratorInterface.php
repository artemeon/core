<?php

namespace Kajona\System\Admin;

use Slim\Http\Request as SlimRequest;

/**
 * Each key-generator class must implement this interface in order to keep the same structure
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.2
 */
interface KeyGeneratorInterface
{
    public function getKey(SlimRequest $request): string;
}
