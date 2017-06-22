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
 * FlowActionAbstract
 *
 * @author christoph.kappestein@artemeon.de
 * @targetTable flow_trans_action.action_id
 * @module flow
 * @moduleId _flow_module_id_
 * @formGenerator Kajona\Flow\Admin\FlowActionFormgenerator
 */
abstract class FlowActionAbstract extends Model implements ModelInterface, AdminListableInterface, FlowActionInterface
{
    /**
     * @var string
     * @tableColumn flow_trans_action.action_params
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
    public function getStrParams(): string
    {
        return $this->strParams;
    }

    /**
     * @param string $strParams
     */
    public function setStrParams(string $strParams)
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
     * @return string
     */
    public function getStrIcon()
    {
        return "icon_play";
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
        $arrParams = $this->getArrParameters();
        $arrParts = [];
        foreach ($arrParams as $strKey => $strValue) {
            $arrParts[] = $strKey . ": " . $strValue;
        }
        return implode(", ", $arrParts);
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
