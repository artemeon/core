<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Flow\System;

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
    public function getPossibleStatusForModel(Model $objObject) : array
    {
        return $this->getPossibleStatusForClass(get_class($objObject));
    }

    /**
     * @param string $objObject
     * @return array
     */
    public function getPossibleStatusForClass(string $objObject) : array
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
     * Returns all available status transition for the model
     *
     * @param Model $objObject
     * @param bool $bitValidateConditions
     * @return FlowTransition[]
     */
    public function getPossibleTransitionsForModel(Model $objObject, $bitValidateConditions = true) : array
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
     * Returns the next transition which can be used if we want to automatically set the next status for the object
     *
     * @param Model $objObject
     * @return FlowTransition|false
     */
    public function getNextTransitionForModel(Model $objObject)
    {
        $arrTransitions = $this->getPossibleTransitionsForModel($objObject);
        return reset($arrTransitions);
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
}
