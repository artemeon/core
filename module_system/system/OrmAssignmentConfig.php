<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;


/**
 * A simple value holder for the assignment config handling of a mapped property
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 4.8
 */
class OrmAssignmentConfig
{

    private $strTableName = "";
    private $strSourceColumn = "";
    private $strTargetColumn = "";
    private $arrTypeFilter = null;

    function __construct($strTableName, $strSourceColumn, $strTargetColumn, $arrTypeFilter)
    {
        $this->arrTypeFilter = $arrTypeFilter;
        $this->strSourceColumn = $strSourceColumn;
        $this->strTableName = $strTableName;
        $this->strTargetColumn = $strTargetColumn;
    }

    /**
     * Static factory to parse the @objectList annotation of a single property
     *
     * @param $objObject
     * @param $strProperty
     *
     * @return OrmAssignmentConfig
     * @throws OrmException
     */
    public static function getConfigForProperty($objObject, $strProperty)
    {

        $objReflection = new Reflection($objObject);
        $arrPropertyParams = $objReflection->getAnnotationValueForProperty($strProperty, OrmBase::STR_ANNOTATION_OBJECTLIST, ReflectionEnum::PARAMS);
        $strTable = $objReflection->getAnnotationValueForProperty($strProperty, OrmBase::STR_ANNOTATION_OBJECTLIST, ReflectionEnum::VALUES);

        $arrTypeFilter = isset($arrPropertyParams["type"]) ? $arrPropertyParams["type"] : null;

        if (!isset($arrPropertyParams["source"]) || !isset($arrPropertyParams["target"]) || empty($strTable)) {
            throw new OrmException("@objectList annotation for ".$strProperty."@".get_class($objObject)." is malformed", OrmException::$level_FATALERROR);
        }

        return new OrmAssignmentConfig($strTable, $arrPropertyParams["source"], $arrPropertyParams["target"], $arrTypeFilter);
    }

    /**
     * @return null
     */
    public function getArrTypeFilter()
    {
        return $this->arrTypeFilter;
    }

    /**
     * @return string
     */
    public function getStrSourceColumn()
    {
        return $this->strSourceColumn;
    }

    /**
     * @return string
     */
    public function getStrTableName()
    {
        return $this->strTableName;
    }

    /**
     * @return string
     */
    public function getStrTargetColumn()
    {
        return $this->strTargetColumn;
    }


}