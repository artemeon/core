<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Formentry\Datesingle;

use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\View\Components\Formentry\FormentryComponentAbstract;

/**
 * Datesingle
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/formentry/datesingle/template.twig
 */
class Datesingle extends FormentryComponentAbstract
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param string $name
     * @param string $title
     * @param Date|null $value
     */
    public function __construct(string $name, string $title, Date $value = null)
    {
        parent::__construct($name, $title);

        $this->value = $value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @inheritdoc
     */
    public function buildContext()
    {
        $context = parent::buildContext();
        $context["value"] = $this->value;
        $context["format"] = Carrier::getInstance()->getObjLang()->getLang("dateStyleShort", "system");
        $context["lang"] = Carrier::getInstance()->getObjSession()->getAdminLanguage() ?: "en";

        return $context;
    }
}
