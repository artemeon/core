<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/


// -- The Path on the filesystem ---------------------------------------------------------------------------------------
// Determine the current path on the filesystem. Use the dir-name of the current file minus core/module_system
if (substr(__DIR__, 0, 7) == "phar://") {
    define("_realpath_", str_replace(array(" ", "\\"), array("\040", "/"), substr(__DIR__, 7, -23)));
}
else {
    define("_realpath_", str_replace(array(" ", "\\"), array("\040", "/"), substr(__DIR__, 0, -18)));
}

// -- Loader pre-configuration -----------------------------------------------------------------------------------------
if (!defined("_xmlLoader_")) {
    /** @depreacted use ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::XML()) instead */
    define("_xmlLoader_", false);
}

// -- Settings ---------------------------------------------------------------------------------------------------------
// Setting up the default timezone, determined by the server / environment. may be redefined by _system_timezone_
date_default_timezone_set(date_default_timezone_get());

// -- Composer Autoloader ----------------------------------------------------------------------------------------------
// We always include the composer autoloader
require_once __DIR__ . '/../../project/vendor/autoload.php';

// -- Include core files -----------------------------------------------------------------------------------------------
// Functions to have fun & check for mb-string
require_once __DIR__ . '/system/functions.php';

// -- Exception handler ------------------------------------------------------------------------------------------------
// Register global exception handler for exceptions thrown but not catched (bad style ;) )
set_exception_handler(array(\Kajona\System\System\Exception::class, 'globalExceptionHandler'));

// -- The Path on web --------------------------------------------------------------------------------------------------
defineWebPath();

// -- Include needed classes of each module ----------------------------------------------------------------------------
// This registers all service providers of each module
\Kajona\System\System\Classloader::getInstance()->registerModuleServices(\Kajona\System\System\Carrier::getInstance()->getContainer());
//scan module ids
\Kajona\System\System\Classloader::getInstance()->bootstrapIncludeModuleIds();

// Now we include all classes which i.e. register event listeners
\Kajona\System\System\Classloader::getInstance()->includeClasses();

// -- Trigger the phar-extractor ---------------------------------------------------------------------------------------
\Kajona\System\System\PharModuleExtractor::bootstrapPharContent();

// Define web path
function defineWebPath()
{
    require_once __DIR__."/system/Config.php";
    $strHeaderValue = strtolower(\Kajona\System\System\Config::readPlainConfigsFromFilesystem("https_header_value"));
    $arrHeaderNames = \Kajona\System\System\Config::readPlainConfigsFromFilesystem("https_header");
    if (!is_array($arrHeaderNames)) {
        $arrHeaderNames = array($arrHeaderNames);
    }

    $bitIsHttps = false;
    foreach ($arrHeaderNames as $strOneName) {
        if (isset($_SERVER[$strOneName]) && (strtolower($_SERVER[$strOneName]) == $strHeaderValue)) {
            $bitIsHttps = true;
            break;
        }
    }

    if (!defined("_webpath_")) {
        if (strpos($_SERVER['SCRIPT_FILENAME'], "/debug/")) {
            //Determine the current path on the web
            $strWeb = dirname(($bitIsHttps ? "https://" : "http://").$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
            define("_webpath_", saveUrlEncode(substr_replace($strWeb, "", strrpos($strWeb, "/"))));
        } else {
            //Determine the current path on the web
            $strWeb = dirname(($bitIsHttps ? "https://" : "http://").(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "localhost").$_SERVER['SCRIPT_NAME']);
            define("_webpath_", saveUrlEncode($strWeb));
        }
    }
}

