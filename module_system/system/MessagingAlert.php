<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/
declare(strict_types=1);

namespace Kajona\System\System;

use Kajona\System\System\Alert\MessagingAlertActionInterface;

/**
 * Model for a single alert, emitted by the messaging subsytem.
 * Alerts differ from messages, since alerts are shown automatically as soon as the user navigates in the system.
 * The user is granted with an option to accept the alert or to dismiss the alert, on accept a custom js action may be executed.
 * As soon as the alert is rendered on the users screen, it is marked and read and deleted automatically.
 *
 * Please do not use Alerts directly, use the MessagingMessagehandler::sendAlertToUser() method instead.
 *
 * Example:
 *
 *   $objAlert = new MessagingAlert();
 *   $objAlert->setStrTitle("Optional");
 *   $objAlert->setStrBody("Here we go");
 *   $objAlert->setObjAlertAction(new MessagingAlertActionRedirect(Link::getLinkAdminHref("dashboard")));
 *
 *   $objMessagehandler = new MessagingMessagehandler();
 *   $objMessagehandler->sendAlertToUser($objAlert, UserUser::getAllUsersByName("sir")[0]);
 *
 *
 * @author sidler@mulchprod.de
 * @since 7.0
 * @targetTable agp_messages_alert.alert_id
 *
 * @module messaging
 * @moduleId _messaging_module_id_
 *
 * @lifeCycleService system_life_cycle_messages_alert
 * @see MessagingMessagehandler::sendAlertToUser
 *
 */
class MessagingAlert extends Model implements ModelInterface, AdminListableInterface, \JsonSerializable
{
    /**
     * @var string
     * @tableColumn agp_messages_alert.alert_user
     * @tableColumnDatatype char20
     * @tableColumnIndex
     */
    private $strUser = "";

    /**
     * A general reference to a database record or a generated id
     *
     * @var string
     * @tableColumn agp_messages_alert.alert_ref
     * @tableColumnDatatype char20
     * @tableColumnIndex
     */
    private $strRef = "";

    /**
     * @var string
     * @tableColumn agp_messages_alert.alert_title
     * @tableColumnDatatype char254
     */
    private $strTitle = "";

    /**
     * @var string
     * @tableColumn agp_messages_alert.alert_body
     * @tableColumnDatatype longtext
     * @blockEscaping
     */
    private $strBody = "";

    /**
     * @var bool
     * @tableColumn agp_messages_alert.alert_shown
     * @tableColumnDatatype int
     */
    private $bitShown = 0;

    /**
     * @var string
     * @tableColumn agp_messages_alert.alert_callback
     * @tableColumnDatatype longtext
     * @blockEscaping
     */
    private $strOnAcceptCallback = "";

    /**
     * @var Date
     * @tableColumn agp_messages_alert.alert_send
     * @tableColumnDatatype long
     */
    private $objSendDate = null;

    /**
     * @var string
     * @tableColumn agp_messages_alert.alert_confirm_label
     * @tableColumnDatatype char254
     */
    private $strConfirmLabel = "";

    /**
     * @var int
     * @tableColumn agp_messages_alert.alert_priority
     * @tableColumnDatatype int
     */
    private $intPriority = 1;

    /**
     * MessagingAlert constructor.
     */
    public function __construct($strSystemid = "")
    {
        parent::__construct($strSystemid);
        $this->strConfirmLabel = $this->getLang("commons_ok");
    }

    /**
     * Fetches the next alert for the current user - if given
     *
     * @param string $strUserid
     * @return MessagingAlert|null
     */
    public static function getNextAlertForUser($strUserid)
    {
        $objOrm = new OrmObjectlist();
        $objOrm->addWhereRestriction(new OrmPropertyCondition("strUser", OrmComparatorEnum::Equal(), $strUserid));
        $objOrm->addOrderBy(new OrmObjectlistOrderby("alert_priority DESC"));
        return $objOrm->getSingleObject(MessagingAlert::class);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            "type" => get_class($this),
            "title" => $this->strTitle,
            "body" => $this->strBody,
            "confirmLabel" => $this->strConfirmLabel,
            "onAccept" => json_decode($this->strOnAcceptCallback.""),
            "systemid" => $this->getSystemid()
        ];
    }


    public function setObjAlertAction(MessagingAlertActionInterface $objAction)
    {
        $this->strOnAcceptCallback = json_encode($objAction->getAsActionArray());
    }

    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     */
    public function getStrDisplayName()
    {
        return $this->getStrTitle();
    }

    /**
     * Returns the icon the be used in lists.
     * Please be aware, that only the filename should be returned, the wrapping by getImageAdmin() is
     * done afterwards.
     *
     * @return string the name of the icon, not yet wrapped by getImageAdmin()
     */
    public function getStrIcon()
    {
        if ($this->getBitShown()) {
            return "icon_mail";
        } else {
            return "icon_mailNew";
        }
    }

    /**
     * In nearly all cases, the additional info is rendered left to the action-icons.
     *
     * @return string
     */
    public function getStrAdditionalInfo()
    {
        return dateToString($this->getObjSendDate());
    }

    /**
     * If not empty, the returned string is rendered below the common title.
     *
     * @return string
     */
    public function getStrLongDescription()
    {
        return "";
    }

    /**
     * @return string
     */
    public function getStrUser()
    {
        return $this->strUser;
    }

    /**
     * @param string $strUser
     */
    public function setStrUser($strUser)
    {
        $this->strUser = $strUser;
    }

    /**
     * @return string
     */
    public function getStrRef()
    {
        return $this->strRef;
    }

    /**
     * @param string $strRef
     */
    public function setStrRef($strRef)
    {
        $this->strRef = $strRef;
    }

    /**
     * @return string
     */
    public function getStrTitle()
    {
        return $this->strTitle;
    }

    /**
     * @param string $strTitle
     */
    public function setStrTitle($strTitle)
    {
        $this->strTitle = $strTitle;
    }

    /**
     * @return string
     */
    public function getStrBody()
    {
        return $this->strBody;
    }

    /**
     * @param string $strBody
     */
    public function setStrBody(string $strBody)
    {
        $this->strBody = $strBody;
    }

    /**
     * @return bool
     */
    public function getBitShown()
    {
        return $this->bitShown;
    }

    /**
     * @param bool $bitShown
     */
    public function setBitShown($bitShown)
    {
        $this->bitShown = $bitShown;
    }

    /**
     * @return string
     * @internal
     */
    public function getStrOnAcceptCallback()
    {
        return $this->strOnAcceptCallback;
    }

    /**
     * @param string $strOnAcceptCallback
     * @internal
     */
    public function setStrOnAcceptCallback($strOnAcceptCallback)
    {
        $this->strOnAcceptCallback = $strOnAcceptCallback;
    }


    /**
     * @return Date
     */
    public function getObjSendDate()
    {
        return $this->objSendDate;
    }

    /**
     * @param Date $objSendDate
     */
    public function setObjSendDate($objSendDate)
    {
        $this->objSendDate = $objSendDate;
    }

    /**
     * @return string
     */
    public function getStrConfirmLabel()
    {
        return $this->strConfirmLabel;
    }

    /**
     * @param string $strConfirmLabel
     */
    public function setStrConfirmLabel($strConfirmLabel)
    {
        $this->strConfirmLabel = $strConfirmLabel;
    }

    /**
     * @return int
     */
    public function getIntPriority()
    {
        return $this->intPriority;
    }

    /**
     * @param int $intPriority
     */
    public function setIntPriority($intPriority)
    {
        $this->intPriority = $intPriority;
    }




}
