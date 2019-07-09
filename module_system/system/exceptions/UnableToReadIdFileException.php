<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Exceptions;

use Kajona\System\System\Exception;
use Throwable;

final class UnableToReadIdFileException extends Exception
{
    public function __construct(Throwable $previousException = null)
    {
        parent::__construct('Failed reading content from idFile', Exception::$level_FATALERROR, $previousException);
    }
}
