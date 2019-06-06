<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Devops\System;


use Kajona\System\System\Carrier;
use Kajona\System\System\Config;
use Kajona\System\System\SysteminfoInterface;

/**
 * General information regarding the current database environment
 *
 * @author sidler@mulchprod.de
 * @since 4.5
 */
class DevopsPluginDb implements SysteminfoInterface
{
    /**
     * Returns the title of the info-block
     *
     * @return string
     */
    public function getStrTitle()
    {
        return Carrier::getInstance()->getObjLang()->getLang("db", "devops");
    }

    /**
     * @inheritDoc
     */
    public function getArrContent($mediaType = self::TYPE_HTML)
    {
        $objLang = Carrier::getInstance()->getObjLang();
        $arrReturn = array();

        $arrTables = Carrier::getInstance()->getObjDB()->getTables();
        $arrReturn[] = array($objLang->getLang("datenbanktreiber", "devops"), Config::getInstance()->getConfig("dbdriver"));
        $arrReturn[] = array($objLang->getLang("datenbankserver", "devops"), Config::getInstance()->getConfig("dbhost"));
        $arrReturn[] = array($objLang->getLang("db", "devops"), Config::getInstance()->getConfig("dbname"));
        $arrReturn[] = array($objLang->getLang("anzahltabellen", "devops"), count($arrTables));

        $arrInfo = Carrier::getInstance()->getObjDB()->getDbInfo();
        foreach ($arrInfo as $strKey => $strValue) {
            $arrReturn[] = array($strKey, $strValue);
        }


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
