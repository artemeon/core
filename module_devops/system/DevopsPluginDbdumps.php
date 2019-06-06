<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Devops\System;

use Kajona\System\System\Carrier;
use Kajona\System\System\Filesystem;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SysteminfoInterface;

/**
 * A plugin collecting the list of dbdumps available
 *
 * @package AGP\Devops\System
 * @author stefan.idler@artemeon.de
 * @since 6.1
 */
class DevopsPluginDbdumps implements SysteminfoInterface
{
    public static function getExtensionName()
    {
        return SysteminfoInterface::STR_EXTENSION_POINT;
    }

    /**
     * @inheritDoc
     */
    public function getStrTitle()
    {
        return Carrier::getInstance()->getObjLang()->getLang("plugin_dbdumps_title", "devops");
    }

    /**
     * @inheritDoc
     */
    public function getArrContent($mediaType = self::TYPE_HTML)
    {
        $objFilesystem = new Filesystem();

        $arrRows = [];
        $arrRows[] = ["Name", "Size", "Create date acc. to filename", "Last modified"];

        foreach ($objFilesystem->getFilelist(_projectpath_."/dbdumps") as $strOneDump) {
            $arrDetails = $objFilesystem->getFileDetails(_projectpath_."/dbdumps/".$strOneDump);

            $strTimestamp = "";
            if (StringUtil::indexOf($strOneDump, "_") !== false) {
                $strTimestamp = StringUtil::substring($strOneDump, StringUtil::lastIndexOf($strOneDump, "_") + 1, (StringUtil::indexOf($strOneDump, ".") - StringUtil::lastIndexOf($strOneDump, "_")));

                if (StringUtil::length($strTimestamp) > 9 && is_numeric($strTimestamp)) {
                    $strTimestamp = timeToString($strTimestamp);
                } else {
                    $strTimestamp = "";
                }
            }

            $arrRows[] = [
                $strOneDump,
                bytesToString($arrDetails['filesize']),
                $strTimestamp,
                timeToString($arrDetails['filechange'])
            ];
        }

        return $arrRows;
    }


}