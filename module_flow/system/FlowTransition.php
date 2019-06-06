<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Flow\System;

use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;

/**
 * FlowTransition
 *
 * @author christoph.kappestein@artemeon.de
 * @targetTable agp_flow_step_transition.transition_id
 * @module flow
 * @moduleId _flow_module_id_
 * @formGenerator Kajona\Flow\Admin\FlowTransitionFormgenerator
 */
class FlowTransition extends Model implements ModelInterface, AdminListableInterface
{
    /**
     * @var string
     * @tableColumn agp_flow_step_transition.target_step
     * @tableColumnDatatype char254
     * @fieldType Kajona\System\Admin\Formentries\FormentryDropdown
     * @fieldMandatory
     */
    protected $strTargetStatus;

    /**
     * @var int
     * @tableColumn agp_flow_step_transition.transition_visible
     * @tableColumnDatatype int
     * @fieldType Kajona\System\Admin\Formentries\FormentryDropdown
     * @fieldDDValues [0 => transition_visible_0],[1 => transition_visible_1]
     * @fieldMandatory
     */
    protected $intVisible = 1;

    /**
     * Basically this property is a filter for the getNextTransitionForModel method, in case it is set to 1 the method
     * does not consider this transition
     *
     * @see FlowManager::getNextTransitionForModel
     * @var int
     * @tableColumn agp_flow_step_transition.transition_skip
     * @tableColumnDatatype int
     * @fieldType Kajona\System\Admin\Formentries\FormentryDropdown
     * @fieldDDValues [0 => transition_skip_0],[1 => transition_skip_1]
     * @fieldMandatory
     */
    protected $intSkip = 0;

    /**
     * This field contains parameters from the user input action
     *
     * @var array
     */
    protected $arrParams;

    /**
     * @return string
     */
    public function getStrTargetStatus()
    {
        return $this->strTargetStatus;
    }

    /**
     * @param string $strTargetStatus
     */
    public function setStrTargetStatus(string $strTargetStatus)
    {
        $this->strTargetStatus = $strTargetStatus;
    }

    /**
     * @return int
     */
    public function getIntVisible()
    {
        return $this->intVisible;
    }

    /**
     * @param int $intVisible
     */
    public function setIntVisible($intVisible)
    {
        $this->intVisible = $intVisible;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return !!$this->intVisible;
    }

    /**
     * @return int
     */
    public function getIntSkip()
    {
        return $this->intSkip;
    }

    /**
     * @param int $intSkip
     */
    public function setIntSkip($intSkip)
    {
        $this->intSkip = $intSkip;
    }

    /**
     * @return bool
     */
    public function shouldSkip()
    {
        return !!$this->intSkip;
    }

    /**
     * @return array
     */
    public function getArrParams()
    {
        return $this->arrParams;
    }

    /**
     * @param array $arrParams
     * @deprecated - please use setParameter since this methods overwrites all params from previous actions
     */
    public function setArrParams(array $arrParams)
    {
        $this->arrParams = $arrParams;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setParameter($key, $value)
    {
        $this->arrParams[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getParameter($key)
    {
        return $this->arrParams[$key] ?? null;
    }

    /**
     * @return FlowStatus
     */
    public function getParentStatus()
    {
        return Objectfactory::getInstance()->getObject($this->getPrevId());
    }

    /**
     * @return FlowStatus
     */
    public function getTargetStatus()
    {
        return Objectfactory::getInstance()->getObject($this->strTargetStatus);
    }

    /**
     * @return FlowActionAbstract[]
     */
    public function getArrActions()
    {
        return FlowActionAbstract::getObjectListFiltered(null, $this->getSystemid());
    }

    /**
     * @return FlowConditionAbstract[]
     */
    public function getArrConditions()
    {
        return FlowConditionAbstract::getObjectListFiltered(null, $this->getSystemid());
    }

    /**
     * @return string
     */
    public function getStrIcon()
    {
        return $this->getTargetStatus() ? $this->getTargetStatus()->getStrIcon() : null;
    }

    /**
     * @return string
     */
    public function getStrIconColor()
    {
        return $this->getTargetStatus() ? $this->getTargetStatus()->getStrIconColor() : null;
    }


    /**
     * @return string
     */
    public function getStrDisplayName()
    {
        return $this->getTargetStatus() ? $this->getTargetStatus()->getStrName() : null;
    }

    public function getStrAdditionalInfo()
    {
        return "";
    }

    public function getStrLongDescription()
    {
        return "";
    }

    /**
     * Checks whether the transition moves the status forward or backwards in the flow
     *
     * @deprecated - in look back this method makes no sense please dont use it
     */
    public function isForwarding()
    {
        $objParentStatus = $this->getParentStatus();
        $arrStatus = $objParentStatus->getFlowConfig()->getArrStatus();
        $arrStatus = array_map(function(FlowStatus $objStatus){
            return $objStatus->getIntIndex();
        }, $arrStatus);

        $intCurrentIndex = $objParentStatus->getIntIndex();
        $intFutureIndex = $this->getTargetStatus()->getIntIndex();

        return array_search($intFutureIndex, $arrStatus) > array_search($intCurrentIndex, $arrStatus);
    }
}
