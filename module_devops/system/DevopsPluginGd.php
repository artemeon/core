<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Devops\System;


use Kajona\System\System\Carrier;
use Kajona\System\System\SysteminfoInterface;

/**
 * General information regarding the current gd lib environment
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 4.5
 */
class DevopsPluginGd implements SysteminfoInterface
{

    /**
     * Returns the title of the info-block
     *
     * @return string
     */
    public function getStrTitle()
    {
        return Carrier::getInstance()->getObjLang()->getLang("gd", "devops");
    }

    /**
     * @inheritDoc
     */
    public function getArrContent($mediaType = self::TYPE_HTML)
    {
        $objLang = Carrier::getInstance()->getObjLang();
        $arrReturn = array();

        if (function_exists("gd_info")) {
            $arrGd = gd_info();
            $arrReturn[] = array($objLang->getLang("version", "devops"), $arrGd["GD Version"]);
            $arrReturn[] = array($objLang->getLang("gifread", "devops"), (isset($arrGd["GIF Read Support"]) && $arrGd["GIF Read Support"] ? Carrier::getInstance()->getObjLang()->getLang("commons_yes", "devops") : Carrier::getInstance()->getObjLang()->getLang("commons_no", "devops")));
            $arrReturn[] = array($objLang->getLang("gifwrite", "devops"), (isset($arrGd["GIF Create Support"]) && $arrGd["GIF Create Support"] ? Carrier::getInstance()->getObjLang()->getLang("commons_yes", "devops") : Carrier::getInstance()->getObjLang()->getLang("commons_no", "devops")));
            $arrReturn[] = array($objLang->getLang("jpg", "devops"), (((isset($arrGd["JPG Support"]) && $arrGd["JPG Support"]) || (isset($arrGd["JPEG Support"]) && $arrGd["JPEG Support"])) ? Carrier::getInstance()->getObjLang()->getLang("commons_yes", "devops") : Carrier::getInstance()->getObjLang()->getLang("commons_no", "devops")));
            $arrReturn[] = array($objLang->getLang("png", "devops"), (isset($arrGd["PNG Support"]) && $arrGd["PNG Support"] ? Carrier::getInstance()->getObjLang()->getLang("commons_yes", "devops") : Carrier::getInstance()->getObjLang()->getLang("commons_no", "devops")));
        } else {
            $arrReturn[] = array("", Carrier::getInstance()->getObjLang()->getLang("keinegd", "devops"));
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
