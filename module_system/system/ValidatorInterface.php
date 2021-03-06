<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/


namespace Kajona\System\System;

/**
 * A validator is used to validate a chunk of data.
 * In most cases, validators are used to ensure submitted data
 * matches the backends' requirements.
 *
 * @author sidler@mulchprod.de
 * @package module_system
 * @since 4.0
 */
interface ValidatorInterface
{

    /**
     * Validates the passed chunk of data.
     * In most cases, this'll be a string-object.
     *
     * @param string $objValue
     *
     * @return bool
     * @abstract
     */
    public function validate($objValue);

}
