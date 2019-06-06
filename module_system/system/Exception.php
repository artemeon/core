<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                                        *
********************************************************************************************************/

namespace Kajona\System\System;

use Kajona\System\System\Messageproviders\MessageproviderExceptions;


/**
 * This is the common exception to inherit or to throw in the code.
 * Please DO NOT throw a "plain" exception, otherwise logging and error-handling
 * will not work properly!
 *
 * @package module_system
 */
class Exception extends \Exception
{

    /**
     * This level is for common errors happening from time to time ;)
     *
     * @var int
     * @static
     */
    public static $level_ERROR = 1;

    /**
     * Level for really heavy errors. Hopefully not happening that often...
     *
     * @var int
     * @static
     */
    public static $level_FATALERROR = 2;

    private $intErrorlevel;
    private $intDebuglevel;

    /**
     * @param string $strError
     * @param int $intErrorlevel
     * @param Exception|null $objPrevious
     */
    public function __construct($strError, $intErrorlevel = 1, \Throwable $objPrevious = null)
    {
        parent::__construct($strError, 0, $objPrevious);
        $this->intErrorlevel = $intErrorlevel;

        //decide, what to print --> get config-value
        // 0: fatal errors will be displayed
        // 1: fatal and regular errors will be displayed
        $this->intDebuglevel = Carrier::getInstance()->getObjConfig()->getDebug("debuglevel");
    }


    /**
     * Used to handle the current exception.
     * Decides, if the execution should be stopped, or continued.
     * Therefore the errorlevel defines the "weight" of the exception
     *
     * @return void
     */
    public function processException()
    {

        //set which POST parameters should read out
        $arrPostParams = array("module", "action", "page", "systemid");

        $objHistory = new History();

        //send an email to the admin?
        $strAdminMail = "";
        try {
            if (Database::getInstance()->getBitConnected()) {
                $strAdminMail = SystemSetting::getConfigValue("_system_admin_email_");
            }
        } catch (Exception $objEx) {
        }

        if ($strAdminMail != "") {
            $strMailtext = "";
            $strMailtext .= "The system installed at "._webpath_." registered an error!\n\n";
            $strMailtext .= "The error message was:\n";
            $strMailtext .= "\t".$this->getMessage()."\n\n";
            $strMailtext .= "The level of this error was:\n";
            $strMailtext .= "\t";
            if ($this->getErrorlevel() == self::$level_FATALERROR) {
                $strMailtext .= "FATAL ERROR";
            }
            if ($this->getErrorlevel() == self::$level_ERROR) {
                $strMailtext .= "REGULAR ERROR";
            }
            $strMailtext .= "\n\n";
            $strMailtext .= "File and line number the error was thrown:\n";
            $strMailtext .= "\t".basename($this->getFile())." in line ".$this->getLine()."\n\n";
            $strMailtext .= "Callstack / Backtrace:\n\n";
            $strMailtext .= $this->getTraceAsString();
            $previous = $this->getPrevious();
            while ($previous !== null) {
                $strMailtext .= "\n\nPrevious Exception:\n\n";
                $strMailtext .= "\t".basename($previous->getFile())." in line ".$previous->getLine()."\n\n";
                $strMailtext .= $previous->getTraceAsString();
                $previous = $previous->getPrevious();
            }
            $strMailtext .= "\n\n";
            $strMailtext .= "User: ".Carrier::getInstance()->getObjSession()->getUserID()." (".Carrier::getInstance()->getObjSession()->getUsername().")\n";
            $strMailtext .= "Source host: ".getServer("REMOTE_ADDR")." (".@gethostbyaddr(getServer("REMOTE_ADDR")).")\n";
            $strMailtext .= "Query string: ".getServer("REQUEST_URI")."\n";
            $strMailtext .= "POST data (selective):\n";
            foreach ($arrPostParams as $strParam) {
                if (getPost($strParam) != "") {
                    $strMailtext .= "\t".$strParam.": ".getPost($strParam)."\n";
                }
            }
            $strMailtext .= "\n\n";
            $strMailtext .= "Last actions called:\n";
            $strMailtext .= "Admin:\n";
            $arrHistory = $objHistory->getArrAdminHistory();
            if (is_array($arrHistory)) {
                foreach ($arrHistory as $intIndex => $strOneUrl) {
                    $strMailtext .= " #".$intIndex.": ".$strOneUrl."\n";
                }
            }
            $strMailtext .= "Portal:\n";
            $arrHistory = $objHistory->getArrPortalHistory();
            if (is_array($arrHistory)) {
                foreach ($arrHistory as $intIndex => $strOneUrl) {
                    $strMailtext .= " #".$intIndex.": ".$strOneUrl."\n";
                }
            }
            $strMailtext .= "\n\n";

            $strMailtext .= "Callstack:\n";
            $strMailtext .= $this->getTraceAsString();
            $strMailtext .= "\n\n";


            $strMailtext .= "If you don't know what to do, feel free to open a ticket.\n\n";
            $strMailtext .= "For more help visit http://www.kajona.de.\n\n";

            $objMail = new Mail();
            $objMail->setSubject("Error on website "._webpath_." occured!");
            $objMail->setSender($strAdminMail);
            $objMail->setText($strMailtext);
            $objMail->addTo($strAdminMail);
            $objMail->sendMail();


            $objMessageHandler = new MessagingMessagehandler();
            $objMessage = new MessagingMessage();
            $objMessage->setStrBody($strMailtext);
            $objMessage->setObjMessageProvider(new MessageproviderExceptions());
            $objMessageHandler->sendMessageObject($objMessage, Objectfactory::getInstance()->getObject(SystemSetting::getConfigValue("_admins_group_id_")));
        }


        //Handle  errors.
        $strLogMessage = basename($this->getFile()).":".$this->getLine()." -- ".$this->getMessage();
        Logger::getInstance()->error($strLogMessage);
    }

