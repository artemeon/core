<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Flow\System;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Formentries\FormentryHeadline;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Alert\MessagingAlertActionRedirect;
use Kajona\System\System\Alert\MessagingAlertActionUpdateStatus;
use Kajona\System\System\Alert\MessagingAlertActionVoid;
use Kajona\System\System\Carrier;
use Kajona\System\System\Link;
use Kajona\System\System\MessagingAlert;
use Kajona\System\System\MessagingExecution;
use Kajona\System\System\MessagingMessagehandler;
use Kajona\System\System\MessagingNotification;
use Kajona\System\System\Model;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\RedirectException;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\Session;
use Kajona\System\View\Components\Dynamicmenu\DynamicMenu;
use Kajona\System\View\Components\Menu\Item\Dialog;
use Kajona\System\View\Components\Menu\Item\Headline;
use Kajona\System\View\Components\Menu\Item\Separator;
use Kajona\System\View\Components\Menu\Menu;
use Kajona\System\View\Components\Menu\MenuItem;
use Kajona\System\Xml;

/**
 * @author christoph.kappestein@artemeon.de
 * @module flow
 */
trait FlowControllerTrait
{
    /**
     * @inject flow_manager
     * @var FlowManager
     */
    protected $objFlowManager;

    /**
     * @inject system_message_handler
     * @var MessagingMessagehandler
     */
    protected $objMessageHandler;

    /**
     * @inject system_session
     * @var Session
     */
    protected $objSession;

    protected function renderStatusAction(Model $objListEntry, $strAltActive = "", $strAltInactive = "")
    {
        if ($objListEntry->getIntRecordDeleted() == 1) {
            return "";
        }

        $objCurrentStatus = $this->objFlowManager->getCurrentStepForModel($objListEntry);
        if ($objCurrentStatus === null) {
            return parent::renderStatusAction($objListEntry, $strAltActive, $strAltInactive);
        }

        $strIcon = AdminskinHelper::getAdminImage($objCurrentStatus->getStrIcon(), $objCurrentStatus->getStrDisplayName());

        if (!$objListEntry->rightView()) {
            return "";
        }

        $menu = new DynamicMenu(
            $this->objToolkit->listButton($strIcon),
            Link::getLinkAdminXml($objListEntry->getArrModule('module'), "showStatusMenu", ["systemid" => $objListEntry->getSystemid()])
        );
        $menu->setClass("flow-status-icon");
        $menu->setSystemId($objListEntry->getSystemid());

        $strReturn = $menu->renderComponent();
        return $strReturn;
    }

