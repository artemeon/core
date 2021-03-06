<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System\Scriptlets;

use Kajona\System\System\ScriptletInterface;
use Kajona\System\System\SystemSetting;

/**
 * General replacement of global constants such as the webpath
 *
 * @since 4.0
 * @author sidler@mulchprod.de
 */
class ScriptletXConstants implements ScriptletInterface
{

    /**
     * Processes the content.
     * Make sure to return the string again, otherwise the output will remain blank.
     *
     * @param string $strContent
     *
     * @return string
     */
    public function processContent($strContent)
    {

        $arrConstants = array(
            "_indexpath_",
            "_webpath_",
            "_system_browser_cachebuster_",
            "_gentime_"
        );
        $arrValues = array(
            _indexpath_,
            _webpath_,
            SystemSetting::getConfigValue("_system_browser_cachebuster_"),
            date("d.m.y H:i", time())
        );

        if (defined("_skinwebpath_")) {
            $arrConstants[] = "_skinwebpath_";
            $arrValues[] = _skinwebpath_;
        }


        return str_replace($arrConstants, $arrValues, $strContent);
    }

    /**
     * Define the context the scriptlet is applied to.
     * A combination of contexts is allowed using an or-concatenation.
     * Examples:
     *   return ScriptletInterface::BIT_CONTEXT_ADMIN
     *   return ScriptletInterface::BIT_CONTEXT_ADMIN | ScriptletInterface::BIT_CONTEXT_PORTAL_ELEMENT
     *
     * @return mixed
     */
    public function getProcessingContext()
    {
        return ScriptletInterface::BIT_CONTEXT_PORTAL_PAGE | ScriptletInterface::BIT_CONTEXT_ADMIN;
    }

}
