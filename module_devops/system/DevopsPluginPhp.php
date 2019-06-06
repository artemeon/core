<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Devops\System;


use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\SysteminfoInterface;

/**
 * General information regarding the current php environment
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 4.5
 */
class DevopsPluginPhp implements SysteminfoInterface
{
    /**
     * Returns the title of the info-block
     *
     * @return string
     */
    public function getStrTitle()
    {
        return Carrier::getInstance()->getObjLang()->getLang("php", "devops");
    }

    /**
     * @inheritDoc
     */
    public function getArrContent($mediaType = self::TYPE_HTML)
    {

        $objLang = Carrier::getInstance()->getObjLang();

        $arrReturn = array();
        $arrReturn[] = array($objLang->getLang("version", "devops"), phpversion());
        $arrReturn[] = array($objLang->getLang("geladeneerweiterungen", "devops"), implode(", ", get_loaded_extensions()));
        $arrReturn[] = array($objLang->getLang("executiontimeout", "devops"), Carrier::getInstance()->getObjConfig()->getPhpIni("max_execution_time")."s");
        $arrReturn[] = array($objLang->getLang("inputtimeout", "devops"), Carrier::getInstance()->getObjConfig()->getPhpIni("max_input_time")."s");
        $arrReturn[] = array($objLang->getLang("memorylimit", "devops"), bytesToString(ini_get("memory_limit"), true));
        $arrReturn[] = array($objLang->getLang("errorlevel", "devops"), Carrier::getInstance()->getObjConfig()->getPhpIni("error_reporting"));
        $arrReturn[] = array($objLang->getLang("systeminfo_php_safemode", "devops"), (ini_get("safe_mode") ? $objLang->getLang("commons_yes", "devops") : $objLang->getLang("commons_no", "devops")));
        $arrReturn[] = array($objLang->getLang("systeminfo_php_urlfopen", "devops"), (ini_get("allow_url_fopen") ? $objLang->getLang("commons_yes", "devops") : $objLang->getLang("commons_no", "devops")));
        $arrReturn[] = array($objLang->getLang("systeminfo_php_regglobal", "devops"), (ini_get("register_globals") ? $objLang->getLang("commons_yes", "devops") : $objLang->getLang("commons_no", "devops")));
        $arrReturn[] = array($objLang->getLang("postmaxsize", "devops"), bytesToString(ini_get("post_max_size"), true));
        $arrReturn[] = array($objLang->getLang("uploadmaxsize", "devops"), bytesToString(ini_get("upload_max_filesize"), true));
        $arrReturn[] = array($objLang->getLang("uploads", "devops"), (Carrier::getInstance()->getObjConfig()->getPhpIni("file_uploads") == 1 ? $objLang->getLang("commons_yes", "devops") : $objLang->getLang("commons_no", "devops")));
        $arrReturn[] = array($objLang->getLang("timezone", "devops"), date_default_timezone_get());
        $arrReturn[] = array($objLang->getLang("datekajona", "devops"), dateToString(new Date()));

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
