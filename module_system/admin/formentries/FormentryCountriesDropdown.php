<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Admin\Formentries;

use Kajona\System\System\Config;
use Kajona\System\System\Lang;

/**
 * Formentry to show world's countries list
 *
 * @author andrii.konoval@artemeon.de
 * @since 7.2
 */
class FormentryCountriesDropdown extends FormentryDropdown
{
    public function __construct(string $formName, string $sourceProperty, object $sourceObject = null)
    {
        parent::__construct($formName, $sourceProperty, $sourceObject);

        $lang = Lang::getInstance()->getStrTextLanguage();
        if (empty($lang)) {
            $lang = "de";
        }

        $countries = Config::getInstance("module_system", "countries.php")->getConfig("countries_".$lang);

        $this->setArrKeyValues($countries);

    }
}
