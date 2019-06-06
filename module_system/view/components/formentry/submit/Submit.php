<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare (strict_types = 1);

namespace Kajona\System\View\Components\Formentry\Submit;

use Kajona\System\System\Carrier;
use Kajona\System\System\StringUtil;
use Kajona\System\View\Components\Buttonwrapper\Buttonwrapper;
use Kajona\System\View\Components\Formentry\FormentryComponentAbstract;

/**
 * Submit Button
 *
 * @author stefan.idler@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/formentry/submit/template.twig
 */
class Submit extends FormentryComponentAbstract
{
    /**
     * @var mixed
     */
    protected $value;

    protected $withWrapper = true;
    protected $onClick = "$(this).addClass('clicked')";

    /**
     * @param string $name
     * @param string $title
     * @param mixed $value
     */
    public function __construct(string $name = "Submit", $value = null)
    {
        if ($value === null) {
            $value = Carrier::getInstance()->getObjLang()->getLang("commons_save", "system");
        }
        parent::__construct($name, null);

        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function renderComponent(): string
    {
        $template = parent::renderComponent();

        if ($this->withWrapper) {
            $wrapper = new Buttonwrapper($template);
            $template = $wrapper->renderComponent();
        }

        return $template;
    }

    /**
     * @inheritdoc
     */
    public function buildContext()
    {
        $context = parent::buildContext();
        $context["value"] = $this->value;
        $context["withWrapper"] = $this->withWrapper;

        $onClick = $this->onClick;
        //backwards compat
        if (StringUtil::startsWith($onClick, 'onclick=')) {
            $onClick = StringUtil::substring($onClick, 8);
        }

        $context["onClick"] = $onClick;

        return $context;
    }

    /**
     * @return bool
     */
    public function isWithWrapper(): bool
    {
        return $this->withWrapper;
    }

    /**
     * @param bool $withWrapper
     * @return Submit
     */
    public function setWithWrapper(bool $withWrapper): Submit
    {
        $this->withWrapper = $withWrapper;
        return $this;
    }

    /**
     * @return string
     */
    public function getOnClick(): string
    {
        return $this->onClick;
    }

    /**
     * @param string $onClick
     * @return Submit
     */
    public function setOnClick(string $onClick): Submit
    {
        $this->onClick = $onClick;
        return $this;
    }

}
