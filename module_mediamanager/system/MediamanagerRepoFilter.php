<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                            *
********************************************************************************************************/

namespace Kajona\Mediamanager\System;

use Kajona\System\System\FilterBase;
use Kajona\System\System\OrmComparatorEnum;
use Kajona\System\System\OrmCondition;

/**
 * @package module_mediamanager
 * @author christoph.kappestein@artemeon.de
 * @module mediamanager
 * @moduleId _mediamanager_module_id_
 */
class MediamanagerRepoFilter extends FilterBase
{
    /**
     * @var bool
     * @tableColumn repo_search_index
     */
    private $bitSearchIndex;

    /**
     *@inheritdoc
     */
    protected function getSingleOrmCondition($strAttributeName, $strValue, $strTableColumn, OrmComparatorEnum $enumFilterCompareOperator = null)
    {
        switch ($strAttributeName) {
            case "bitSearchIndex":
                if ($this->bitSearchIndex === true) {
                    return new OrmCondition(" repo_search_index = 1 ", []);
                } elseif ($this->bitSearchIndex === false) {
                    return new OrmCondition(" repo_search_index = 0 ", []);
                } else {
                    return null;
                }
                break;
        }

        return parent::getSingleOrmCondition($strAttributeName, $strValue, $strTableColumn, $enumFilterCompareOperator);
    }

    /**
     * @return bool
     */
    public function getBitSearchIndex()
    {
        return $this->bitSearchIndex;
    }

    /**
     * @param bool $bitSearchIndex
     */
    public function setBitSearchIndex($bitSearchIndex)
    {
        $this->bitSearchIndex = $bitSearchIndex;
    }
}

