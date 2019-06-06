<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Headline;

use Kajona\System\View\Components\AbstractComponent;

/**
 * Returns a headline
 *
 * @author stefan.idler@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/headline/template.twig
 */
class Headline extends AbstractComponent
{
    
    private $strText = "";
    private $strClass = "";
    private $strLevel = "h2";

    private $data = [];

    /**
     * Headline constructor.
     * @param string $strText
     * @param string $strClass
     * @param string $strLevel
     */
    public function __construct(string $strText, string $strClass = "", string $strLevel = "h2")
    {
        parent::__construct();
        $this->strText = $strText;
        $this->strClass = $strClass;
        $this->strLevel = $strLevel;
    }


    /**
     * @inheritdoc
     */
    public function renderComponent(): string
    {
        $data = [
            "level" => $this->strLevel,
            "class" => $this->strClass,
            "text" => $this->strText,
            "data" => $this->data,
        ];

        return $this->renderTemplate($data);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return Headline
     */
    public function setData(array $data): Headline
    {
        $this->data = $data;
        return $this;
    }


}
