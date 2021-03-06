<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Formentry\Inputonoff;

use Kajona\System\View\Components\Formentry\Inputcheckbox\Inputcheckbox;

/**
 * Inputonoff
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/formentry/inputonoff/template.twig
 */
class Inputonoff extends Inputcheckbox
{
    /**
     * @var string
     */
    protected $callback;

    /**
     * @param string $callback
     */
    public function setCallback(string $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @inheritdoc
     */
    public function buildContext()
    {
        // set default data attributes
        if (!isset($this->data["size"])) {
            $this->data["size"] = "small";
        }
        if (!isset($this->data["on-text"])) {
            $this->data["on-text"] = "<i class='fa fa-check fa-white'></i>";
        }
        if (!isset($this->data["off-text"])) {
            $this->data["off-text"] = "<i class='fa fa-times'></i>";
        }

        $context = parent::buildContext();
        $context["callback"] = $this->callback;

        return $context;
    }
}
