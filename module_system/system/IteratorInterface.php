<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                      *
********************************************************************************************************/

namespace Kajona\System\System;

/**
 * Interface for all iterators
 * An iterator is used to walk over a collection of elements
 *
 * @package module_system
 * @deprecated replaced by phps' Iterator
 */
interface IteratorInterface
{

    /**
     * Returns the current element
     *
     * @return mixed
     */
    public function getCurrentElement();

    /**
     * Returns the next element, null if no further element available
     *
     * @return mixed
     */
    public function getNextElement();

    /**
     * Checks if theres one more element to return
     *
     * @return bool
     */
    public function isNext();

    /**
     * Returns the first element of the colection,
     * rewinds the cursor
     *
     * @return mixed
     */
    public function getFirstElement();

    /**
     * Returns the number of elements
     *
     * @return int
     */
    public function getNumberOfElements();

}
