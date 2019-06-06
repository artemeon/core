<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                                                   *
********************************************************************************************************/

$container = \Kajona\System\System\Carrier::getInstance()->getContainer();

/** @var \Kajona\Api\System\AppBuilder $builder */
$builder = $container[\Kajona\Api\System\ServiceProvider::STR_APP_BUILDER];
$builder->run();
