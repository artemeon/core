<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Formentry\InputDateinterval;

use Kajona\System\System\Lang;
use Kajona\System\View\Components\Formentry\FormentryComponentAbstract;

/**
 * InputDateInterval
 *
 * @author andrii.konoval@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_system/view/components/formentry/inputdateinterval/template.twig
 */
class InputDateInterval extends FormentryComponentAbstract
{
    /**
     * @var \DateInterval
     */
    protected $value;

    /**
     * @var string
     */
    protected $addons = "";

    /**
     * @var bool
     */
    protected $readOnly = false;

    /**
     * @var int
     */
    protected $minValue = 0;

    /**
     * InputDateInterval constructor.
     *
     * @param string $name
     * @param string $title
     * @param \DateInterval|null $value
     */
    public function __construct(string $name, string $title, \DateInterval $value = null)
    {
        parent::__construct($name, $title);

        $this->value = $value;
    }

    /**
     * @param \DateInterval $value
     */
    public function setValue(\DateInterval $value): void
    {
        $this->value = $value;
    }

    /**
     * @param string $addons
     */
    public function setAddons(string $addons): void
    {
        $this->addons = $addons;
    }

    /**
     * @param bool $readOnly
     */
    public function setReadOnly(bool $readOnly): void
    {
        $this->readOnly = $readOnly;
    }

    /**
     * @param int $minValue
     */
    public function setMinValue(int $minValue): void
    {
        $this->minValue = $minValue;
    }

    /**
     * @inheritdoc
     */
    public function buildContext()
    {
        $context = parent::buildContext();
        $context["addons"] = $this->addons;
        $context["readonly"] = $this->readOnly;

        $lang = Lang::getInstance();

        $context["options"] = [
            "D" => $lang->getLang("commons_interval_day_days", "system"),
            "W" => $lang->getLang("commons_interval_week_weeks", "system"),
            "M" => $lang->getLang("commons_interval_month_months", "system"),
            "Y" => $lang->getLang("commons_interval_year_years", "system"),
        ];

        $selectedOption = "";
        $intervalValue = "";
        if ($this->value !== null) {
            if ($this->value->d > 0) {
                if ($this->value->d % 7 == 0) {
                    $selectedOption = "W";
                    $intervalValue = $this->value->d / 7;
                } else {
                    $selectedOption = "D";
                    $intervalValue = $this->value->d;
                }
            } elseif ($this->value->m > 0) {
                $selectedOption = "M";
                $intervalValue = $this->value->m;
            } elseif ($this->value->y > 0) {
                $selectedOption = "Y";
                $intervalValue = $this->value->y;
            }
        }
        if (empty($selectedOption)) {
            $selectedOption = "D";
        }

        $context["selected"] = $selectedOption;
        $context["value"] = $intervalValue;
        $context["minValue"] = $this->minValue;

        return $context;
    }
}
