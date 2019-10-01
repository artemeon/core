<?php
/*"******************************************************************************************************
*   (c) 2007-2017 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;

/**
 * Class which represents an item/entry of a module from the menu
 *
 * @package module_system
 * @author laura.albersmann@artemeon.de
 * @since 7.2
 */
class MenuItem
{
    private $right = "";
    private $name = "";
    private $link = "";

    /**
     *
     * Constructor
     * @param $right Right to view menu item
     * @param $name Name of the menu item
     * @param $link href link of the menu item
     */
    public function __construct($right, $name, $link)
    {
        $this->right = $right;
        $this->name = $name;
        $this->link = $link;
    }

    /**
     * Return right
     *
     * @return Right|string
     */
    public function getMenuItemRight()
    {
        return $this->right;
    }

    /**
     *  Returns name
     *
     * @return Name|string
     */
    public function getMenuItemName()
    {
        return $this->name;
    }

    /**
     * Returns link
     *
     * @return href|string
     */
    public function getMenuItemLink()
    {
        return $this->link;
    }

    /**
     * Returns an array containing right, name and link
     *
     * @return array
     */
    public function toArray()
    {
        $menuItemArr = ["right" => $this->right, "name" => $this->name, "link" => $this->link];
        return $menuItemArr;
    }
}
