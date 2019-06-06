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
 * General information regarding the current php environment
 *
 * @package module_system
 * @author andrii.konoval@artemeon.de
 * @since 7.1
 */
class DevopsPluginMail implements SysteminfoInterface
{
    /**
     * Returns the title of the info-block
     *
     * @return string
     */
    public function getStrTitle()
    {
        return Carrier::getInstance()->getObjLang()->getLang("smtp_config", "devops");
    }

    /**
     * @inheritDoc
     */
    public function getArrContent($mediaType = self::TYPE_HTML)
    {

        $objLang = Carrier::getInstance()->getObjLang();
        $cfg = Config::getInstance("module_system", "mail.php");

        $arrReturn = array();

        $smtpIsEnabled = $cfg->getConfig('smtp_enabled');
        $arrReturn[] = array(
            $objLang->getLang("smtp_enabled", "devops"),
            Carrier::getInstance()->getObjLang()->getLang(!empty($smtpIsEnabled) ? "commons_yes" : "commons_no", "devops")
        );
        $arrReturn[] = array($objLang->getLang("smtp_host", "devops"), $cfg->getConfig('smtp_host'));
        $arrReturn[] = array($objLang->getLang("smtp_port", "devops"), $cfg->getConfig('smtp_port'));
        $arrReturn[] = array($objLang->getLang("smtp_encryption", "devops"), $cfg->getConfig('smtp_encryption'));
        $arrReturn[] = array($objLang->getLang("smtp_debug", "devops"), $cfg->getConfig('smtp_debug'));

        $smtpIsAuthEnabled = $cfg->getConfig('smtp_auth_enabled');
        $arrReturn[] = array(
            $objLang->getLang("smtp_auth_enabled", "devops"),
            Carrier::getInstance()->getObjLang()->getLang(!empty($smtpIsAuthEnabled) ? "commons_yes" : "commons_no", "devops")
        );
        $arrReturn[] = array($objLang->getLang("smtp_auth_username", "devops"), $cfg->getConfig('smtp_auth_username'));
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
