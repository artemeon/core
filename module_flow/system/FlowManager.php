<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Flow\System;

use InvalidArgumentException;
use Kajona\System\System\Model;

/**
 * @author christoph.kappestein@artemeon.de
 * @module flow
 */
class FlowManager
{
    /**
     * Internal cache
     *
     * @var array
     */
    private $arrFlows;

    /**
     * FlowManager constructor.
     */
    public function __construct()
    {
        $this->arrFlows = [];
    }

    /**
     * Returns an associative array<status index => status name>
     *
     * @param Model $objObject
     * @return array
     */
    public function getArrStatusForModel(Model $objObject): array
    {
        return $this->getArrStatusForClass(get_class($objObject));
    }

    /**
     * @param string $objObject
     * @return array
     */
    public function getArrStatusForClass(string $objObject): array
    {
        $objFlow = $this->getFlowForClass($objObject);
        if ($objFlow instanceof FlowConfig) {
            $arrStatus = $objFlow->getArrStatus();
            $arrResult = [];
            foreach ($arrStatus as $objStatus) {
                $arrResult[$objStatus->getIntIndex()] = $objStatus->getStrDisplayName();
            }
            return $arrResult;
        } else {
            return [];
        }
    }

    /**
     * Returns all available transitions which are valid for the current model status. This means
     * that the assigned conditions are validated. If the argument bitValidateConditions is false
     * all visible transitions are returned
     *
     * @param Model $objObject
     * @param bool $bitValidateConditions
     * @return FlowTransition[]
     */
    public function getPossibleTransitionsForModel(Model $objObject, $bitValidateConditions = true): array
    {
        $objStep = $this->getCurrentStepForModel($objObject);
        if ($objStep instanceof FlowStatus) {
            $objHandler = $objStep->getFlowConfig()->getHandler();
            $arrTransitions = $objStep->getArrTransitions();
            $arrResult = [];

            // filter out transitions where the condition is not valid
            foreach ($arrTransitions as $objTransition) {
                // validate conditions
                if ($bitValidateConditions) {
                    $objResult = $objHandler->validateStatusTransition($objObject, $objTransition);
                    if (!$objResult->isValid()) {
                        continue;
                    }
                }

                // ask the handler whether this transition is visible
                if (!$objHandler->isTransitionVisible($objObject, $objTransition)) {
                    continue;
                }

                $arrResult[] = $objTransition;
            }

            return $arrResult;
        }

        return [];
    }

    /**
     * Returns the next transition which can be used if we want to automatically set the next status for the object.
     * Note we return only a transition in case we have exactly one possible transition otherwise the method returns
     * null
     *
     * @param Model $objObject
     * @return FlowTransition|null
     */
    public function getNextTransitionForModel(Model $objObject)
    {
        $arrTransitions = $this->getPossibleTransitionsForModel($objObject);

        // filter out transition which skip the auto trigger
        $arrTransitions = array_filter($arrTransitions, function (FlowTransition $objTransition) {
            return !$objTransition->shouldSkip();
        });

        // check whether the transition was skipped by a session
        $arrTransitions = array_filter($arrTransitions, function (FlowTransition $objTransition) use ($objObject) {
            return !SessionTransitionSkip::shouldSkip($objObject, $objTransition->getTargetStatus());
        });

        if (count($arrTransitions) == 1) {
            return current($arrTransitions);
        } else {
            return null;
        }
    }

    /**
     * @param Model $objObject
     * @return FlowStatus|null
     */
    public function getCurrentStepForModel(Model $objObject)
    {
        $objFlow = $this->getFlowForModel($objObject);
        if ($objFlow instanceof FlowConfig) {
            return $objFlow->getStatusByIndex($objObject->getIntRecordStatus());
        }
        return null;
    }

    /**
     * Returns the flow config for this model or null
     *
     * @param Model $objObject
     * @return FlowConfig|null
     */
    public function getFlowForModel(Model $objObject)
    {
        return $this->getFlowForClass(get_class($objObject));
    }

    /**
     * Returns the flow config for the given class
     *
     * @param string $strClass
     * @return FlowConfig|null
     */
    public function getFlowForClass(string $strClass)
    {
        if (!isset($this->arrFlows[$strClass])) {
            $objFlow = FlowConfig::getByModelClass($strClass);
            if ($objFlow instanceof FlowConfig) {
                $this->arrFlows[$strClass] = $objFlow;
            } else {
                return null;
            }
        }
        return $this->arrFlows[$strClass];
    }

    /**
     * Returns if a flow is configured for the given class
     *
     * @param string $strClass
     * @return bool
     */
    public function isFlowConfiguredForClass($strClass)
    {
        if (isset($this->arrFlows[$strClass])) {
            return true;
        }

        $objFlow = FlowConfig::getByModelClass($strClass);
        if (!($objFlow instanceof FlowConfig)) {
            return false;
        }

        $this->arrFlows[$strClass] = $objFlow;

        return true;
    }

    /**
     * Executes an action with the given classname of the transition from sourceindex to targetindex
     *
     * @param int $intSourceIndex
     * @param int $intTargetndex
     * @param Model $objModel
     * @param string $strActionClassName
     * @throws InvalidArgumentException
     *
     */
    public function executeTransitionAction(int $intSourceIndex, int $intTargetndex, Model $objModel, string $strActionClassName)
    {
        $objFlow = $this->getFlowForModel($objModel);
        if ($objFlow === null) {
            throw new InvalidArgumentException("Flow not found for model " . get_class($objModel));
        }

        $objStatus = $objFlow->getStatusByIndex($intSourceIndex);
        if ($objStatus === null) {
            throw new InvalidArgumentException("Status not found for source index $intSourceIndex ");
        }

        $objTransition = $objStatus->getTransitionByTargetIndex($intTargetndex);
        if ($objTransition === null) {
            throw new InvalidArgumentException("Transition not found for target index $intTargetndex");
        }

        //Now try to execute the action
        $arrActions = $objTransition->getArrActions();
        foreach ($arrActions as $objAction) {
            if ($objAction instanceof $strActionClassName) {
                $objAction->executeAction($objModel, $objTransition);
            }
        }
    }
}
