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
     * @return string
     */
    abstract public function getRight();

    /**
     *  Returns name
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Returns href
     *
     * @return string
     */
    abstract public function getHref();

    /**
     * Returns an array containing right, name and link
     *
     * @return array
     */
    public function toArray(): array
    {
        $menuItemArr = ["link" => $this->getHref(), "name" => $this->getName(), "href" => $this->getHref(), ];
        return $menuItemArr;
    }

}
