<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                                        *
********************************************************************************************************/

namespace Kajona\System\Admin\Exceptions;

use Kajona\System\System\Exception;

/**
 * Exception thrown in case the controller tries to load a model
 * no longer known. Used to render a user friendly error without
 * notifying an admin.
 */
class ModelNotFoundException extends Exception
{



}