    /**
     * Renders the passed exception, either using the xml channes or using the web channel
     *
     * @param Exception $objException
     *
     * @return string
     */
    public static function renderException(Exception $objException)
    {
        if (ResponseObject::getInstance()->getObjEntrypoint()->equals(RequestEntrypointEnum::XML())) {
            $strErrormessage = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $strErrormessage .= "<error>".xmlSafeString($objException->getMessage())."</error>";
        } else {
            $strErrormessage = "<div class=\"alert alert-danger\" role=\"alert\">\n";
            $strErrormessage .= "<p>An error occurred:<br><b>".(htmlspecialchars($objException->getMessage(), ENT_QUOTES, "UTF-8", false))."</b></p>";

            if ($objException->intErrorlevel == Exception::$level_FATALERROR || Session::getInstance()->isSuperAdmin()) {
                $trace = basename($objException->getFile())." in line ".$objException->getLine()."\n";
                $trace .= $objException->getTraceAsString();
                $previous = $objException->getPrevious();
                while ($previous !== null) {
                    $trace .= "\nPrevious Exception:\n";
                    $trace .= basename($previous->getFile())." in line ".$previous->getLine()."\n\n";
                    $trace .= $previous->getTraceAsString();
                    $previous = $previous->getPrevious();
                }
                $strErrormessage .= "<br><p><pre style='font-size:12px;'>Stacktrace:\n".(htmlspecialchars($trace, ENT_QUOTES, "UTF-8", false))."</pre></p>";
            }

            $strErrormessage .= "<br><p>Please contact the system admin</p>";
            $strErrormessage .= "</div>";
        }

        return $strErrormessage;
    }




    /**
     * This method is called, if an exception was thrown in the code but not caught
     * by an try-catch block.
     *
     * @param Exception $objException
     *
     * @return void
     */
    public static function globalExceptionHandler($objException)
    {
        if (!($objException instanceof Exception)) {
            $objException = new Exception((string)$objException);
        }
        $objException->processException();
        ResponseObject::getInstance()->sendHeaders();

        // in this case we simply render the exception
        echo self::renderException($objException);
    }

    /**
     * @return int
     */
    public function getErrorlevel()
    {
        return $this->intErrorlevel;
    }

    /**
     * @param int $intErrorlevel
     *
     * @return void
     */
    public function setErrorlevel($intErrorlevel)
    {
        $this->intErrorlevel = $intErrorlevel;
    }

    /**
     * @param string $intDebuglevel
     *
     * @return void
     */
    public function setIntDebuglevel($intDebuglevel)
    {
        $this->intDebuglevel = $intDebuglevel;
    }

    /**
     * @return string
     */
    public function getIntDebuglevel()
    {
        return $this->intDebuglevel;
    }

}
