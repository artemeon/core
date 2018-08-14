<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;


/**
 * A small class to provide access to cookies, both ways, reading as writing.
 * Use this class ONLY to access / set cookies!
 *
 * @package module_system
 * @author sidler@mulchprod.de
 */
class Cookie
{


    /**
     * Sends a cookie to the browser
     *
     * @param string $strName
     * @param string $strValue
     * @param int $intTime
     *
     * @param bool $httpOnly
     * @return bool
     */
    public function setCookie($strName, $strValue, $intTime = 0, $httpOnly = true)
    {
        //cookie is 30 days valid
        if ($intTime == 0) {
            $intTime = time() + 60 * 60 * 24 * 30;
        }

        $strPath = preg_replace('#http(s?)://'.getServer("HTTP_HOST").'#i', '', _webpath_);
        if ($strPath == "" || $strPath[0] != "/") {
            $strPath = "/".$strPath;
        }

        return setcookie($strName, $strValue, $intTime, $strPath, null, SystemSetting::getConfigValue("_cookies_only_https_") == "true", $httpOnly);
    }

    /**
     * Gets the value of a cookie
     *
     * @param string $strName
     *
     * @return mixed
     */
    public function getCookie($strName)
    {
        return getCookie($strName);
    }

}

