<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *   $Id$                              *
 ********************************************************************************************************/

namespace Kajona\System\Admin;

use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\ArraySectionIterator;
use Kajona\System\System\AuthenticationException;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\Filesystem;
use Kajona\System\System\Link;
use Kajona\System\System\Messageproviders\MessageproviderExtendedInterface;
use Kajona\System\System\Messageproviders\MessageproviderPersonalmessage;
use Kajona\System\System\MessagingAlert;
use Kajona\System\System\MessagingConfig;
use Kajona\System\System\MessagingMessage;
use Kajona\System\System\MessagingMessagehandler;
use Kajona\System\System\Model;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemChangelog;

/**
 * Admin-class to manage a users messages.
 * In addition, the user is able to configure each messageprovider (enable / disable, send by mail, ...)
 *
 * @package module_messaging
 * @author sidler@mulchprod.de
 * @since 4.0
 *
 * @module messaging
 * @moduleId _messaging_module_id_
 *
 * @objectList Kajona\System\System\MessagingMessage
 * @objectNew Kajona\System\System\MessagingMessage
 * @objectEdit Kajona\System\System\MessagingMessage
 */
class MessagingAdmin extends AdminEvensimpler implements AdminInterface
{

    /**
     * @return array
     */
    public function getOutputModuleNavi()
    {
        $arrReturn = array();
        $arrReturn[] = array("view", Link::getLinkAdmin($this->getArrModule("modul"), "list", "", $this->getLang("commons_list"), "", "", true, "adminnavi"));
        $arrReturn[] = array("edit", Link::getLinkAdmin($this->getArrModule("modul"), "config", "", $this->getLang("action_config"), "", "", true, "adminnavi"));
        return $arrReturn;
    }

    /**
     * @return array
     */
    public function getArrOutputNaviEntries()
    {
        $arrEntries = parent::getArrOutputNaviEntries();
        $objObject = Objectfactory::getInstance()->getObject($this->getSystemid());
        if ($objObject instanceof MessagingMessage) {
            $arrEntries[] = Link::getLinkAdmin("messaging", "view", "&systemid=" . $objObject->getSystemid(), $objObject->getStrDisplayName());
        }

        return $arrEntries;
    }

    /**
     * Renders the form to configure each messageprovider
     *
     * @permissions edit
     * @autoTestable
     *
     * @return string
     * @throws Exception
     */
    protected function actionConfig()
    {
        $objHandler = new MessagingMessagehandler();
        $arrMessageproviders = $objHandler->getMessageproviders();

        $strReturn = "";

        //create callback for the on-off toogle which is passed to formInputOnOff
        $strCallback = <<<JS
            //data contains the clicked element
            var inputId = $(this).attr('id');
            var messageProviderType = inputId.slice(0, inputId.lastIndexOf("_"));

            var param1 =inputId+'='+state; //value for clicked toggle element
            var param2 = 'messageprovidertype='+messageProviderType; //messageprovide type
            var postBody = param1+'&'+param2;

            Ajax.genericAjaxCall("messaging", "saveConfigAjax", "&"+postBody, Ajax.regularCallback);

            if(inputId.indexOf("_enabled") > 0 ) {
                $("#"+inputId).closest("tr").find("div.checkbox input:not(.blockEnable)").slice(1).bootstrapSwitch("disabled", state);
            }
JS;
        $arrRows = array();
        foreach ($arrMessageproviders as $objOneProvider) {
            if ($objOneProvider instanceof MessageproviderExtendedInterface && !$objOneProvider->isVisibleInConfigView()) {
                continue;
            }

            $objConfig = MessagingConfig::getConfigForUserAndProvider($this->objSession->getUserID(), $objOneProvider);

            $bitAlwaysEnabled = $objOneProvider instanceof MessageproviderExtendedInterface && $objOneProvider->isAlwaysActive();
            $bitAlwaysMail = $objOneProvider instanceof MessageproviderExtendedInterface && $objOneProvider->isAlwaysByMail();

            if ($bitAlwaysEnabled && $bitAlwaysMail) {
                continue;
            }

            $strClassname = StringUtil::replace("\\", "-", get_class($objOneProvider));

            $arrRows[] = array(
                $objOneProvider->getStrName(),
                "inlineFormEntry 1" => $this->objToolkit->formInputOnOff($strClassname . "_enabled", $this->getLang("provider_enabled"), $objConfig->getBitEnabled() == 1, $bitAlwaysEnabled, $strCallback),
                "inlineFormEntry 2" => $this->objToolkit->formInputOnOff($strClassname . "_bymail", $this->getLang("provider_bymail"), $objConfig->getBitBymail() == 1, $bitAlwaysMail, $strCallback, ($bitAlwaysMail ? "blockEnable" : "")),
            );

        }

        $arrHeader = array(
            $this->getLang("provider_title"),
            $this->getLang("provider_enabled"),
            $this->getLang("provider_bymail"),
        );

        $strReturn .= $this->objToolkit->dataTable($arrHeader, $arrRows);
        return $strReturn;
    }

