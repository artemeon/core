<?php

/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/


namespace Kajona\Debugging\Debug;

use Kajona\System\System\Carrier;
use Kajona\System\System\Classloader;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\RequestEntrypointEnum;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\SystemEventidentifier;

class DebugHelper
{

    private $arrTimestampStart = null;

    function __construct()
    {
        $this->arrTimestampStart = gettimeofday();

        ResponseObject::getInstance()->setObjEntrypoint(RequestEntrypointEnum::DEBUG());
    }


    public function debugHelper()
    {
        echo "<pre>";
        echo "<b>Kajona Core V7 Debug Subsystem</b>\n\n";

        if (getGet("debugfile") != "") {


            echo "Loading path for ".getGet("debugfile")."\n";
            $strPath = array_search(getGet("debugfile"), Resourceloader::getInstance()->getFolderContent("/debug", array(".php")));
            if ($strPath !== false) {
                echo "Passing request to ".$strPath."\n\n";
                include $strPath;
            }

        }
        else {
            echo "Searching for debug-scripts available...\n";

            $arrFiles = \Kajona\System\System\Resourceloader::getInstance()->getFolderContent("/debug", array(".php"));

            echo "<ul>";
            foreach ($arrFiles as $strPath => $strOneFile) {
                echo "<li><a href='?debugfile=".$strOneFile."' >".$strOneFile."</a> <br />".$strPath."</li>";
            }

            echo "</ul>";
        }


        $arrTimestampEnde = gettimeofday();
        $intTimeUsed = (($arrTimestampEnde['sec'] * 1000000 + $arrTimestampEnde['usec'])
                - ($this->arrTimestampStart['sec'] * 1000000 + $this->arrTimestampStart['usec'])) / 1000000;


        echo "\n\n<b>PHP-Time:</b>                              ".number_format($intTimeUsed, 6)." sec \n";
        echo "<b>Queries db/cachesize/cached/fired:</b>     ".Carrier::getInstance()->getObjDB()->getNumber()."/".
            Carrier::getInstance()->getObjDB()->getCacheSize()."/".
            Carrier::getInstance()->getObjDB()->getNumberCache()."/".
            (Carrier::getInstance()->getObjDB()->getNumber() - Carrier::getInstance()->getObjDB()->getNumberCache())."\n";

        echo "<b>Memory/Max Memory:</b>                     ".bytesToString(memory_get_usage())."/".bytesToString(memory_get_peak_usage())." \n";
        echo "<b>Classes Loaded:</b>                        ".Classloader::getInstance()->getIntNumberOfClassesLoaded()." \n";

        echo "</pre>";

    }

}

header("Content-Type: text/html; charset=utf-8");

$objDebug = new DebugHelper();
$objDebug->debugHelper();

CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_ENDPROCESSING, array(RequestEntrypointEnum::DEBUG()));
CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_AFTERCONTENTSEND, array(RequestEntrypointEnum::DEBUG()));