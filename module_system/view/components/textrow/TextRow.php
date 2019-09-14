<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Textrow;

use Kajona\System\View\Components\AbstractComponent;

/**
 * Returns a single TextRow
 *
 * @author sascha.broening@artemeon.de
 * @since 7.0
 * @componentTemplate core/module_system/view/components/textrow/template.twig
 */
class TextRow extends AbstractComponent
{

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $strText;

    /**
     * @var string
     */
    protected $strClass;

    /**
     * @param string $strText
     * @param string $strClass
     */
    public function __construct(string $strText, string $strClass = "")
    {
        parent::__construct();

        $this->strText = $strText;
        $this->strClass = $strClass;
    }

    /**
     * @inheritdoc
     */
    public function renderComponent(): string
    {
        $data = [
            "text" => $this->strText,
            "class" => $this->strClass,
            "data" => $this->data
        ];

        return $this->renderTemplate($data);
    }
    /**
     * Method to set additional data attributes on an element
     *
     * @param string $key
     * @param mixed $value
     */
    public function setData(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Method to set additional data attributes on an element
     *
     * @param $data
     */
    public function setDataArray($data)
    {
        $this->data = $data;
    }


}
