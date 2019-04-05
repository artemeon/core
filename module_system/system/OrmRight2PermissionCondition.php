<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;


/**
 * A orm condition to filter by right2 permission for the currently logged in user
 *
 *
 * @package Kajona\System\System
 * @author stefan.idler@artemeon.de
 * @since 7.2
 */
class OrmRight2PermissionCondition extends OrmCondition
{
    private $strColumn = null;

    /**
     * @var OrmPermissionCondition
     */
    private $fallback;

    /**
     * OrmPermissionCondition constructor.
     *
     * @param string $column the column to query against
     * @throws Exception
     */
    public function __construct($column = "system.system_id")
    {
        parent::__construct("", array());
        $this->strColumn = $column;

        if (count(Session::getInstance()->getShortGroupIdsAsArray()) < SystemSetting::getConfigValue("_system_permission_assignment_threshold_")) {
            //fall back to the simple like logic for small amount of data
            $this->fallback = new OrmPermissionCondition(Rights::$STR_RIGHT_RIGHT2, null, StringUtil::replace("system_id", "right_right2", $column));
        }
    }


    /**
     * @inheritdoc
     */
    public function getStrWhere()
    {
        if ($this->fallback !== null) {
            return $this->fallback->getStrWhere();
        }

        return "
        {$this->strColumn} IN (
          SELECT right2_id
          FROM "._dbprefix_."permissions_right2, "._dbprefix_."user_group, "._dbprefix_."user_kajona_members
          WHERE right2_shortgroup = group_short_id AND group_id = group_member_group_kajona_id AND group_member_user_kajona_id = ? AND right2_id = {$this->strColumn}
        )
";
    }

    /**
     * @inheritdoc
     */
    public function getArrParams()
    {

        if ($this->fallback !== null) {
            return $this->fallback->getArrParams();
        }

        return [Session::getInstance()->getUserID()];
    }

}
