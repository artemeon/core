<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                               *
********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Kajona\System\System\Validators\EmailValidator;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * This class can be used to generate and send emails
 * This class is able to send plaintext mails, html mails, mails with attachments and variations
 * of these. To send a mail, a call could be
 * $objMail = new Mail();
 * $objMail->setSender("test@kajona.de");
 * $objMail->setSenderName("Kajona System");
 * $objMail->addTo("sidler@localhost");
 * $objMail->setSubject("Kajona test mail");
 * $objMail->setText("This is the plain text");
 * $objMail->setHtml("This is<br />the <b>html-content</b><br /><img src=\"cid:kajona_poweredby.png\" />");
 * $objMail->addAttachement("/portal/pics/kajona/login_logo.gif");
 * $objMail->addAttachement("/portal/pics/kajona/kajona_poweredby.png", "", true);
 * $objMail->sendMail();
 *
 * Internally, the class uses PHPMailer since 7.0 in order to provide full SMTP support
 *
 * @author sidler@mulchprod.de
 */
class Mail
{
    private $arrayTo = array();
    private $arrayCc = array();
    private $arrayBcc = array();

    private $strSender = "";
    private $strSenderName = "";
    private $strSubject = "";
    private $strText = "";
    private $strHtml = "";
    private $arrFiles = array();

    /**
     * Adds a recipient to the to-list
     *
     * @param string $strMailaddress
     *
     * @return void
     */
    public function addTo(string $strMailaddress)
    {
        $this->arrayTo[] = $strMailaddress;
    }

    /**
     * Adds a recipient to the cc-list
     *
     * @param string $strMailaddress
     *
     * @return void
     */
    public function addCc(string $strMailaddress)
    {
        $this->arrayCc[] = $strMailaddress;
    }

    /**
     * Adds a recipient to the bcc-list
     *
     * @param string $strMailaddress
     *
     * @return void
     */
    public function addBcc(string $strMailaddress)
    {
        $this->arrayBcc[] = $strMailaddress;
    }

    /**
     * Sets the text-content for the mail
     *
     * @param string $strText
     *
     * @return void
     */
    public function setText(string $strText)
    {
        $this->strText = html_entity_decode($strText);
    }

    /**
     * Sets the html-content for the mail
     *
     * @param string $strHtml
     *
     * @return void
     */
    public function setHtml(string $strHtml)
    {
        $this->strHtml = $strHtml;
    }

    /**
     * Sets the subject of the mail
     *
     * @param string $strSubject
     *
     * @return void
     */
    public function setSubject(string $strSubject)
    {
        $this->strSubject = str_replace(array("\r", "\n"), array(" ", " "), $strSubject);
    }

    /**
     * Sets the sender of the mail
     *
     * @param string $strSender
     *
     * @return void
     */
    public function setSender(string $strSender)
    {
        $this->strSender = $strSender;
    }

    /**
     * Sets the name of the mails sender
     *
     * @param string $strSenderName
     *
     * @return void
     */
    public function setSenderName(string $strSenderName)
    {
        $this->strSenderName = $strSenderName;
    }

