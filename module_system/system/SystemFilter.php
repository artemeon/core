<?php
/*"******************************************************************************************************
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;


/**
 * Base filter class
 *
 * @author stefan.idler@artemeon.de
 * @module system
*/
class SystemFilter extends FilterBase
{
    /**
     * @var string
     * @tableColumn agp_system.system_class
     * @filterCompareOperator EQ
     */
    private $class = null;

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class)
    {
        $this->class = $class;
    }

}
