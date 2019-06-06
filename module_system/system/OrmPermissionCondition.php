<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;


/**
 * A orm condition to filter by rights and usergroup assignment.
 *
 *
 * @package Kajona\System\System
 * @author stefan.meyer1@yahoo.de
 * @since 6.0
 *
 * @deprecated move to the OrmViewPermissionCondition
 */
class OrmPermissionCondition extends OrmCondition
{

    private $arrUserGroupIds = null;
    private $strPermission = null;
    private $strColumn = null;
    private $objCompositeCondition = null;

    /**
     * OrmPermissionCondition constructor.
     *
     * @param string $strPermission
     * @param array|null $arrUserGroupIds
     * @param null $strColumn - optional, set if different column is being used
     * @throws Exception
     */
    public function __construct($strPermission, array $arrUserGroupIds = null, $strColumn = null)
    {
        parent::__construct("", array());

        $this->arrUserGroupIds = $arrUserGroupIds;
        $this->strPermission = $strPermission;

        if ($this->arrUserGroupIds === null) {
            $this->arrUserGroupIds = Carrier::getInstance()->getObjSession()->getShortGroupIdsAsArray();
        } else {
            $this->arrUserGroupIds = array_map(function ($strSytemid) {
                return UserGroup::getShortIdForGroupId($strSytemid);
            }, $this->arrUserGroupIds);
        }

        if ($strColumn == null) {
            $this->strColumn = "right_".$strPermission;
        } else {
            $this->strColumn = $strColumn;
        }
    }

    /**
     * Generates the compound condition for the condition
     *
     * @return OrmCondition
     */
    private function generateCompoundCondition()
    {
        $strLikeOperator = OrmComparatorEnum::Like;

        $arrConditions = [];
        $arrParams = [];
        foreach ($this->arrUserGroupIds as $strUserGroupId) {
            $arrConditions[] = "{$this->strColumn} {$strLikeOperator}  ? ";
            $arrParams[] = "%,".$strUserGroupId.",%";
        }

        if (empty($arrParams)) {
            return new OrmCompositeCondition();
        }

        return new OrmCondition(" ( ". implode(" OR ", $arrConditions)." ) ", $arrParams);
    }

    /**
     * @inheritdoc
     */
    public function getStrWhere()
    {
        if ($this->objCompositeCondition === null) {
            $this->objCompositeCondition = $this->generateCompoundCondition();
        }

        return $this->objCompositeCondition->getStrWhere();
    }

    /**
     * @inheritdoc
     */
    public function getArrParams()
    {
        if ($this->objCompositeCondition === null) {
            $this->objCompositeCondition = $this->generateCompoundCondition();
        }

        return $this->objCompositeCondition->getArrParams();
    }


}
