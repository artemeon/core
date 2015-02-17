<?php
/*"******************************************************************************************************
*   (c) 2007-2015 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

/**
 * Extension to the simple formentry-interface,
 * adds a method to fetch a textual representation of the
 * value. May be used for "readonly" fields or generic summaries of
 * a record.
 *
 * @author sidler@mulchprod.de
 * @since 4.2
 * @package module_formgenerator
 */
interface interface_formentry_printable extends interface_formentry {

    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @abstract
     * @return string
     */
    public function getValueAsText();

}
