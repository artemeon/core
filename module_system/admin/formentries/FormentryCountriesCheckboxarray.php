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
class FormentryCountriesCheckboxarray extends FormentryCheckboxarray
{
    public function __construct(string $formName, string $sourceProperty, object $sourceObject = null)
    {
        parent::__construct($formName, $sourceProperty, $sourceObject);

        $this->setArrKeyValues(Lang::getInstance()->getLang("countries", "countries"));
        $this->setShowSelectedFirst(true);
    }
}
