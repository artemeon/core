<?php

namespace Kajona\System\Tests;

use Kajona\System\Admin\AdminModelserializer;
use Kajona\System\System\MessagingMessage;

class AdminModelserializerTest extends Testbase
{
    public function testGetProperties()
    {
        $objModel = new MessagingMessage();
        $objModel->setStrTitle("foobar");
        $objModel->setStrBody("foobar");
        $objModel->setBitRead(true);

        $arrActual = AdminModelserializer::getProperties($objModel);

        $arrExpect = [
            'strSystemid' => "",
            'strPrevId' => -1,
            'intModuleNr' => 135,
            'intSort' => -1,
            'strOwner' => "",
            'strLmUser' => "",
            'intLmTime' => 0,
            'strLockId' => "",
            'intLockTime' => 0,
            'intRecordStatus' => 1,
            'intRecordDeleted' => 0,
            'strRecordClass' => "Kajona\System\System\MessagingMessage",
            'longCreateDate' => 0,
            'strUser' => "",
            'strTitle' => "foobar",
            'strBody' => "foobar",
            'bitRead' => true,
            'strInternalIdentifier' => "",
            'strMessageProvider' => "",
            'strSenderId' => "",
            'strMessageRefId' => "",
            'strAttachment' => "",
        ];

        $this->assertEquals($arrExpect, $arrActual);
    }

    public function testGetPropertiesWhitelist()
    {
        $objModel = new MessagingMessage();
        $objModel->setStrTitle("foobar");
        $objModel->setStrBody("foobar");
        $objModel->setBitRead(true);

        $arrActual = AdminModelserializer::getProperties($objModel, ["strTitle", "bitRead"]);

        $arrExpect = [
            'strTitle' => "foobar",
            'bitRead' => true,
        ];

        $this->assertEquals($arrExpect, $arrActual);
    }

    public function testSerialize()
    {
        $objModel = new MessagingMessage();
        $objModel->setStrTitle("foobar");
        $objModel->setStrBody("foobar");
        $objModel->setBitRead(true);

        $strActual = AdminModelserializer::serialize($objModel);

        $strExpect = <<<'JSON'
{
  "strSystemid": "",
  "strPrevId": -1,
  "intModuleNr": 135,
  "intSort": -1,
  "strOwner": "",
  "strLmUser": "",
  "intLmTime": 0,
  "strLockId": "",
  "intLockTime": 0,
  "intRecordStatus": 1,
  "intRecordDeleted": 0,
  "strRecordClass": "Kajona\\System\\System\\MessagingMessage",
  "longCreateDate": null,
  "strUser": "",
  "strTitle": "foobar",
  "strBody": "foobar",
  "bitRead": true,
  "strInternalIdentifier": "",
  "strMessageProvider": "",
  "strSenderId": "",
  "strMessageRefId": "",
  "strAttachment": ""
}
JSON;

        $this->assertJsonStringEqualsJsonString($strExpect, $strActual, $strActual);
    }

    public function testUnserialize()
    {
        $strData = <<<'JSON'
{
  "strSystemid": "",
  "strPrevId": -1,
  "intModuleNr": 135,
  "intSort": -1,
  "strOwner": "",
  "strLmUser": "",
  "intLmTime": 0,
  "strLockId": "",
  "intLockTime": 0,
  "intRecordStatus": 1,
  "intRecordDeleted": 0,
  "strRecordClass": "Kajona\\System\\System\\MessagingMessage",
  "longCreateDate": null,
  "strUser": "",
  "strTitle": "foobar",
  "strBody": "foobar",
  "bitRead": true,
  "strInternalIdentifier": "",
  "strMessageProvider": "",
  "strSenderId": "",
  "strMessageRefId": ""
}
JSON;

        /** @var MessagingMessage $objMessage */
        $objMessage = AdminModelserializer::unserialize($strData);

        $this->assertInstanceOf(MessagingMessage::class, $objMessage);
        $this->assertEquals("foobar", $objMessage->getStrTitle());
        $this->assertEquals("foobar", $objMessage->getStrBody());
        $this->assertEquals(true, $objMessage->getBitRead());
    }
}

