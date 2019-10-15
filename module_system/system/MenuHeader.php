<?php
/*"******************************************************************************************************
*   (c) 2007-2017 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;

/**
 * Class which represents a Header of a module item from the menu
 *
 * @package module_system
 * @author laura.albersmann@artemeon.de
 * @since 7.2
 */
class MenuHeader extends MenuItem
{
    private $right = "";
    private $href = "";

    /**
     *
     * Constructor
     * @param string $right Right to view menu item
     * @param string $href href link of the menu item
     */
    public function __construct(string $right, string $href)
    {
        $this->right = $right;
        $this->href = $href;
    }

    /**
     * Return right
     *
     * @return string
     */
    public function getMenuItemRight()
    {
        return $this->right;
    }

    /**
     *  Returns name
     *
     * @return string
     */
    public function getMenuItemName()
    {
        return "";
    }

    /**
     * Returns href
     *
     * @return string
     */
    public function getMenuItemHref()
    {
        return $this->href;
    }
}