    /**
     * Action to set the next status
     *
     * @return string
     * @permissions view
     */
    protected function actionSetStatus()
    {
        $objObject = $this->objFactory->getObject($this->getSystemid());
        if ($objObject instanceof Model) {
            // check right
            if ($objObject instanceof FlowModelRightInterface) {
                $bitHasRight = $objObject->rightStatus();
            } else {
                $bitHasRight = $objObject->rightEdit();
            }

            if (!$bitHasRight) {
                throw new \RuntimeException("No right to change the status of the object");
            }

            $strTransitionId = $this->getParam("transition_id");
            $objFlow = $this->objFlowManager->getFlowForModel($objObject);
            $objTransition = Objectfactory::getInstance()->getObject($strTransitionId);

            if ($objTransition instanceof FlowTransition) {
                if (!$objFlow->hasTransition($objObject->getIntRecordStatus(), $objTransition)) {
                    throw new \RuntimeException("It is not possible to trigger the provided transition in this state");
                }

                $arrActions = $objTransition->getArrActions();
                $objForm = new AdminFormgenerator("", $objObject);
                $bitInputRequired = false;

                foreach ($arrActions as $objAction) {
                    if ($objAction instanceof FlowActionUserInputInterface) {
                        $objForm->addField(new FormentryHeadline())->setStrValue($objAction->getTitle());
                        $objAction->configureUserInputForm($objForm);
                        $bitInputRequired = true;
                    }
                }

                if ($bitInputRequired) {
                    if ($_SERVER["REQUEST_METHOD"] == "GET" || !$objForm->validateForm()) {
                        $strForm = $objForm->renderForm(Link::getLinkAdminHref($this->getArrModule("modul"), "setStatus", "&systemid=" . $objObject->getStrSystemid() . "&transition_id=" . $strTransitionId));
                        return $strForm;
                    } else {
                        foreach ($arrActions as $objAction) {
                            if ($objAction instanceof FlowActionUserInputInterface) {
                                $objActionForm = new AdminFormgenerator("", $objObject);
                                $objAction->configureUserInputForm($objActionForm);
                                $objAction->handleUserInput($objObject, $objTransition, $objActionForm);

                                // in case the handleUserInput added a validation error
                                if (!$objActionForm->validateForm()) {
                                    $strForm = $objActionForm->renderForm(Link::getLinkAdminHref($this->getArrModule("modul"), "setStatus", "&systemid=" . $objObject->getStrSystemid() . "&transition_id=" . $strTransitionId));
                                    return $strForm;
                                }
                            }
                        }
                    }
                }

                $objHandler = $objFlow->getHandler();

                try {
                    $objHandler->handleStatusTransition($objObject, $objTransition);
                    $this->adminReload(Link::getLinkAdminHref($this->getArrModule("modul"), "list", "&systemid=" . $objObject->getStrPrevId()));
                } catch (RedirectException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
                    return $objToolkit->warningBox($e->getMessage());
                }
            }
        }

        return "";
    }

    /**
     * Renders the status menu
     *
     * @return string
     * @permissions view
     * @responseType html
     */
    protected function actionShowStatusMenu()
    {
        Xml::setBitSuppressXmlHeader(true);

        $objObject = Objectfactory::getInstance()->getObject($this->getSystemid());

        if ($objObject->getIntRecordDeleted() == 1) {
            return "";
        }

        if (!$objObject->rightView()) {
            return "<ul><li class='dropdown-header'>" . $this->getLang("list_flow_no_right", "flow") . "</li></ul>";
        }

        $strClass = $objObject->getSystemid() . "-errors";
        $arrTransitions = $this->objFlowManager->getPossibleTransitionsForModel($objObject, false);
        $objFlow = $this->objFlowManager->getFlowForModel($objObject);

        // check right
        if ($objObject instanceof FlowModelRightInterface) {
            $bitHasRight = $objObject->rightStatus();
        } else {
            $bitHasRight = $objObject->rightEdit();
        }

        $actionItems = [];
        $statusItems = [];

        if (!empty($arrTransitions) && $bitHasRight) {
            foreach ($arrTransitions as $objTransition) {
                // skip if not visible
                if (!$objTransition->isVisible()) {
                    continue;
                }

                /** @var FlowTransition $objTransition */
                $objTargetStatus = $objTransition->getTargetStatus();

                if ($objTargetStatus === null) {
                    continue;
                }

                // validation
                $objResult = $objFlow->getHandler()->validateStatusTransition($objObject, $objTransition, true);

                if (!$objResult->isValid()) {
                    $link = Link::getLinkAdminDialog($objObject->getArrModule("module"), "showTransitionErrors", ["systemid" => $objObject->getSystemid(), "transition_id" => $objTransition->getSystemid()], AdminskinHelper::getAdminImage("icon_flag_hex_disabled_" . $objTargetStatus->getStrIconColor()) . " " . $objTargetStatus->getStrDisplayName());

                    $menuItem = new MenuItem();
                    $menuItem->setFullEntry($link);
                    $statusItems[] = $menuItem;
                } else {
                    $link = Link::getLinkAdminHref($this->getArrModule("modul"), "setStatus", ["systemid" => $objObject->getStrSystemid(), "transition_id" => $objTransition->getSystemid()]);

                    $menuItem = new MenuItem();
                    $menuItem->setName(AdminskinHelper::getAdminImage($objTargetStatus->getStrIcon()) . " " . $objTargetStatus->getStrDisplayName());
                    $menuItem->setLink($link);
                    $statusItems[] = $menuItem;
                }

                $actionItems = array_merge($actionItems, $objResult->getMenuItems());
            }
        }

        $menu = new Menu();

        // flow chart
        $currentStatus = $objFlow->getStatusByIndex($objObject->getIntRecordStatus());
        $menu->addItem(new Dialog($currentStatus->getStrDisplayName(), Link::getLinkAdminHref("flow", "showFlow", ["systemid" => $this->getSystemid(), "folderview" => "1"]), $currentStatus->getStrIcon()));

        if (count($statusItems) > 0) {
            // status
            $menu->addItem(new Separator());
            $menu->addItem(new Headline($this->getLang("flow_controller_trait_headline_status", "flow")));
            $menu->addItems($statusItems);

            if (count($actionItems) > 0) {
                // actions
                $menu->addItem(new Separator());
                $menu->addItem(new Headline($this->getLang("flow_controller_trait_headline_action", "flow")));
                $menu->addItems($actionItems);
            }
        } else {
            $menu->addItem(new Separator());
            $menu->addItem(new Headline($this->getLang("list_flow_no_status", "flow")));
        }

        $menu->setRenderMenuContainer(false);
        $strHtml = $menu->renderComponent();

        return $strHtml;
    }

