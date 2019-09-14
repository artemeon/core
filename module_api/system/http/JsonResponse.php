<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System\Http;

use PSX\Http\Environment\HttpResponse;

/**
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
class JsonResponse extends HttpResponse
{
    /**
     * @param mixed $data
     * @param int $code
     * @param array $headers
     */
    public function __construct($data, int $code = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';

        parent::__construct($code, $headers, \json_encode($data));
    }
}
