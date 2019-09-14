<?php
/*"******************************************************************************************************
*   (c) 2018 ARTEMEON                                                                                   *
*       Published under the GNU LGPL v2.1                                                               *
********************************************************************************************************/

declare(strict_types = 1);

namespace Kajona\System\View\Components\Listbutton;

use Kajona\System\View\Components\AbstractComponent;

/**
 *
 * @author andrii.konoval@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/listbutton/template.twig
 */
class ListButton extends AbstractComponent
{

    private $content = "";

    /**
     * Listbutton constructor.
     * @param string $content
     */
    public function __construct(string $content)
    {
        parent::__construct();
        $this->content = $content;
    }


    /**
     * @inheritdoc
     */
    public function renderComponent(): string
    {
        return $this->renderTemplate(['content' => $this->content]);
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }


}
