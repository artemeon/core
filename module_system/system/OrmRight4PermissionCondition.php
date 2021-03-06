<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;


/**
 * A orm condition to filter by right4 permission for the currently logged in user
 *
 *
 * @package Kajona\System\System
 * @author laura.albersmann@artemeon.de
 * @since 7.2
 */
class OrmRight4PermissionCondition extends OrmCondition
{

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
    public function __construct($column = "agp_system.system_id")
    {
        parent::__construct("", array());

        //fall back to the simple like logic for small amount of data
        $this->fallback = new OrmPermissionCondition(Rights::$STR_RIGHT_RIGHT4, null, "agp_system.right_right4");

    }


    /**
     * @inheritdoc
     */
    public function getStrWhere()
    {
        return $this->fallback->getStrWhere();
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