    /**
     * Adds a file to the current mail
     * If no mimetype is given, the system tries to lookup the mimetype itself.
     * Use $bitInline if the attachment should not appear in the list of attachments in the mail client.
     * Inline-attachments can be used in html-emails like <img src="cid:your-filename.jpg" />
     *
     * @param string $strFilename
     * @param string $strContentType
     * @param bool $bitInline
     *
     * @return bool
     */
    public function addAttachement(string $strFilename, string $strContentType = "", bool $bitInline = false): bool
    {
        $strFilename = StringUtil::replace(_realpath_, '', $strFilename);
        if (is_file(_realpath_.$strFilename)) {
            $arrTemp = array();
            $arrTemp["filename"] = _realpath_.$strFilename;
            //content-type given?
            if ($strContentType == "") {
                //try to find out
                $objToolkit = new Toolkit();
                $arrMime = $objToolkit->mimeType($strFilename);
                $arrTemp["mimetype"] = $arrMime[0];
            }

            //attach as inline-attachment?
            $arrTemp["inline"] = $bitInline;

            $this->arrFiles[] = $arrTemp;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Tries to resolve the sender, either by using the set sender or by overwriting wiht system defaults
     * @param string $sender
     * @return string
     * @throws Exception
     */
    private function getResolvedSender(string $sender): string
    {
        if ($sender == "") {
            //try to load the current users' mail adress
            /** @var UserUser $objSenderUser */
            $objSenderUser = Carrier::getInstance()->getObjSession()->getUser();
            $validator = new EmailValidator();
            if ($objSenderUser !== null) {
                if ($validator->validate($objSenderUser->getStrEmail())) {
                    $sender = $objSenderUser->getStrEmail();
                }
            }

        }

        if ($sender == "" || SystemSetting::getConfigValue("_system_email_forcesender_") == "true") {
            $sender = SystemSetting::getConfigValue("_system_email_defaultsender_");
        }

        return $sender;
    }

    /**
     * Sends, finally, the mail
     *
     * @return bool
     * @throws Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendMail(): bool
    {
        if (count($this->arrayTo) == 0) {
            return false;
        }

        //generate a PHPMailer E-mail
        $objMail = new PHPMailer(true);
        $objMail->Debugoutput = Logger::getInstance("mail.log");
        $objMail->XMailer = 'ARTEMEON Core';
        $objMail->CharSet = 'UTF-8';

        $cfg = Config::getInstance("module_system", "mail.php");

        //set a possible imap config
        if ($cfg->getConfig('smtp_enabled') === true) {
            $objMail->SMTPDebug = $cfg->getConfig('smtp_debug');

            $objMail->isSMTP();
            $objMail->Host = $cfg->getConfig('smtp_host');
            $objMail->Port = $cfg->getConfig('smtp_port');
            $objMail->SMTPSecure = $cfg->getConfig('smtp_encryption');
            $objMail->SMTPAutoTLS = $cfg->getConfig('smtp_auto_tls');

            if ($cfg->getConfig('smtp_auth_enabled') === true) {
                $objMail->SMTPAuth = true;
                $objMail->Username = $cfg->getConfig('smtp_auth_username');
                $objMail->Password = $cfg->getConfig('smtp_auth_password');
            }

        }

        $objMail->setFrom($this->getResolvedSender($this->strSender), $this->strSenderName);

        foreach ($this->arrayTo as $to) {
            $objMail->addAddress($to);
        }
        foreach ($this->arrayCc as $cc) {
            $objMail->addCC($cc);
        }
        foreach ($this->arrayBcc as $bcc) {
            $objMail->addBCC($bcc);
        }

        foreach ($this->arrFiles as $arrOneFile) {
            $objMail->addAttachment($arrOneFile["filename"], basename($arrOneFile["filename"]), 'base64', $arrOneFile["mimetype"], $arrOneFile["inline"] === true ? 'inline' : 'attachment');
        }

        $objMail->Subject = $this->strSubject;
        if (!empty($this->strHtml)) {
            $objMail->isHTML(true);
            $objMail->Body = $this->strHtml;
            $objMail->AltBody = strip_tags(($this->strText == "" ? str_replace(array("<br />", "<br>"), array("\n", "\n"), $this->strHtml) : $this->strText));
        } else {
            $objMail->isHTML(false);
            $objMail->Body = $this->strText;
        }

        Logger::getInstance('mail.log')->info("sending mail to ".implode(", ", $this->arrayTo));

        if ($cfg->getConfig('mail_copy2file')) {
            $strDebug  = "To:        ".implode(", ", $this->arrayTo).PHP_EOL;
            $strDebug .= "CC:        ".implode(", ", $this->arrayCc).PHP_EOL;
            $strDebug .= "BCC:       ".implode(", ", $this->arrayBcc).PHP_EOL;
            $strDebug .= "Subject:   ".$objMail->Subject.PHP_EOL;
            $strDebug .= "Body:      ".PHP_EOL.$objMail->Body.PHP_EOL;

            $filesystem = new Filesystem();
            $filesystem->folderCreate("project/log/mail", true);

            file_put_contents(_realpath_."project/log/mail/".time()."_".generateSystemid().".txt", $strDebug);
        }

        //only send if enabled
        return $cfg->getConfig('mail_relay_enabled') ? $objMail->send() : true;
    }
}
