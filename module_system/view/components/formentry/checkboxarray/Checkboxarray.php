<?php
/*"******************************************************************************************************
*   (c) 2018 ARTEMEON                                                                                   *
*       Published under the GNU LGPL v2.1                                                               *
********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Formentry\Checkboxarray;

use Kajona\System\Admin\Formentries\FormentryCheckboxarray;
use Kajona\System\View\Components\Formentry\FormentryComponentAbstract;

/**
 * Renders a list of options, either as a checkbox or as a radio button.
 * Hierarchical lists are rendered as ul / li combinations.
 *
 * @author stefan.idler@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/formentry/checkboxarray/template.twig
 */
class Checkboxarray extends FormentryComponentAbstract
{
    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $selected;

    /** @var int */
    protected $type = FormentryCheckboxarray::TYPE_CHECKBOX;

    /** @var bool */
    protected $inline = false;

    /** @var bool */
    protected $showSelectedFirst = false;

    /** @var bool */
    protected $showFilter = false;

    /**
     * @param string $name
     * @param string $title
     * @param array $items
     * @param array $selected
     */
    public function __construct($name, $title, array $items, array $selected)
    {
        parent::__construct($name, $title);

        $this->items = $items;
        $this->selected = $selected;
    }


    /**
     * @inheritdoc
     */
    public function buildContext()
    {
        $context = parent::buildContext();

        $context['type'] = $this->type;
        $context["rows"] = $this->itemsToRows();
        $context["filter"] = $this->isShowFilter();

        return $context;
    }

    private function itemsToRows()
    {
        $rows = [];
        if (!$this->showSelectedFirst) {
            foreach ($this->items as $key => $value) {
                $rows[] = [
                    'key' => $key,
                    'title' => $value,
                    'checked' => in_array($key, $this->selected) ? 'checked' : '',
                    'inline' => $this->inline ? '-inline' : '',
                    'readonly' => $this->readOnly ? 'disabled' : '',
                    'type' => $this->type,
                    'value' => $this->type == FormentryCheckboxarray::TYPE_CHECKBOX ? 'checked' : $key,
                    'name' => $this->type == FormentryCheckboxarray::TYPE_CHECKBOX ? $this->name.'['.$key.']' : $this->name,
                ];
            }
        } else {
            foreach ($this->selected as $itemKey) {
                if (!isset($this->items[$itemKey])) {
                    continue;
                }
                $rows[] = [
                    'key' => $itemKey,
                    'title' => $this->items[$itemKey],
                    'checked' => 'checked',
                    'inline' => $this->inline ? '-inline' : '',
                    'readonly' => $this->readOnly ? 'disabled' : '',
                    'type' => $this->type,
                    'value' => $this->type == FormentryCheckboxarray::TYPE_CHECKBOX ? 'checked' : $itemKey,
                    'name' => $this->type == FormentryCheckboxarray::TYPE_CHECKBOX ? $this->name.'['.$itemKey.']' : $this->name,
                ];
                unset($this->items[$itemKey]);
            }
            $this->setShowSelectedFirst(false);
            $rows = array_merge($rows, $this->itemsToRows());
        }

        return $rows;
    }


    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return Checkboxarray
     */
    public function setType(int $type): Checkboxarray
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return bool
     */
    public function isInline(): bool
    {
        return $this->inline;
    }

    /**
     * @param bool $inline
     * @return Checkboxarray
     */
    public function setInline(bool $inline): Checkboxarray
    {
        $this->inline = $inline;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowSelectedFirst(): bool
    {
        return $this->showSelectedFirst;
    }

    /**
     * @param bool $showSelectedFirst
     */
    public function setShowSelectedFirst(bool $showSelectedFirst): void
    {
        $this->showSelectedFirst = $showSelectedFirst;
    }

    /**
     * @return bool
     */
    public function isShowFilter(): bool
    {
        return $this->showFilter;
    }

    /**
     * @param bool $showFilter
     */
    public function setShowFilter(bool $showFilter): void
    {
        $this->showFilter = $showFilter;
    }

}
