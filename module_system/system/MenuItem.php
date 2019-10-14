<?php
/*"******************************************************************************************************
*   (c) 2007-2017 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;

/**
 * Abstract class which represents an item/entry of a module from the menu
 *
 * @package module_system
 * @author laura.albersmann@artemeon.de
 * @since 7.2
 */
abstract class MenuItem
{

    /**
     * Return right
     *
     * @return Right|string
     */
    abstract protected function getMenuItemRight();

    /**
     *  Returns name
     *
     * @return Name|string
     */
    abstract protected function getMenuItemName();

    /**
     * Returns href
     *
     * @return href|string
     */
    abstract protected function getMenuItemHref();

    /**
     * Returns an array containing right, name and link
     *
     * @return array
     */
    public function toArray()
    {
        $menuItemArr = ["link" => $this->getMenuItemHref(), "name" => $this->getMenuItemName(), "href" => $this->getMenuItemHref(), ];
        return $menuItemArr;
    }

}
