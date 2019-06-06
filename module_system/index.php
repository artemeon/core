<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                                                   *
********************************************************************************************************/

namespace Kajona\System;

//Determing the area to load
use Kajona\System\System\Carrier;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\HttpResponsetypes;
use Kajona\System\System\Lang;
use Kajona\System\System\RequestDispatcher;
use Kajona\System\System\RequestEntrypointEnum;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\ServiceProvider;
use Kajona\System\System\Session;
use Kajona\System\System\SystemEventidentifier;

define("_autotesting_", false);


/**
 * Wrapper class to centralize a method within its namespace
 *
 * @package module_system
 */
class Index
{

    /**
     * @var ResponseObject
     */
    public $objResponse;

    /**
     * @var \Kajona\System\System\ObjectBuilder
     */
    public $objBuilder;

    /**
     * Triggers the processing of the current request
     *
     * @return void
     */
    public function processRequest()
    {
        $strModule = Carrier::getInstance()->getParam("module");
        $strAction = Carrier::getInstance()->getParam("action");

        $this->objResponse = ResponseObject::getInstance();
        $this->objResponse->setStrResponseType(HttpResponsetypes::STR_TYPE_HTML);
        $this->objResponse->setObjEntrypoint(RequestEntrypointEnum::INDEX());

        $this->objBuilder = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_OBJECT_BUILDER);

        if (is_file(_realpath_."/kajona.lock") && !Session::getInstance()->isSuperAdmin()) {
            $waitMessage = Lang::getInstance()->getLang("update_in_progress", "system");
            die($this->getLockTemplate($waitMessage));
        }

        $objDispatcher = new RequestDispatcher($this->objResponse, $this->objBuilder);
        $objDispatcher->processRequest($strModule, $strAction);
    }

    /**
     * @param string $message
     * @return string
     */
    private function getLockTemplate($message)
    {
        return <<<HTML
<!doctype html>
<html>
<head>
<title>{$message}</title>
</head>
<body>
<div style="text-align:center;margin-top:128px;">
<img src="data:image/png;base64,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">
<h1>{$message}</h1>
<small><a href="https://www.artemeon.de/">www.artemeon.de</a></small>
</div>
</body>
</html>
HTML;
    }
}


//creating the wrapper instance and passing control
$objIndex = new Index();
$objIndex->processRequest();
$objIndex->objResponse->sendHeaders();
$objIndex->objResponse->sendContent();
CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_AFTERCONTENTSEND, array(RequestEntrypointEnum::INDEX()));

