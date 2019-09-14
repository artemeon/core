<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Mediamanager\System;

use Kajona\System\System\FilterBase;
use Kajona\System\System\OrmComparatorEnum;
use Kajona\System\System\OrmCondition;
use Kajona\System\System\OrmObjectlist;
use Kajona\System\System\OrmObjectlistOrderby;

/**
 * @package module_mediamanager
 * @author christoph.kappestein@artemeon.de
 * @module mediamanager
 * @moduleId _mediamanager_module_id_
 */
class MediamanagerFileFilter extends FilterBase
{
    /**
     * @var bool
     * @tableColumn file_search_content
     */
    private $bitIndexPending;

    /**
     * @var int
     * @tableColumn file_type
     */
    private $intFileType;

    /**
     * @var string
     * @tableColumn agp_mediamanager_file.file_filename
     * @tableColumnDatatype char254
     */
    private $strFilename;

    /**
     * @var bool
     */
    private $bitDateDescOrder = true;

    /**
     *@inheritdoc
     */
    protected function getSingleOrmCondition($strAttributeName, $strValue, $strTableColumn, OrmComparatorEnum $enumFilterCompareOperator = null)
    {
        switch ($strAttributeName) {
            case "bitIndexPending":
                if ($this->bitIndexPending === true) {
                    return new OrmCondition(" file_search_content IS NULL OR file_search_content LIKE ? ", [""]);
                } elseif ($this->bitIndexPending === false) {
                    return new OrmCondition(" file_search_content IS NOT NULL AND file_search_content NOT LIKE ? ", [""]);
                } else {
                    return null;
                }
                break;

            case "strFilename":
                if (!empty($strValue)) {
                    return new OrmCondition(" {$strTableColumn} LIKE ? ", ["{$strValue}%"]);
                }
                break;
        }

        return parent::getSingleOrmCondition($strAttributeName, $strValue, $strTableColumn, $enumFilterCompareOperator);
    }

    /**
     * @inheritDoc
     */
    public function addOrderByConditionToORM(OrmObjectlist $objORM)
    {
        if ($this->bitDateDescOrder) {
            $objORM->addOrderBy(new OrmObjectlistOrderby("system_create_date DESC"));
        }
    }


    /**
     * @return bool
     */
    public function getBitIndexPending()
    {
        return $this->bitIndexPending;
    }

    /**
     * @param bool $bitIndexPending
     */
    public function setBitIndexPending($bitIndexPending)
    {
        $this->bitIndexPending = $bitIndexPending;
    }

    /**
     * @return int
     */
    public function getIntFileType()
    {
        return $this->intFileType;
    }

    /**
     * @param int $intFileType
     */
    public function setIntFileType($intFileType)
    {
        $this->intFileType = $intFileType;
    }

    /**
     * @return string
     */
    public function getStrFilename()
    {
        return $this->strFilename;
    }

    /**
     * @param string $strFilename
     */
    public function setStrFilename($strFilename)
    {
        $this->strFilename = $strFilename;
    }

    /**
     * @param bool $bitDateDescOrder
     */
    public function setBitDateDescOrder(bool $bitDateDescOrder)
    {
        $this->bitDateDescOrder = $bitDateDescOrder;
    }


}
