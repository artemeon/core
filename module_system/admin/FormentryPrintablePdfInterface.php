<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin;

/**
 * Interface to enable a formentry to generate a pdf-based / formatted output.
 * Only used when rendering a formentry within a pdf.
 *
 * @author sidler@mulchprod.de
 * @since 7.1
 */
interface FormentryPrintablePdfInterface extends FormentryPrintableInterface {

    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @return string
     */
    public function getValueForPdf(): string;

}
