<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *    $Id$                                                      *
 ********************************************************************************************************/
namespace Kajona\System;

//Determine the area to load
use Kajona\System\System\Carrier;
use Kajona\System\System\Config;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\HttpResponsetypes;
use Kajona\System\System\HttpStatuscodes;
use Kajona\System\System\RequestDispatcher;
use Kajona\System\System\RequestEntrypointEnum;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\ServiceProvider;
use Kajona\System\System\SystemEventidentifier;

define("_autotesting_", false);

/**
 * Class handling all requests to be served with xml
 *
 * @package module_system
 */
class Xml
{

    private static $bitRenderXmlHeader = true;

    /**
     * @var ResponseObject
     */
    public $objResponse;

    /**
     * @var \Kajona\System\System\ObjectBuilder
     */
    public $objBuilder;

    /**
     * Starts the processing of the requests, fetches params and passes control to the request dispatcher
     *
     * @return void
     */
    public function processRequest()
    {

        $strModule = Carrier::getInstance()->getParam("module");
        $strAction = Carrier::getInstance()->getParam("action");

        $this->objResponse = ResponseObject::getInstance();
        $this->objResponse->setStrResponseType(HttpResponsetypes::STR_TYPE_XML);
        $this->objResponse->setStrStatusCode(HttpStatuscodes::SC_OK);
        $this->objResponse->setObjEntrypoint(RequestEntrypointEnum::XML());

        $origin = Config::getInstance()->getConfig("header_cors_origin");
        if (!empty($origin)) {
            $this->objResponse->addHeader("Access-Control-Allow-Origin: " . $origin);
            $this->objResponse->addHeader("Access-Control-Allow-Methods: GET, POST, PUT, DELETE , OPTIONS");
            $this->objResponse->addHeader("Access-Control-Allow-Headers: Authorization , Content-Type");
        }

        if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_OK);
            return;
        }

        // in case for options requests i.e. preflight requests we always want to set an ok status code otherwise the
        // browser will deny the request
        if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_OK);
            return;
        }

        //only allowed with a module definition. if not given skip, so that there's no exception thrown
        if (empty($strModule)) {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_BADREQUEST);
            $this->objResponse->setStrContent("<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<error>module missing</error>");
            return;
        }

        if (Carrier::getInstance()->getParam("contentFill") == 1) {
            $this->objResponse->setStrResponseType(HttpResponsetypes::STR_TYPE_HTML);
        }

        $this->objBuilder = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_OBJECT_BUILDER);

        $objDispatcher = new RequestDispatcher($this->objResponse, $this->objBuilder);
        $objDispatcher->processRequest($strModule, $strAction);

        if ($this->objResponse->getStrContent() == "") {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_BADREQUEST);
            $this->objResponse->setStrContent("<error>An error occurred, malformed request</error>");
        }

        if ($this->objResponse->getStrResponseType() == HttpResponsetypes::STR_TYPE_XML && self::$bitRenderXmlHeader) {
            $this->objResponse->setStrContent("<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n" . $this->objResponse->getStrContent());
        }
    }

    /**
     * If set to true, the output will be sent without the mandatory xml-head-element
     *
     * @deprecated
     * @param bool $bitSuppressXmlHeader
     * @return void
     */
    public static function setBitSuppressXmlHeader($bitSuppressXmlHeader)
    {
        self::$bitRenderXmlHeader = !$bitSuppressXmlHeader;
    }

}

//pass control
$objXML = new Xml();
$objXML->processRequest();
$objXML->objResponse->sendHeaders();
$objXML->objResponse->sendContent();
CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_AFTERCONTENTSEND, array(RequestEntrypointEnum::XML()));
