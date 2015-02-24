<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2015 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                                     *
********************************************************************************************************/

/**
 * Interface for a single pdf footer element
 *
 * @author sidler
 * @package module_pdf
 * @since 3.3.0
 */
interface interface_pdf_footer {

    /**
     * Writes the footer for a single page.
     * Use the passed $objPdf to access the pdf.
     *
     * @param class_pdf_tcpdf $objPdf the target pdf-object
     * @return void
     */
    public function writeFooter($objPdf);

}
