<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Admin\Formentries;

class FormentryDateMonths extends FormentryDate
{

    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
        $this->displayType = self::DISPLAY_MONTHS;

        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);
    }

}
