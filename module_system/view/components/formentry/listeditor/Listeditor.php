<?php
/*"******************************************************************************************************
*   (c) 2018 ARTEMEON                                                                                   *
*       Published under the GNU LGPL v2.1                                                               *
********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Formentry\Listeditor;

use Kajona\System\View\Components\Formentry\FormentryComponentAbstract;

/**
 * The listeditor is a form entry which can be used to edit long text options. It is lists all text items and preserves
 * the array key for each option. In case a new option is added the component generate a new random id
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.0
 * @componentTemplate core/module_system/view/components/formentry/listeditor/template.twig
 */
class Listeditor extends FormentryComponentAbstract
{
    /**
     * @var array
     */
    protected $items;

    private $continuousIndexes = false;

    /**
     * @param string $name
     * @param string $title
     * @param array $items
     */
    public function __construct($name, $title, array $items)
    {
        parent::__construct($name, $title);

        $this->items = $items;
    }

    /**
     * @param string $item
     */
    public function addItem($item)
    {
        $this->items[] = $item;
    }

    /**
     * @inheritdoc
     */
    public function buildContext()
    {
        // decode previous tag editor encoded commas which are not needed anymore at the listeditor since we dont need
        // a separator value
        $items = array_map('Kajona\System\Admin\Formentries\FormentryTageditor::decodeValue', $this->items);

        $context = parent::buildContext();
        $context["items"] = $items;
        $context["continuousIndexes"] = $this->continuousIndexes;

        return $context;
    }

    /**
     * @return bool
     */
    public function isContinuousIndexes(): bool
    {
        return $this->continuousIndexes;
    }

    /**
     * @param bool $continuousIndexes
     */
    public function setContinuousIndexes(bool $continuousIndexes): void
    {
        $this->continuousIndexes = $continuousIndexes;
    }



}