    /**
     * @permissions view
     * @return string
     */
    protected function actionShowTransitionErrors()
    {
        $object = Objectfactory::getInstance()->getObject($this->getSystemid());
        $transition = Objectfactory::getInstance()->getObject($this->getParam("transition_id"));

        $flow = $this->objFlowManager->getFlowForModel($object);

        // validation
        $result = $flow->getHandler()->validateStatusTransition($object, $transition);

        $return = "";
        $errors = $result->getErrors();
        if (!empty($errors)) {
            $return = "<div class='alert alert-danger'>";
            $return.= "<ul>";
            foreach ($errors as $error) {
                if (!empty($error)) {
                    $return.= "<li>{$error}</li>";
                }
            }
            $return.= "</ul>";
            $return.= "</div>";
        }

        $menuItems = $result->getMenuItems();
        if (!empty($menuItems)) {
            $return .= "<div class='alert alert-info'>";
            $return.= "<ul>";
            foreach ($menuItems as $item) {
                $link = Link::getLinkAdminManual('href="'.$item->getLink().'" target="_parent"', $item->getName());
                $return.= "<li>{$link}</li>";
            }
            $return.= "</ul>";
            $return.= "</div>";
        }

        return $return;
    }

    /**
     * Ajax endpoint to trigger a status transition
     *
     * @return array
     * @permissions view
     * @responseType json
     * @xml
     */
    protected function actionApiSetFlowStatus()
    {
        $objObject = $this->objFactory->getObject($this->getSystemid());
        $objAlert = null;

        if ($objObject instanceof Model) {
            // check right
            if ($objObject instanceof FlowModelRightInterface) {
                $bitHasRight = $objObject->rightStatus();
            } else {
                $bitHasRight = $objObject->rightEdit();
            }

            if (!$bitHasRight) {
                return [
                    "success" => false,
                    "message" => $this->getLang("commons_error_permissions", "commons")
                ];
            }

            $strTransitionId = $this->getParam("transition_id");
            $objFlow = $this->objFlowManager->getFlowForModel($objObject);
            $objTransition = Objectfactory::getInstance()->getObject($strTransitionId);

            if ($objTransition instanceof FlowTransition) {
                $arrActions = $objTransition->getArrActions();
                $objForm = new AdminFormgenerator("", $objObject);
                $bitInputRequired = false;

                foreach ($arrActions as $objAction) {
                    if ($objAction instanceof FlowActionUserInputInterface) {
                        $objForm->addField(new FormentryHeadline())->setStrValue($objAction->getTitle());
                        $objAction->configureUserInputForm($objForm);
                        $bitInputRequired = true;
                    }
                }

                if ($bitInputRequired) {
                    // in this case an action needs additional user input so we redirect the user to the form
                    $strRedirect = Link::getLinkAdminHref($this->getArrModule("modul"), "setStatus", "&systemid=" . $objObject->getStrSystemid() . "&transition_id=" . $strTransitionId);

                    $objAlert = new MessagingAlert();
                    $objAlert->setStrTitle($this->getLang("action_status_change_title", "flow"));
                    $objAlert->setStrBody($this->getLang("action_status_change_redirect", "flow"));
                    $objAlert->setObjAlertAction(new MessagingAlertActionRedirect($strRedirect));
                } else {
                    try {
                        // validation
                        $objResult = $objFlow->getHandler()->validateStatusTransition($objObject, $objTransition);

                        if (!$objResult->isValid()) {
                            $arrErrors = $objResult->getErrors();
                            if (!empty($arrErrors)) {
                                $strTooltip = "<ul>";
                                foreach ($arrErrors as $strError) {
                                    if (!empty($strError)) {
                                        $strError = htmlspecialchars($strError);
                                        $strTooltip.= "<li>{$strError}</li>";
                                    }
                                }
                                $strTooltip.= "</ul>";

                                $objAlert = new MessagingAlert();
                                $objAlert->setStrTitle($this->getLang("action_status_change_title", "flow"));
                                $objAlert->setStrBody($this->objToolkit->warningBox($strTooltip, "alert-danger"));
                                $objAlert->setObjAlertAction(new MessagingAlertActionVoid());
                            }
                        } else {
                            // execute status transition
                            $objFlow->getHandler()->handleStatusTransition($objObject, $objTransition);

                            $objAlert = new MessagingNotification();
                            $objAlert->setStrTitle($this->getLang("action_status_change_title", "flow"));
                            $objAlert->setStrBody($this->getLang("action_status_change_success", "flow"));
                            $objAlert->setObjAlertAction(new MessagingAlertActionVoid());

                            $strRedirectUrl = ResponseObject::getInstance()->getStrRedirectUrl();
                            if (!empty($strRedirectUrl)) {
                                $objAlert->setObjAlertAction(new MessagingAlertActionRedirect($strRedirectUrl));
                                ResponseObject::getInstance()->setStrRedirectUrl("");
                            }
                        }

                    } catch (RedirectException $e) {
                        $objAlert = new MessagingNotification();
                        $objAlert->setStrTitle($this->getLang("action_status_change_title", "flow"));
                        $objAlert->setStrBody($this->getLang("action_status_change_success", "flow"));
                        $objAlert->setObjAlertAction(new MessagingAlertActionRedirect($e->getHref()));
                    } catch (\Exception $e) {
                        $objAlert = new MessagingAlert();
                        $objAlert->setStrTitle($this->getLang("action_status_change_title", "flow"));
                        $objAlert->setStrBody($this->objToolkit->warningBox($e->getMessage(), "alert-danger"));
                        $objAlert->setObjAlertAction(new MessagingAlertActionVoid());
                    }
                }
            }
        }

        if ($objAlert instanceof MessagingAlert) {
            $objAlert->setIntPriority(9);
            $this->objMessageHandler->sendAlertToUser($objAlert, $this->objSession->getUser());
        }

        $status = $this->objFlowManager->getCurrentStepForModel($objObject);
        $icon = $this->objToolkit->listButton(AdminskinHelper::getAdminImage($status->getStrIcon(), $status->getStrDisplayName()));

        return [
            "success" => true,
            "actions" => [
                (new MessagingAlertActionUpdateStatus($objObject->getSystemid(), $icon))->getAsActionArray()
            ]
        ];
    }
}
