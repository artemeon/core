<?php
/*"******************************************************************************************************
*   (c) 2018 ARTEMEON                                                                                   *
*       Published under the GNU LGPL v2.1                                                               *
********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Warningbox;

use Kajona\System\View\Components\AbstractComponent;

/**
 * Returns a warning box, e.g. shown before deleting a record
 *
 * @author sascha.broening@artemeon.de
 * @since 7.0
 * @componentTemplate core/module_system/view/components/formentry/inputcheckbox/template.twig
 */
class Warningbox extends AbstractComponent
{
    
    /**
     * @var string
     */
    protected $strContent;

    /**
     * @var string
     */
    protected $strClass;

    /**
     * @param string $strContent
     * @param string $strClass
     */
    public function __construct(string $strContent, string $strClass = "")
    {
        parent::__construct($strContent, $strClass);

        $this->strContent = $strContent;
        $this->strClass = $strClass;
    }

    /**
     * @inheritdoc
     */
    public function renderComponent(): string
    {
        $data = [
            "content" => $this->strContent,
            "class" => $this->strClass
        ];

        return $this->renderTemplate($data);
    }
}
