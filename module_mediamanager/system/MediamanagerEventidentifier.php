<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Mediamanager\System;

use Kajona\System\System\Root;

/**
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
interface MediamanagerEventidentifier
{
    /**
     * Callback method in case a user archives existing files
     *
     * @param Root $object
     * @param string $folder
     *
     * @return bool
     * @since 7.1
     */
    const EVENT_MEDIAMANAGER_FILES_ARCHIVED = "core.mediamanager.files.archived";
}
