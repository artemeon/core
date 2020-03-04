<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Kajona\System\System\Exceptions\InvalidJsonFormatException;

final class JsonDecoder
{
    public static function decode($json)
    {
        $decodedJson = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonFormatException(json_last_error_msg());
        }

        return $decodedJson;
    }
}
