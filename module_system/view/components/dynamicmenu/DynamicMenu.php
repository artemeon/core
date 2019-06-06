<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types = 1);

namespace Kajona\System\View\Components\Dynamicmenu;

use Kajona\System\View\Components\AbstractComponent;

/**
 * Simple menu component loads entries on click using a ajax call
 *
 * @author stefan.idler@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/dynamicmenu/template.twig
 */
class DynamicMenu extends AbstractComponent
{
    /**
     * @var string
     */
    private $button;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var int
     */
    private $width;

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $systemId;

    /**
     * @var bool
     */
    private $filter;

    /**
     * @var string
     */
    private $filterPlaceholder;

    /**
     * DynamicMenu constructor.
     * @param string $button
     * @param string $endpoint
     */
    public function __construct(string $button, string $endpoint)
    {
        parent::__construct();

        $this->button = $button;
        $this->endpoint = $endpoint;
    }

    /**
     * @param int $width
     */
    public function setWidth(int $width)
    {
        $this->width = $width;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class)
    {
        $this->class = $class;
    }

    /**
     * @param string $systemId
     */
    public function setSystemId(string $systemId)
    {
        $this->systemId = $systemId;
    }

    /**
     * @param bool $filter
     */
    public function setFilter(bool $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @param string $filterPlaceholder
     */
    public function setFilterPlaceholder(string $filterPlaceholder)
    {
        $this->filterPlaceholder = $filterPlaceholder;
    }

    /**
     * @inheritdoc
     */
    public function renderComponent(): string
    {
        $data = [
            "button" => $this->button,
            "endpoint" => $this->endpoint,
            "menuid" => generateSystemid(),
            "width" => $this->width,
            "class" => $this->class,
            "systemId" => $this->systemId,
            "filter" => $this->filter,
            "filterPlaceholder" => $this->filterPlaceholder,
        ];

        return $this->renderTemplate($data);
    }
}