    /**
     * @param Model $objListEntry
     *
     * @return array
     */
    protected function renderAdditionalActions(Model $objListEntry)
    {
        if ($objListEntry instanceof MessagingMessage) {
            return array(
                $this->objToolkit->listButton(Link::getLinkAdmin($objListEntry->getArrModule("modul"), "view", "&systemid=" . $objListEntry->getSystemid(), $this->getLang("action_edit"), $this->getLang("action_edit"), "icon_lens")),
                $this->objToolkit->listButton(Link::getLinkAdminDialog($this->getArrModule("modul"), "new", ["messaging_user_id" => $objListEntry->getStrSenderId(), "messaging_messagerefid" => $objListEntry->getSystemid(), "messaging_title" => "RE: " . $objListEntry->getStrTitle()], $this->getLang("message_reply"), $this->getLang("message_reply"), "icon_reply")),
            );
        }

        return array();
    }

    /**
     * @param string $strListIdentifier
     * @param bool $bitDialog
     *
     * @return array|string
     */
    protected function getNewEntryAction($strListIdentifier, $bitDialog = false)
    {
        return parent::getNewEntryAction($strListIdentifier, true);
    }

    /**
     * @param Model $objListEntry
     *
     * @return string
     */
    protected function renderCopyAction(Model $objListEntry)
    {
        return "";
    }

    /**
     * Stores the submitted config-data back to the database
     *
     * @permissions edit
     * @return void
     * @throws Exception
     */
    protected function actionSaveConfig()
    {

        $objHandler = new MessagingMessagehandler();
        $arrMessageproviders = $objHandler->getMessageproviders();

        foreach ($arrMessageproviders as $objOneProvider) {

            $strClassname = StringUtil::replace("\\", "", get_class($objOneProvider));

            $objConfig = MessagingConfig::getConfigForUserAndProvider($this->objSession->getUserID(), $objOneProvider);
            $objConfig->setBitBymail($this->getParam($strClassname . "_bymail") != "");
            $objConfig->setBitEnabled($this->getParam($strClassname . "_enabled") != "");
            $this->objLifeCycleFactory->factory(get_class($objConfig))->update($objConfig);

        }

        $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul")));
    }

