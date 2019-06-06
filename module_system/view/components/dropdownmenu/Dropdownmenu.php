<?php
/*"******************************************************************************************************
*   (c) 2018 ARTEMEON                                                                                   *
*       Published under the GNU LGPL v2.1                                                               *
********************************************************************************************************/

declare(strict_types = 1);

namespace Kajona\System\View\Components\Dropdownmenu;

use Kajona\System\View\Components\AbstractComponent;
use Kajona\System\View\Components\Menu\Menu;

/**
 * Simple menu component which renders a dropdown menu
 *
 * @author stefan.idler@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/dropdownmenu/template.twig
 */
class Dropdownmenu extends AbstractComponent
{
    /**
     * @var string
     */
    private $link;

    /**
     * @var Menu
     */
    private $menu;

    /**
     * Dropdownmenu constructor.
     * @param string $link
     * @param Menu $menu
     */
    public function __construct(string $link, Menu $menu)
    {
        parent::__construct();
        $this->link = $link;
        $this->menu = $menu;
    }


    /**
     * @inheritDoc
     */
    public function renderComponent(): string
    {
        $data = [
            "menu" => $this->menu->renderComponent(),
            "link" => $this->link
        ];

        return $this->renderTemplate($data);
    }


}
