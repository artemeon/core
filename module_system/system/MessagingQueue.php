<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;

/**
 * MessagingQueue
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.0
 * @package module_messaging
 * @targetTable messages_queue.queue_id
 * @module messaging
 * @moduleId _messaging_module_id_
 */
class MessagingQueue extends Model
{
    /**
     * @var string
     * @tableColumn messages_queue.queue_receiver
     * @tableColumnDatatype char20
     */
    private $strReceiver = "";

    /**
     * @var string
     * @tableColumn messages_queue.queue_message
     * @tableColumnDatatype text
     * @blockEscaping
     */
    private $strMessage = "";

    /**
     * @var Date
     * @tableColumn messages_queue.queue_send_date
     * @tableColumnDatatype long
     * @blockEscaping
     */
    private $objSendDate;

    /**
     * @return string
     */
    public function getStrReceiver()
    {
        return $this->strReceiver;
    }

    /**
     * @param string $strReceiver
     */
    public function setStrReceiver($strReceiver)
    {
        $this->strReceiver = $strReceiver;
    }

    /**
     * @return string
     */
    public function getStrMessage()
    {
        return $this->strMessage;
    }

    /**
     * @param string $strMessage
     */
    public function setStrMessage($strMessage)
    {
        $this->strMessage = $strMessage;
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
    public function setObjSendDate(Date $objSendDate)
    {
        $this->objSendDate = $objSendDate;
    }

    /**
     * @param string $strMessage
     */
    public function setMessage(MessagingMessage $objMessage)
    {
        $this->setStrMessage(json_encode($objMessage->toArray()));
    }

    /**
     * @return UserUser
     */
    public function getReceiver()
    {
        return Objectfactory::getInstance()->getObject($this->strReceiver);
    }

    /**
     * @return MessagingMessage
     */
    public function getMessage()
    {
        return !empty($this->strMessage) ? MessagingMessage::fromArray(json_decode($this->strMessage, true)) : null;
    }

    /**
     * @param Date $objDate
     * @return MessagingQueue[]
     */
    public static function getMessagesForToday()
    {
        $objNowDate = new Date();
        $objNowDate->setBeginningOfDay();

        $objORM = new OrmObjectlist();
        $objORM->addWhereRestriction(new OrmPropertyCondition("objSendDate", OrmComparatorEnum::Equal(), $objNowDate->getLongTimestamp()));

        return $objORM->getObjectList(get_called_class());
    }
}