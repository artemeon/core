<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Devops\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\System\System\Pluginmanager;
use Kajona\System\System\SysteminfoInterface;

/**
 * DevopsApiController
 *
 * @author christoph.kappestein@gmail.com
 * @since 7.1
 */
class DevopsApiController implements ApiControllerInterface
{
    /**
     * Endpoint which returns the output of all available devops plugins
     *
     * @api
     * @method GET
     * @path /systeminfo
     * @authorization filetoken
     */
    public function listPlugins()
    {
        $pluginManager = new Pluginmanager(SysteminfoInterface::STR_EXTENSION_POINT);
        /** @var SysteminfoInterface[] $plugins */
        $plugins = $pluginManager->getPlugins();

        $return = [];
        foreach ($plugins as $plugin) {
            $return[] = [
                "title" => $plugin->getStrTitle(),
                "fields" => $plugin->getArrContent(SysteminfoInterface::TYPE_JSON),
            ];
        }

        return [
            "plugins" => $return
        ];
    }
}

