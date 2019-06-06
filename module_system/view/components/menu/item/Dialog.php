<?php
/*"******************************************************************************************************
 *   (c) 2018 ARTEMEON                                                                                   *
 *       Published under the GNU LGPL v2.1                                                               *
 ********************************************************************************************************/

declare (strict_types = 1);

namespace Kajona\System\View\Components\Menu\Item;

use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\View\Components\Menu\MenuItem;

/**
 * Dialog
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
class Dialog extends MenuItem
{
    public function __construct($title, $link, $icon = null)
    {
        $title = htmlspecialchars($title, ENT_QUOTES);
        $link = htmlspecialchars($link, ENT_QUOTES);

        $linkTitle = $title;
        if ($icon !== null) {
            $linkTitle = Carrier::getInstance()->getObjToolkit()->listButton(AdminskinHelper::getAdminImage($icon)) . " " . $title;
        }

        $this->setFullEntry("<li class=\"core-component-menu-item\"><a href=\"#\" onclick='DialogHelper.showIframeDialog(\"{$link}\", \"{$title}\"); return false;'>{$linkTitle}</a></li>");
    }
}