    /**
     * Stores the submitted config-data back to the database.
     * This method stores only one value message for one messageprovider (either "_bymail" or "_enabled").
     *
     * @permissions edit
     *
     * @return string
     * @throws Exception
     */
    protected function actionSaveConfigAjax()
    {
        $objHandler = new MessagingMessagehandler();
        $arrMessageproviders = $objHandler->getMessageproviders();
        $strMessage = "";

        foreach ($arrMessageproviders as $objOneProvider) {
            $objConfig = MessagingConfig::getConfigForUserAndProvider($this->objSession->getUserID(), $objOneProvider);

            $strClassname = StringUtil::replace("\\", "-", get_class($objOneProvider));

            //only update the message provider which is set in the param "messageprovidertype"
            if ($this->getParam("messageprovidertype") == $strClassname) {
                if ($this->getParam($strClassname . "_bymail") != "") {
                    $bitA = $this->getParam($strClassname . "_bymail") == "true";
                    $objConfig->setBitBymail($bitA);
                    $this->objLifeCycleFactory->factory(get_class($objConfig))->update($objConfig);
                    $strMessage = $objOneProvider->getStrName() . " " . $this->getLang("provider_bymail") . "=" . ($bitA ? $this->getLang("systemtask_systemstatus_active", "system") : $this->getLang("systemtask_systemstatus_inactive", "system"));
                    break;

                } elseif ($this->getParam($strClassname . "_enabled") != "") {
                    $bitA = $this->getParam($strClassname . "_enabled") == "true";
                    $objConfig->setBitEnabled($bitA);
                    $this->objLifeCycleFactory->factory(get_class($objConfig))->update($objConfig);
                    $strMessage = $objOneProvider->getStrName() . " " . $this->getLang("provider_enabled") . "=" . ($bitA ? $this->getLang("systemtask_systemstatus_active", "system") : $this->getLang("systemtask_systemstatus_inactive", "system"));
                    break;
                }
            }
        }

        return "<message>" . $strMessage . "</message>";
    }

    /**
     * @param Model $objListEntry
     * @param bool $bitDialog
     *
     * @param array $arrParams
     * @return string
     */
    protected function renderEditAction(Model $objListEntry, $bitDialog = false, array $arrParams = null)
    {
        return "";
    }

    /**
     * @param Model $objListEntry
     * @param string $strAltActive tooltip text for the icon if record is active
     * @param string $strAltInactive tooltip text for the icon if record is inactive
     *
     * @return string
     */
    protected function renderStatusAction(Model $objListEntry, $strAltActive = "", $strAltInactive = "")
    {
        return "";
    }

