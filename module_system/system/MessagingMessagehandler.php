<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;

use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Messageproviders\MessageproviderInterface;
use Kajona\System\System\Validators\EmailValidator;

/**
 * The messagehandler provides common methods to interact with the messaging-subsystem
 *
 * @author sidler@mulchprod.de
 * @since 4.0
 * @package module_messaging
 */
class MessagingMessagehandler
{
    /**
     * @var ServiceLifeCycleFactory
     */
    protected $objLifeCycleFactory;

    /**
     * @param ServiceLifeCycleFactory|null $objLifeCycleFactory
     */
    public function __construct(ServiceLifeCycleFactory $objLifeCycleFactory = null)
    {
        $this->objLifeCycleFactory = $objLifeCycleFactory === null ? Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_LIFE_CYCLE_FACTORY) : $objLifeCycleFactory;
    }

    /**
     * @return MessageproviderInterface[]
     */
    public function getMessageproviders()
    {
        $arrHandler = Resourceloader::getInstance()->getFolderContent("/system/messageproviders", array(".php"), false, null,
            function (&$strOneFile, $strPath) {

                $objInstance = Classloader::getInstance()->getInstanceFromFilename($strPath);

                if ($objInstance != null && $objInstance instanceof MessageproviderInterface) {
                    $strOneFile = $objInstance;
                } else {
                    $strOneFile = null;
                }
            }
        );

        $map = [];
        foreach ($arrHandler as $instance) {
            if ($instance !== null) {
                //keep entries from the project dir
                $map[get_class($instance)] = $instance;
            }
        }
        return $map;
    }


    /**
     * Sends a message.
     * If the list of recipients contains a group, the message is duplicated for each member.
     *
     * @param string $strContent
     * @param UserGroup[]|UserUser[]|UserGroup|UserUser $arrRecipients
     * @param MessageproviderInterface $objProvider
     * @param string $strInternalIdentifier
     * @param string $strSubject
     *
     * @deprecated use @link{class_module:messaging_messagehandler::sendMessageObject()} instead
     *
     * @return bool
     */
    public function sendMessage($strContent, $arrRecipients, MessageproviderInterface $objProvider, $strInternalIdentifier = "", $strSubject = "")
    {

        //build a default message and pass it to sendMessageObject
        $objMessage = new MessagingMessage();
        $objMessage->setStrTitle($strSubject);
        $objMessage->setStrBody($strContent);
        $objMessage->setStrInternalIdentifier($strInternalIdentifier);
        $objMessage->setStrMessageProvider(get_class($objProvider));
        return $this->sendMessageObject($objMessage, $arrRecipients);
    }


    /**
     * Sends an alert to a single user.
     * The alert is shown to the user directly, the user is forced to either accept or dismiss the alert
     * @param MessagingAlert $objAlert
     * @param UserUser $objUser
     */
    public function sendAlertToUser(MessagingAlert $objAlert, UserUser $objUser)
    {
        // is user currently active?
        if ($objUser->getIntRecordStatus() != 1) {
            return;
        }

        // check whether an alert exists already for the reference
        if ($this->hasAlert($objAlert->getStrRef(), $objUser->getSystemid())) {
            return;
        }

        $objAlert->setStrUser($objUser->getSystemid());
        $objAlert->setObjSendDate(new Date());

        if ($objUser->getSystemid() == Carrier::getInstance()->getObjSession()->getUserID()) {
            ResponseObject::getInstance()->setBitForceMessagePollOnRedirect(true);
        }

        $this->objLifeCycleFactory->factory(get_class($objAlert))->update($objAlert);
    }

    /**
     * Sends a message. If the list of recipients contains a group, the message is duplicated for each member. In case
     * a send date was provided we send the message at the provided date, if the date is now or in the past we send
     * the message direct.
     *
     * @param MessagingMessage $objMessage
     * @param UserGroup[]|UserUser[]|UserGroup|UserUser $arrRecipients
     * @param Date|null $objSendDate
     */
    public function sendMessageObject(MessagingMessage $objMessage, $arrRecipients, Date $objSendDate = null)
    {
        $objValidator = new EmailValidator();
        $objNowDate = new Date();
        $objNowDate->setBeginningOfDay();

        if ($arrRecipients instanceof UserGroup || $arrRecipients instanceof UserUser) {
            $arrRecipients = array($arrRecipients);
        }

        $arrRecipients = $this->getRecipientsFromArray($arrRecipients);

        foreach ($arrRecipients as $objOneUser) {
            //skip inactive users
            if ($objOneUser == null || $objOneUser->getIntRecordStatus() != 1) {
                continue;
            }

            $objConfig = MessagingConfig::getConfigForUserAndProvider($objOneUser->getSystemid(), $objMessage->getObjMessageProvider());

            if ($objConfig->getBitEnabled()) {
                if ($objSendDate !== null) {
                    $objSendDate->setBeginningOfDay();
                }

                if ($objSendDate !== null && $objSendDate->getTimeInOldStyle() > $objNowDate->getTimeInOldStyle()) {
                    // insert into queue
                    $objMessageQueue = new MessagingQueue();
                    $objMessageQueue->setStrRecipient($objOneUser->getSystemid());
                    $objMessageQueue->setObjSendDate($objSendDate);
                    $objMessageQueue->setMessage($objMessage);
                    ServiceLifeCycleFactory::getLifeCycle(get_class($objMessageQueue))->update($objMessageQueue);
                } else {
                    //clone the message
                    $objCurrentMessage = new MessagingMessage();
                    $objCurrentMessage->setStrTitle($objMessage->getStrTitle());
                    $objCurrentMessage->setStrBody($objMessage->getStrBody());
                    $objCurrentMessage->setStrUser($objOneUser->getSystemid());
                    $objCurrentMessage->setStrInternalIdentifier($objMessage->getStrInternalIdentifier());
                    $objCurrentMessage->setStrMessageProvider($objMessage->getStrMessageProvider());
                    $objCurrentMessage->setStrMessageRefId($objMessage->getStrMessageRefId());
                    $objCurrentMessage->setStrSenderId(validateSystemid($objMessage->getStrSenderId()) ? $objMessage->getStrSenderId() : Carrier::getInstance()->getObjSession()->getUserID());
                    $objCurrentMessage->setStrAttachment($objMessage->getStrAttachment());

                    ServiceLifeCycleFactory::getLifeCycle(get_class($objCurrentMessage))->update($objCurrentMessage);

                    if ($objConfig->getBitBymail() && $objValidator->validate($objOneUser->getStrEmail())) {
                        $this->sendMessageByMail($objCurrentMessage, $objOneUser);
                    }
                }
            }
        }
    }

    /**
     * Returns whether an alert exists for a specific reference id
     *
     * @param string $strRef
     * @param $strUserId
     * @return bool
     */
    protected function hasAlert($strRef, $strUserId)
    {
        if (empty($strRef)) {
            return false;
        }

        $objOrm = new OrmObjectlist();
        $objOrm->addWhereRestriction(new OrmPropertyCondition("strRef", OrmComparatorEnum::Equal(), $strRef));
        $objOrm->addWhereRestriction(new OrmPropertyCondition("strUser", OrmComparatorEnum::Equal(), $strUserId));
        return $objOrm->getSingleObject(MessagingAlert::class) !== null;
    }

    /**
     * Sends a copy of the message to the user by mail
     *
     * @param MessagingMessage $objMessage
     * @param UserUser $objUser
     *
     * @return bool
     */
    private function sendMessageByMail(MessagingMessage $objMessage, UserUser $objUser)
    {

        $strOriginalLang = Carrier::getInstance()->getObjLang()->getStrTextLanguage();

        Carrier::getInstance()->getObjLang()->setStrTextLanguage($objUser->getStrAdminlanguage());

        $strSubject = $objMessage->getStrTitle() != "" ? $objMessage->getStrTitle() : Carrier::getInstance()->getObjLang()->getLang("message_notification_subject", "messaging");

        $strBody = Carrier::getInstance()->getObjLang()->getLang("message_prolog", "messaging");
        $strBody .= "\n\n".Link::getLinkAdminHref("messaging", "view", "&systemid=".$objMessage->getSystemid(), false)."\n\n";
        $strBody .= $objMessage->getStrBody();

        $objMail = new Mail();

        //try to get a matching sender and place it into the mail
        if (validateSystemid($objMessage->getStrSenderId())) {
            /** @var UserUser $objSenderUser */
            $objSenderUser = Objectfactory::getInstance()->getObject($objMessage->getStrSenderId());
            $objValidator = new EmailValidator();
            if ($objValidator->validate($objSenderUser->getStrEmail())) {
                $objMail->setSender($objSenderUser->getStrEmail());
            }
        }

        $objMail->setSubject($strSubject);

        //add a read image to the body
        $strImageUrl = _xmlpath_."?module=messaging&action=setRead&systemid=".$objMessage->getSystemid();
        $strBody .= "<br /><br /><img src='{$strImageUrl}' width='1' height='1'>";
        $objMail->setHtml(nl2br($strBody));
        $objMail->addTo($objUser->getStrEmail());

        // add attachment
        $file = $objMessage->getStrAttachment();
        if (!empty($file)) {
            $objMail->addAttachement($file);
        }

        Carrier::getInstance()->getObjLang()->setStrTextLanguage($strOriginalLang);

        return $objMail->sendMail();
    }

    /**
     * Transforms a mixed array of users and groups into a list of users.
     *
     * @param UserGroup[]|UserUser[] $arrRecipients
     *
     * @return UserUser[]
     */
    public function getRecipientsFromArray($arrRecipients)
    {
        $arrReturn = array();

        if (!is_iterable($arrRecipients)) {
            return [];
        }

        foreach ($arrRecipients as $objOneRecipient) {
            if ($objOneRecipient instanceof UserUser && $objOneRecipient->getIntRecordDeleted() != 1) {
                $arrReturn[$objOneRecipient->getStrSystemid()] = $objOneRecipient;
            } elseif ($objOneRecipient instanceof UserGroup && $objOneRecipient->getIntRecordDeleted() != 1) {
                $objUsersources = new UserSourcefactory();
                if ($objUsersources->getSourceGroup($objOneRecipient) != null) {
                    $arrMembers = $objUsersources->getSourceGroup($objOneRecipient)->getUserIdsForGroup();

                    foreach ($arrMembers as $strOneId) {
                        if (!isset($arrReturn[$strOneId])) {
                            $arrReturn[$strOneId] = Objectfactory::getInstance()->getObject($strOneId);
                        }
                    }
                }
            }
        }


        return $arrReturn;
    }
}
