<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Formentry\Inputtextarea;

use Kajona\System\View\Components\Formentry\FormentryComponentAbstract;

/**
 * Inputtext
 *
 * @author stefan.idler@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/formentry/inputtextarea/template.twig
 */
class Inputtextarea extends FormentryComponentAbstract
{

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var string
     */
    protected $placeholder = "";

    /**
     * @var int
     */
    protected $numberOfRows = 4;

    /**
     * @param string $name
     * @param string $title
     * @param mixed $value
     */
    public function __construct(string $name, string $title, $value = null)
    {
        parent::__construct($name, $title);

        $this->value = $value;
    }



    /**
     * @inheritdoc
     */
    public function buildContext()
    {
        $context = parent::buildContext();
        $context["value"] = $this->value;
        $context["placeholder"] = $this->placeholder;
        $context["numberOfRows"] = $this->numberOfRows;

        return $context;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * @param string $placeholder
     */
    public function setPlaceholder(?string $placeholder): void
    {
        $this->placeholder = $placeholder;
    }

    /**
     * @return int
     */
    public function getNumberOfRows(): int
    {
        return $this->numberOfRows;
    }

    /**
     * @param int $numberOfRows
     */
    public function setNumberOfRows(int $numberOfRows): void
    {
        $this->numberOfRows = $numberOfRows;
    }




}
