<?php
/*"******************************************************************************************************
*   (c) 2007-2019 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;

/**
 * Interface for chart render service
 *
 * @author bernhard.grabietz@artemeon.de
 * @package module_system
 * @since 7.1
 */
interface ChartRendererInterface
{

    /**
     * @param GraphInterface $chart
     * @param string $outputPath
     * @return string
     */
    public function render(GraphInterface $chart, string $outputPath): string;

}
