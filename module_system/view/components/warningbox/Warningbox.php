<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Warningbox;

use Kajona\System\View\Components\AbstractComponent;
use Twig\Error\Error as TwigError;

/**
 * Returns a warning box, e.g. shown before deleting a record
 *
 * @author sascha.broening@artemeon.de
 * @since 7.0
 * @componentTemplate core/module_system/view/components/warningbox/template.twig
 */
class Warningbox extends AbstractComponent
{
    public const CSS_CLASS_DANGER = 'alert-danger';

    public const CSS_CLASS_WARNING = 'alert-warning';

    public const CSS_CLASS_INFO = 'alert-info';

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $cssClass;

    /**
     * @var bool
     */
    protected $dismissible = true;

    /**
     * @param string $content
     * @param string $cssClass one of the CSS_CLASS_* constants
     */
    public function __construct(string $content, string $cssClass = self::CSS_CLASS_WARNING)
    {
        parent::__construct();

        $this->content = $content;
        $this->cssClass = $cssClass;
    }

    /**
     * @return string
     * @throws TwigError
     */
    public function renderComponent(): string
    {
        $data = [
            'content' => $this->content,
            'class' => $this->cssClass,
            'dismissible' => $this->dismissible,
        ];

        return $this->renderTemplate($data);
    }

    public function isDismissible(): bool
    {
        return $this->dismissible;
    }

    public function setDismissible(bool $dismissible): void
    {
        $this->dismissible = $dismissible;
    }
}