    /**
     * Returns a list of the languages
     *
     * @return string
     * @permissions view
     * @autoTestable
     * @throws Exception
     */
    protected function actionList()
    {

        //render two multi-buttons
        $strReturn = "";

        //create the list-button and the js code to show the dialog
        $strDeleteAllRead = $this->objToolkit->confirmationLink(
            $this->getLang("delete_all_read_question"),
            getLinkAdminHref($this->getArrModule("module"), "deleteAllRead"),
            AdminskinHelper::getAdminImage("icon_delete") . $this->getLang("action_delete_all_read"),
            $this->getLang("dialog_deleteHeader", "system"),
            $this->getLang("dialog_deleteButton", "system")
        );

        $strDeleteAll = $this->objToolkit->confirmationLink(
            $this->getLang("delete_all_question"),
            getLinkAdminHref($this->getArrModule("module"), "deleteAll"),
            AdminskinHelper::getAdminImage("icon_delete") . $this->getLang("action_delete_all"),
            $this->getLang("dialog_deleteHeader", "system"),
            $this->getLang("dialog_deleteButton", "system")
        );

        $strReturn .= $this->objToolkit->getContentToolbar(array(
            Link::getLinkAdmin($this->getArrModule("module"), "setAllRead", "", AdminskinHelper::getAdminImage("icon_mail") . $this->getLang("action_set_all_read")),
            $strDeleteAllRead,
            $strDeleteAll,
        ));

        $objArraySectionIterator = new ArraySectionIterator(MessagingMessage::getNumberOfMessagesForUser($this->objSession->getUserID()));
        $objArraySectionIterator->setPageNumber((int) ($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(
            MessagingMessage::getObjectListFiltered(
                null, $this->objSession->getUserID(), $objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()
            )
        );

        $strReturn .= $this->renderList($objArraySectionIterator);
        return $strReturn;

    }

    /**
     * @param string $strListIdentifier
     *
     * @return array
     */
    protected function getBatchActionHandlers($strListIdentifier)
    {
        $arrDefault = array();
        if ($this->getObjModule()->rightDelete()) {
            $arrDefault[] = new AdminBatchaction(AdminskinHelper::getAdminImage("icon_delete"), Link::getLinkAdminXml("system", "delete", "&systemid=%systemid%"), $this->getLang("commons_batchaction_delete"));
        }
        $arrDefault[] = new AdminBatchaction(AdminskinHelper::getAdminImage("icon_mail"), Link::getLinkAdminXml("messaging", "apiSetRead", "&systemid=%systemid%"), $this->getLang("batchaction_read"));
        $arrDefault[] = new AdminBatchaction(AdminskinHelper::getAdminImage("icon_mailNew"), Link::getLinkAdminXml("messaging", "apiSetUnread", "&systemid=%systemid%"), $this->getLang("batchaction_unread"));
        return $arrDefault;
    }

    /**
     * @return string
     * @permissions delete
     * @throws Exception
     */
    protected function actionDeleteAllRead()
    {
        MessagingMessage::deleteAllReadMessages($this->objSession->getUserID());
        $this->adminReload(getLinkAdminHref($this->getArrModule("modul"), "list"));
        return "";
    }

    /**
     * @return string
     * @permissions delete
     * @throws Exception
     */
    protected function actionDeleteAll()
    {
        MessagingMessage::deleteAllMessages($this->objSession->getUserID());
        $this->adminReload(getLinkAdminHref($this->getArrModule("modul"), "list"));
        return "";
    }

    /**
     * @return string
     * @permissions view
     * @throws Exception
     */
    protected function actionSetAllRead()
    {
        MessagingMessage::markAllMessagesAsRead($this->objSession->getUserID());
        $this->adminReload(getLinkAdminHref($this->getArrModule("modul"), "list"));
        return "";
    }

    /**
     * Marks a single message as read
     *
     * @return string
     * @permissions view
     * @throws Exception
     */
    protected function actionApiSetRead()
    {
        $objMessage = Objectfactory::getInstance()->getObject($this->getSystemid());
        if ($objMessage instanceof MessagingMessage) {
            $objMessage->setBitRead(true);
            $this->objLifeCycleFactory->factory(get_class($objMessage))->update($objMessage);

            return "<message><success /></message>";
        }

        return "<message><error /></message>";
    }

    /**
     * Marks a single message as unread
     *
     * @return string
     * @permissions view
     * @throws Exception
     */
    protected function actionApiSetUnread()
    {
        $objMessage = Objectfactory::getInstance()->getObject($this->getSystemid());
        if ($objMessage instanceof MessagingMessage) {
            $objMessage->setBitRead(false);
            $this->objLifeCycleFactory->factory(get_class($objMessage))->update($objMessage);

            return "<message><success /></message>";
        }

        return "<message><error /></message>";
    }

    /**
     * @permissions view
     * @return string
     */
    protected function actionEdit()
    {
        return $this->actionView();
    }

    /**
     * @permissions edit
     * @return string
     * @throws Exception
     */
    protected function actionNew()
    {
        $this->setStrCurObjectTypeName("");
        $this->setCurObjectClassName("Kajona\\System\\System\\MessagingMessage");
        return parent::actionNew();
    }

    /**
     * @permissions edit
     * @return string
     * @throws Exception
     */
    protected function actionSave()
    {
        /** @var $objMessage MessagingMessage */
        $objMessage = null;

        $objMessage = new MessagingMessage();

        $objForm = $this->getAdminForm($objMessage);
        if (!$objForm->validateForm()) {
            if ($this->getParam("mode") === "new") {
                return $this->actionNew();
            }
        }

        $objForm->updateSourceObject();

        // in case available set the internal identifier
        $internalIdentifier = $this->getParam("messaging_internal_identifier");
        if (validateSystemid($internalIdentifier)) {
            $objMessage->setStrInternalIdentifier($internalIdentifier);
        }

        $objMessageHandler = new MessagingMessagehandler();
        $objMessage->setObjMessageProvider(new MessageproviderPersonalmessage());

        $arrTo = [];
        if (!is_array($objMessage->getStrUser())) {
            $arrTo[] = Objectfactory::getInstance()->getObject($objMessage->getStrUser());
        } elseif (is_array($objMessage->getStrUser())) {
            foreach ($objMessage->getStrUser() as $userId) {
                $arrTo[] = Objectfactory::getInstance()->getObject($userId);
            }
        }

        $objMessageHandler->sendMessageObject($objMessage, $arrTo);

        return $this->objToolkit->warningBox($this->getLang("message_sent_success")) .
            $this->objToolkit->formHeader("") .
            $this->objToolkit->formInputSubmit($this->getLang("commons_ok"), "", "parent.Folderview.dialog.hide();") .
            $this->objToolkit->formClose();
    }

    /**
     * Creates a summary of the message
     *
     * @permissions view
     * @return string
     * @throws Exception
     */
    protected function actionView()
    {
        /** @var MessagingMessage $objMessage */
        $objMessage = Objectfactory::getInstance()->getObject($this->getSystemid());

        //different permission handlings
        if ($objMessage !== null && !$objMessage->rightView()) {
            return $this->strOutput = $this->getLang("commons_error_permissions");
        } elseif ($objMessage == null) {

            $strText = $this->getLang("message_not_existing");
            $strOk = $this->getLang("commons_ok");
            $strLink = Link::getLinkAdminHref($this->getArrModule("modul"), "list");
            $strMessage = "<script type='text/javascript'>
                $(function() { setTimeout(function() {
                    jsDialog_1.setTitle('&nbsp; ');
                    jsDialog_1.setContent('{$strText}', '{$strOk}', '{$strLink}'); jsDialog_1.init();
                    $('#'+jsDialog_1.containerId+'_cancelButton').css('display', 'none');
                }, 500) } );
            </script>";

            return $strMessage;
        }

        if ($objMessage->getStrUser() == $this->objSession->getUserID()) {

            $strReturn = "";
            if (!$objMessage->getBitRead()) {
                $objMessage->setBitRead(true);
                $this->objLifeCycleFactory->factory(get_class($objMessage))->update($objMessage);
            }

            $objSender = Objectfactory::getInstance()->getObject($objMessage->getStrSenderId());

            $strReference = "";
            if (validateSystemid($objMessage->getStrMessageRefId())) {
                $objRefMessage = new MessagingMessage($objMessage->getStrMessageRefId());
                $strReference = $objRefMessage->getStrDisplayName();
                if ($objRefMessage->rightView()) {
                    $strReference = getLinkAdmin($this->getArrModule("modul"), "view", "&systemid=" . $objRefMessage->getSystemid(), $strReference, "", "", false);
                }
            }

            $arrMetaData = array(
                array($this->getLang("message_subject"), $objMessage->getStrTitle()),
                array($this->getLang("message_date"), dateToString($objMessage->getObjDate())),
                array($this->getLang("message_type"), $objMessage->getObjMessageProvider()->getStrName()),
                array($this->getLang("message_sender"), $objSender != null ? $objSender->getStrDisplayName() : ""),
            );

            if (!empty($strReference)) {
                $arrMetaData[] = [
                    $this->getLang("message_reference"),
                    $strReference
                ];
            }

            $attachment = $objMessage->getStrAttachment();
            if (!empty($attachment)) {
                $fileName = pathinfo($attachment, PATHINFO_BASENAME);

                $arrMetaData[] = [
                    $this->getLang("message_attachment"),
                    Link::getLinkAdmin("messaging", "downloadAttachment", ["systemid" => $objMessage->getSystemid()], $fileName),
                ];
            }

            $strReturn .= $this->objToolkit->dataTable(array(), $arrMetaData);

            $strBody = nl2br($objMessage->getStrBody());
            $strBody = replaceTextLinks($strBody);
            $strReturn .= $this->objToolkit->getFieldset($objMessage->getStrTitle(), $this->objToolkit->getTextRow($strBody));

            return $strReturn;
        } else {
            return $this->getLang("commons_error_permissions");
        }
    }

    /**
     * @permissions view
     */
    protected function actionDownloadAttachment()
    {
        $message = $this->objFactory->getObject($this->getSystemid());
        if ($message instanceof MessagingMessage) {
            $attachment = $message->getStrAttachment();

            if (!empty($attachment)) {
                (new Filesystem())->streamFile($attachment);
                exit;
            } else {
                throw new \RuntimeException("Attachment not available");
            }
        } else {
            throw new \RuntimeException("Invalid systemid");
        }
    }

    /**
     * Gets the number of unread messages for the current user.
     * Fetches the latest alert, too - if given.
     *
     * @permissions view
     * @autoTestable
     **
     * @responseType json
     */
    protected function actionGetUnreadMessagesCount()
    {
        Carrier::getInstance()->getObjSession()->setBitBlockDbUpdate(true);
        Session::getInstance()->sessionClose();

        return json_encode([
            "count" => MessagingMessage::getNumberOfMessagesForUser($this->objSession->getUserID(), true),
            "alert" => MessagingAlert::getNextAlertForUser($this->objSession->getUserID()),
        ]);
    }

    /**
     * Creates a list of the recent messages for the current user.
     * The structure is returned in an json-format.
     *
     * @permissions view
     * @autoTestable
     *
     * @return string
     * @responseType json
     * @throws Exception
     */
    protected function actionGetRecentMessages()
    {
        Carrier::getInstance()->getObjSession()->setBitBlockDbUpdate(true);
        Session::getInstance()->sessionClose();
        SystemChangelog::$bitChangelogEnabled = false;

        $intMaxAmount = $this->getParam("limit") != "" ? $this->getParam("limit") : 5;

        $arrMessages = MessagingMessage::getObjectListFiltered(null, $this->objSession->getUserID(), 0, $intMaxAmount - 1);
        $arrReturn = array();
        foreach ($arrMessages as $objOneMessage) {
            $arrReturn[] = array(
                "systemid" => $objOneMessage->getSystemid(),
                "title" => $objOneMessage->getStrDisplayName(),
                "unread" => $objOneMessage->getBitRead(),
                "details" => Link::getLinkAdminHref($objOneMessage->getArrModule("modul"), "edit", "&systemid=" . $objOneMessage->getSystemid(), false, true),
            );
        }

        $arrReturn = array(
            "messages" => $arrReturn,
            "messageCount" => MessagingMessage::getNumberOfMessagesForUser($this->objSession->getUserID(), true),
        );

        return json_encode($arrReturn);
    }

    /**
     * @permissions view
     * @responseType json
     */
    protected function actionDeleteAlert()
    {
        $objAlert = Objectfactory::getInstance()->getObject($this->getSystemid());
        if ($objAlert instanceof MessagingAlert && $objAlert->getStrUser() == $this->objSession->getUserID()) {
            return json_encode(
                $this->objLifeCycleFactory->factory(get_class($objAlert))->delete($objAlert)
            );
        }
        throw new AuthenticationException("User is not allowed to delete action", Exception::$level_ERROR);
    }

    /**
     * Marks a message as read and returns a 1x1px transparent gif as a "read indicator"
     *
     * @return string
     * @responseType gif
     * @permissions anonymous
     * @throws Exception
     */
    protected function actionSetRead()
    {
        $objMessage = Objectfactory::getInstance()->getObject($this->getSystemid());

        if ($objMessage !== null && $objMessage instanceof MessagingMessage && $objMessage->getBitRead() == 0) {
            $objMessage->setBitRead(1);
            $this->objLifeCycleFactory->factory(get_class($objMessage))->update($objMessage);
        }

        return base64_decode("R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==");
    }

}
