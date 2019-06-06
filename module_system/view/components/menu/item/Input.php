<?php
/*"******************************************************************************************************
*   (c) 2018 ARTEMEON                                                                                   *
*       Published under the GNU LGPL v2.1                                                               *
********************************************************************************************************/

declare(strict_types = 1);

namespace Kajona\System\View\Components\Menu\Item;

use Kajona\System\View\Components\Menu\MenuItem;

/**
 * Input
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
class Input extends MenuItem
{
    public function __construct($id = null, $filter = null, $placeholder = null)
    {
        $id = htmlspecialchars($id);
        $filter = htmlspecialchars($filter);
        $placeholder = htmlspecialchars($placeholder);

        $this->setFullEntry("<li class=\"core-component-menu-item\"><input type='text' class='form-control' id='{$id}' value='{$filter}' placeholder='{$placeholder}' autocomplete='off'></li>");
    }
}
