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
 * FlowConditionAbstract
 *
 * @author christoph.kappestein@artemeon.de
 * @targetTable agp_flow_trans_condition.condition_id
 * @module flow
 * @moduleId _flow_module_id_
 * @formGenerator Kajona\Flow\Admin\FlowConditionFormgenerator
 */
abstract class FlowConditionAbstract extends Model implements ModelInterface, AdminListableInterface, FlowConditionInterface
{
    /**
     * @var string
     * @tableColumn agp_flow_trans_condition.condition_params
     * @tableColumnDatatype text
     * @blockEscaping
     */
    protected $strParams;

    /**
     * @var array
     */
    private $arrParameters;

    /**
     * @return string
     */
    public function getStrParams()
    {
        return $this->strParams;
    }

    /**
     * @param string $strParams
     */
    public function setStrParams($strParams)
    {
        $this->strParams = $strParams;
    }

    /**
     * @return array
     */
    public function getArrParameters()
    {
        return $this->arrParameters === null ? $this->arrParameters = json_decode($this->strParams, true) : $this->arrParameters;
    }

    /**
     * @param string $strName
     * @return string|null
     */
    public function getParameter(string $strName)
    {
        $arrParameters = $this->getArrParameters();
        return isset($arrParameters[$strName]) ? $arrParameters[$strName] : null;
    }

    /**
     * Returns all child conditions
     *
     * @return FlowConditionInterface[]
     */
    protected function getChildConditions()
    {
        return FlowConditionAbstract::getObjectListFiltered(null, $this->getSystemid());
    }

    /**
     * @return string
     */
    public function getStrIcon()
    {
        return "icon_document";
    }

    /**
     * @return string
     */
    public function getStrDisplayName()
    {
        return $this->getTitle();
    }

    public function getStrAdditionalInfo()
    {
        return chunk_split($this->getStrParams(), 50);
    }

    public function getStrLongDescription()
    {
        return "";
    }

    /**
     * @return FlowTransition
     */
    public function getTransition()
    {
        return Objectfactory::getInstance()->getObject($this->getPrevId());
    }
}
