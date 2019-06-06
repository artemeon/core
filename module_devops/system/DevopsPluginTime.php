<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Devops\System;


use Kajona\System\System\Carrier;
use Kajona\System\System\SysteminfoInterface;
use Kajona\System\System\SystemSetting;

/**
 * General information regarding the current timezone environment
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 4.5
 */
class DevopsPluginTime implements SysteminfoInterface
{
    /**
     * Returns the title of the info-block
     *
     * @return string
     */
    public function getStrTitle()
    {
        return Carrier::getInstance()->getObjLang()->getLang("timeinfo", "devops");
    }

    /**
     * @inheritDoc
     */
    public function getArrContent($mediaType = self::TYPE_HTML)
    {
        $strOldTimezone = date_default_timezone_get();
        $objLang = Carrier::getInstance()->getObjLang();
        $arrReturn = array();
        $arrReturn[] = array($objLang->getLang("time_phptimestamp", "devops"), time());
        $arrReturn[] = array($objLang->getLang("time_systemtimezone", "devops"), date_default_timezone_get());
        $arrReturn[] = array($objLang->getLang("time_localsystemtime", "devops"), timeToString(time()));
        date_default_timezone_set("UTC");
        $arrReturn[] = array($objLang->getLang("time_systemtime_UTC", "devops"), date('Y-m-d H:i:s'));
        $arrReturn[] = array($objLang->getLang("time_systemzone_manual_setting", "devops"), SystemSetting::getConfigValue("_system_timezone_"));

        date_default_timezone_set($strOldTimezone);
        return $arrReturn;
    }

    /**
     * Returns the name of extension/plugin the objects wants to contribute to.
     *
     * @return string
     */
    public static function getExtensionName()
    {
        return SysteminfoInterface::STR_EXTENSION_POINT;
    }

}
