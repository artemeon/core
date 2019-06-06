<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Flow\System;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\AdminFormgeneratorFactory;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\Database;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Logger;
use Kajona\System\System\Model;
use Kajona\System\System\RedirectException;

/**
 * The status handler contains all informations about the status flow. Through the status handler we can move the model
 * to the next state and get a list of available status transitions. So we have one handler object which can have
 * multiple status options.
 *
 * @author christoph.kappestein@artemeon.de
 * @author stefan.meyer@artemeon.de
 * @module flow
 */
abstract class FlowHandlerAbstract implements FlowHandlerInterface
{
    /**
     * @var FlowManager
     */
    protected $objFlowManager;

    /**
     * @var ServiceLifeCycleFactory
     */
    protected $objLifeCycleFactory;

    /**
     * @param FlowManager $objFlowManager
     * @param ServiceLifeCycleFactory $objLifeCycleFactory
     */
    public function __construct(FlowManager $objFlowManager, ServiceLifeCycleFactory $objLifeCycleFactory)
    {
        $this->objFlowManager = $objFlowManager;
        $this->objLifeCycleFactory = $objLifeCycleFactory;
    }

    /**
     * Handles a status transition
     *
     * @param Model $objObject
     * @param FlowTransition $objTransition
     * @return boolean - true if transition is executed, false if not
     * @throws \Kajona\System\System\Exception
     */
    public function handleStatusTransition(Model $objObject, FlowTransition $objTransition) : bool
    {
        try {
            Database::getInstance()->transactionBegin();

            // check whether the object has the correct status
            if ($objObject->getIntRecordStatus() != $objTransition->getParentStatus()->getIntIndex()) {
                throw new \RuntimeException("Object is not in status " . $objTransition->getParentStatus()->getStrName());
            }

            $intNewStatus = $objTransition->getTargetStatus()->getIntStatus();

            if ($intNewStatus != $objObject->getIntRecordStatus()) {
                // check whether there are validation errors
                $objResult = $this->validateStatusTransition($objObject, $objTransition);
                if (!$objResult->isValid()) {
                    throw new \RuntimeException("There are condition errors for this status transition, errors: ".implode(", ", $objResult->getErrors()));
                }

                // check whether the transition is visible
                $bitReturn = $this->isTransitionVisible($objObject, $objTransition);
                if (!$bitReturn) {
                    throw new \RuntimeException("Transition is not visible");
                }

                // set new status
                $objObject->setIntRecordStatus($intNewStatus);

                // persist the new status
                $this->objLifeCycleFactory->factory(get_class($objObject))->update($objObject);

                // execute transition actions
                $this->executeActions($objObject, $objTransition);

                // execute handler actions
                $redirectException = null;
                try {
                    $this->executeStatusTransition($objObject, $objTransition);
                } catch (RedirectException $e) {
                    // the handle can throw redirect exceptions. Since we want to execute the event after the handler
                    // code was executed we catch here the redirect exception and throw them after the event
                    $redirectException = $e;
                }

                // trigger transition executed event
                CoreEventdispatcher::getInstance()->notifyGenericListeners(FlowEventidentifier::EVENT_TRANSITION_EXECUTED, [$objObject, $objTransition]);

                // throw redirect exception if available
                if ($redirectException instanceof RedirectException) {
                    throw $redirectException;
                }
            }

            Database::getInstance()->transactionCommit();
        } catch (RedirectException $e) {
            Database::getInstance()->transactionCommit();

            throw $e;
        } catch (\Exception $e) {
            Database::getInstance()->transactionRollback();

            Logger::getInstance(Logger::SYSTEMLOG)->addLogRow("Status-Transition error: " . $e->getMessage() . "\n" . $e->getTraceAsString(), Logger::$levelError);
            throw $e;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function validateStatusTransition(Model $objObject, FlowTransition $objTransition, bool $shortCircuit = false) : FlowConditionResult
    {
        $objResult = new FlowConditionResult();

        $objResultCondition = $this->validateTransitionsConditions($objObject, $objTransition, $shortCircuit);

        if ($shortCircuit && !$objResultCondition->isValid()) {
            return $objResultCondition;
        }

        $objResultForms = $this->validateObjectForm($objObject, $objTransition);

        if ($shortCircuit && !$objResultForms->isValid()) {
            return $objResultForms;
        }

        $objResult->merge($objResultForms);
        $objResult->merge($objResultCondition);

        return $objResult;
    }

    /**
     * Validate the object itself by creating a form and validating it.
     *
     * 1. Validates the form of the given in the current status
     * 2. if errors occur, additionally validate form of target status
     *
     * @param Model $objObject
     * @param FlowTransition $objTransition
     * @return FlowConditionResult
     */
    protected function validateObjectForm(Model $objObject, FlowTransition $objTransition)
    {
        $objResult = new FlowConditionResult();

        //validate if form is valid in current status
        $objForm = AdminFormgeneratorFactory::createByModel($objObject);
        $objForm->validateForm();
        $arrErrors = $objForm->getArrValidationErrors();

        if (!empty($arrErrors)) {
            //validate if form is valid in target status
            $intTargetStatus = $objTransition->getTargetStatus()->getIntIndex();
            $objTmpObject = clone $objObject;
            $objTmpObject->setIntRecordStatus($intTargetStatus);

            $objForm = AdminFormgeneratorFactory::newFormGenerator($objTmpObject);
            $objForm->validateForm();
            $arrTargetErrors = $objForm->getArrValidationErrors();

            foreach ($arrTargetErrors as $arrErr) {
                foreach ($arrErr as $strError) {
                    $objResult->addError($strError);
                }
            }
        }

        return $objResult;
    }

    /**
     * Validate the conditions which are configured for the given FlowTransition
     *
     * @param Model $objObject
     * @param FlowTransition $objTransition
     * @param bool $shortCircuit
     * @return FlowConditionResult
     */
    private function validateTransitionsConditions(Model $objObject, FlowTransition $objTransition, bool $shortCircuit = false)
    {
        $objResult = new FlowConditionResult();

        // validate all assigned conditions
        $arrConditions = $objTransition->getArrConditions();
        if (!empty($arrConditions)) {
            foreach ($arrConditions as $objCondition) {
                if ($objCondition instanceof FlowConditionInterface) {
                    $objResult->merge($objCondition->validateCondition($objObject, $objTransition));

                    if ($shortCircuit && !$objResult->isValid()) {
                        return $objResult;
                    }
                }
            }
        }

        return $objResult;
    }

    /**
     * Callback method which can be overridden by a handler to validate whether a status transition is possible. The
     * transition is not listed in the status drop down if this method returns false
     *
     * @param Model $objObject
     * @param FlowTransition $objTransition
     * @return bool
     */
    public function isTransitionVisible(Model $objObject, FlowTransition $objTransition) : bool
    {
        return true;
    }

    /**
     * Callback method which can be overridden by a handler to execute additional actions on a status transition
     *
     * @param Model $objObject
     * @param FlowTransition $objTransition
     */
    protected function executeStatusTransition(Model $objObject, FlowTransition $objTransition)
    {
    }

    /**
     * @param Model $objObject
     * @param FlowTransition $objTransition
     */
    private function executeActions(Model $objObject, FlowTransition $objTransition)
    {
        $arrActions = $objTransition->getArrActions();
        if (!empty($arrActions)) {
            // sort actions
            $preActions = [];
            $actions = [];
            $postActions = [];
            foreach ($arrActions as $action) {
                if ($action->getOrder() === FlowActionInterface::ORDER_PRE) {
                    $preActions[] = $action;
                } elseif ($action->getOrder() === FlowActionInterface::ORDER_POST) {
                    $postActions[] = $action;
                } else {
                    $actions[] = $action;
                }
            }

            $sortedActions = array_merge($preActions, $actions, $postActions);
            foreach ($sortedActions as $objAction) {
                if ($objAction instanceof FlowActionInterface) {
                    $objAction->executeAction($objObject, $objTransition);
                }
            }
        }
    }

    /**
     * Returns the name of extension/plugin the objects wants to contribute to.
     *
     * @return string
     */
    public static function getExtensionName()
    {
        return self::EXTENSION_POINT;
    }
}

