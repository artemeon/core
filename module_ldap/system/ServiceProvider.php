<?php

namespace Kajona\Ldap\System;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        define("_ldap_module_id_", 16);
    }
}
