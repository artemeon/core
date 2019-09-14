<?php

namespace Kajona\System\Tests;

use Kajona\System\System\Database;
use Kajona\System\System\Date;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Messageproviders\MessageproviderExceptions;
use Kajona\System\System\MessagingMessage;
use Kajona\System\System\MessagingMessagehandler;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;

class MessagingTest extends Testbase
{



    public function setUp()
    {
        parent::setUp();

        $this->removeAllQueueEntries();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->removeAllQueueEntries();
    }

    public function testSendMessage()
    {
        $strText = generateSystemid() . " autotest";
        $strTitle = generateSystemid() . " title";
        $strIdentifier = generateSystemid() . " identifier";

        $objMessageHandler = new MessagingMessagehandler();
        $objMessageHandler->sendMessage($strText, Objectfactory::getInstance()->getObject(SystemSetting::getConfigValue("_admins_group_id_")), new MessageproviderExceptions(), $strIdentifier, $strTitle);

        $objGroup = Objectfactory::getInstance()->getObject(SystemSetting::getConfigValue("_admins_group_id_"));
        $arrUsers = $objGroup->getObjSourceGroup()->getUserIdsForGroup();

        $arrMessages = MessagingMessage::getObjectListFiltered(null, $arrUsers[0]);

        $bitFound = false;
        foreach ($arrMessages as $objOneMessage) {
            $bitFound = true;
            $this->assertEquals($strTitle, $objOneMessage->getStrTitle());
            $this->assertEquals($strText, $objOneMessage->getStrBody());
            $this->assertEquals($strIdentifier, $objOneMessage->getStrInternalIdentifier());
        }

        $this->assertTrue($bitFound);
        $this->flushDBCache();
    }

    public function testSendMessageObject()
    {
        $objMessage = $this->newMessage();

        $objMessageHandler = new MessagingMessagehandler();
        $objMessageHandler->sendMessageObject($objMessage, Objectfactory::getInstance()->getObject(SystemSetting::getConfigValue("_admins_group_id_")));

        $objGroup = Objectfactory::getInstance()->getObject(SystemSetting::getConfigValue("_admins_group_id_"));
        $arrUsers = $objGroup->getObjSourceGroup()->getUserIdsForGroup();

        foreach ($arrUsers as $objOneUser) {
            $bitFound = false;
            $arrMessages = MessagingMessage::getObjectListFiltered(null, $objOneUser);

            foreach ($arrMessages as $objOneMessage) {
                $bitFound = true;
                $this->assertEquals($objMessage->getStrTitle(), $objOneMessage->getStrTitle());
                $this->assertEquals($objMessage->getStrInternalIdentifier(), $objOneMessage->getStrInternalIdentifier());
                $this->assertEquals($objMessage->getStrSenderId(), $objOneMessage->getStrSenderId());
                $this->assertEquals($objMessage->getStrMessageRefId(), $objOneMessage->getStrMessageRefId());
            }

            $this->assertTrue($bitFound);
        }
    }

    public function testUnreadCount()
    {
        $strText = generateSystemid() . " autotest";

        $objMessageHandler = new MessagingMessagehandler();
        $objMessageHandler->sendMessage($strText, Objectfactory::getInstance()->getObject(SystemSetting::getConfigValue("_admins_group_id_")), new MessageproviderExceptions());

        $objGroup = Objectfactory::getInstance()->getObject(SystemSetting::getConfigValue("_admins_group_id_"));
        $arrUsers = $objGroup->getObjSourceGroup()->getUserIdsForGroup();
        $strUserId = $arrUsers[0];

        $arrMessages = MessagingMessage::getObjectListFiltered(null, $strUserId);
        $intUnread = MessagingMessage::getNumberOfMessagesForUser($strUserId, true);

        $this->assertEquals(1, $intUnread);
        $this->flushDBCache();

        $bitFound = false;
        foreach ($arrMessages as $objOneMessage) {
            $bitFound = true;
            $objOneMessage->setBitRead(true);
            ServiceLifeCycleFactory::getLifeCycle(get_class($objOneMessage))->update($objOneMessage);

            $this->assertEquals(0, MessagingMessage::getNumberOfMessagesForUser($strUserId, true));
        }

        $this->assertTrue($bitFound);
    }

    private function newMessage()
    {
        $strText = generateSystemid() . " autotest";
        $strTitle = generateSystemid() . " title";
        $strIdentifier = generateSystemid() . " identifier";
        $strSender = generateSystemid();
        $strReference = generateSystemid();
        $objSendDate = new Date();
        $objSendDate->setNextDay();

        $objMessage = new MessagingMessage();
        $objMessage->setStrTitle($strTitle);
        $objMessage->setStrBody($strText);
        $objMessage->setStrInternalIdentifier($strIdentifier);
        $objMessage->setObjMessageProvider(new MessageproviderExceptions());
        $objMessage->setStrSenderId($strSender);
        $objMessage->setStrMessageRefId($strReference);

        return $objMessage;
    }

    private function removeAllQueueEntries()
    {
        $objDb = Database::getInstance();

        $arrResult = $objDb->getPArray("SELECT message_id FROM agp_messages", []);
        foreach ($arrResult as $arrRow) {
            $objMessage = Objectfactory::getInstance()->getObject($arrRow["message_id"]);
            $objMessage->deleteObjectFromDatabase();
        }
    }
}

